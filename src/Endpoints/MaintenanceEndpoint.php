<?php

namespace Kainex\WiseChat\Endpoints;

use Exception;
use Kainex\WiseChat\Endpoints\Maintenance\MaintenanceAuth;
use Kainex\WiseChat\Endpoints\Maintenance\MaintenanceChannels;
use Kainex\WiseChat\Endpoints\Maintenance\MaintenanceI18n;
use Kainex\WiseChat\Endpoints\Maintenance\MaintenanceRecentChats;
use Kainex\WiseChat\Endpoints\Utils\EndpointUtils;
use Kainex\WiseChat\Services\User\UserEventsService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Services\UserMutesService;
use Kainex\WiseChat\Services\MessagesService;
use Kainex\WiseChat\Options;

/**
 * Wise Chat maintenance endpoint class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class MaintenanceEndpoint extends FrontEndpoint {

	private MessagesService $messagesService;
	private UserService $userService;
	private UserMutesService $userMutesService;
	private MaintenanceAuth $maintenanceAuth;
	private MaintenanceI18n $maintenanceI18n;
	private MaintenanceRecentChats $maintenanceRecentChats;
	private MaintenanceChannels $maintenanceChannels;
	private UserEventsService $userEvents;

	/**
	 * @param MessagesService $messagesService
	 * @param UserService $userService
	 * @param UserEventsService $userEvents
	 * @param UserMutesService $userMutesService
	 * @param EndpointUtils $utils
	 * @param MaintenanceAuth $maintenanceAuth
	 * @param MaintenanceI18n $maintenanceI18n
	 * @param MaintenanceRecentChats $maintenanceRecentChats
	 * @param MaintenanceChannels $maintenanceChannels
	 */
	public function __construct(MessagesService $messagesService, UserService $userService, UserEventsService $userEvents, UserMutesService $userMutesService, EndpointUtils $utils, MaintenanceAuth $maintenanceAuth, MaintenanceI18n $maintenanceI18n, MaintenanceRecentChats $maintenanceRecentChats, MaintenanceChannels $maintenanceChannels) {
		parent::__construct($utils);

		$this->messagesService = $messagesService;
		$this->userService = $userService;
		$this->userMutesService = $userMutesService;
		$this->maintenanceAuth = $maintenanceAuth;
		$this->maintenanceI18n = $maintenanceI18n;
		$this->maintenanceRecentChats = $maintenanceRecentChats;
		$this->maintenanceChannels = $maintenanceChannels;
		$this->userEvents = $userEvents;
	}

	/**
	 * Endpoint to perform periodic (every 10-20 seconds) maintenance services like:
	 * - user auto-authentication, authentication requests
	 * - getting the list of events to listen on the client side
	 * - maintenance actions in messages, bans, users, etc.
	 */
	public function maintenanceEndpoint() {
		global $wpdb;

		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$response = array('events' => array());
		try {
			$this->checkGetParams(array('full'));
			$isFull = $this->getGetParam('full') === 'true';

			// periodic maintenance:
			$this->userService->periodicMaintenance();
			$this->messagesService->periodicMaintenance();
			$this->userMutesService->periodicMaintenance(); // TODO: fired too often

			// send user-related content:
			if (!$this->maintenanceAuth->needsAuth()) {
				$this->userService->autoAuthenticateOnMaintenance();

				// merge user dependent events:
				$response['events'] = $this->getUserDependentEvents($isFull);
			}

			// get authentication requests / access denied screens and the public events:
			$response['events'] = array_merge($response['events'], $this->maintenanceAuth->getEvents(), $this->getPublicEvents($isFull));
		} catch (Exception $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendBadRequestStatus();
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Returns events accessible without authentication.
	 *
	 * @param boolean $isFull
	 * @return array
	 */
	private function getPublicEvents(bool $isFull): array {
		$events = array();

		$events[] = array(
			'name' => 'checkSum',
			'data' => $this->generateCheckSum()
		);

		if ($isFull) {
			$events[] = array(
				'name' => 'i18n',
				'data' => $this->maintenanceI18n->getTranslations()
			);
		}

		return $events;
	}

	/**
	 * Returns events accessible for authenticated users only.
	 *
	 * @param boolean $isFull
	 * @return array
	 * @throws Exception
	 */
	private function getUserDependentEvents(bool $isFull): array {
		$events = array();

		if ($this->userEvents->shouldTriggerEvent('wpUserDataRefresh', 'full')) {
			$this->userService->refreshUser();
		}

		if ($isFull || $this->userEvents->shouldTriggerEvent('browser', 'full')) {
			$events[] = [ 'name' => 'browserChannels', 'data' => $this->maintenanceChannels->getBrowserChannels() ];
			$events[] = [ 'name' => 'openChannels', 'data' => $this->maintenanceChannels->getOpenChannels() ];

			if ($this->utils->getOptions()->isOptionEnabled('enable_private_messages')) {
				$events[] = [ 'name' => 'recentChats', 'data' => $this->maintenanceRecentChats->getRecentChats() ];
			}
			if ($this->utils->getOptions()->isOptionNotEmpty('auto_open')) {
				$events[] = [ 'name' => 'autoOpenChannels', 'data' => $this->maintenanceChannels->getAutoOpenChannels() ];
			}
		}

		if ($isFull || $this->userEvents->shouldTriggerEvent('feed', 'full')) {
			$events[] = [ 'name' => 'userFeed', 'data' => $this->maintenanceChannels->getUserFeed() ];
		}

		return $events;
	}

}