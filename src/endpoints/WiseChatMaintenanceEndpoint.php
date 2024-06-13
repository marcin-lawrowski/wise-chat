<?php

WiseChatContainer::load('endpoints/WiseChatEndpoint');

/**
 * Wise Chat maintenance endpoint class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMaintenanceEndpoint extends WiseChatEndpoint {

	/** @var WiseChatMaintenanceAuth */
	private $maintenanceAuth;

	/** @var WiseChatMaintenanceI18n */
	private $maintenanceI18n;

	/** @var WiseChatMaintenanceRecentChats */
	private $maintenanceRecentChats;

	/** @var WiseChatMaintenanceChannels */
	private $maintenanceChannels;

	public function __construct() {
		parent::__construct();

		/** @var WiseChatMaintenanceAuth maintenanceAuth */
		$this->maintenanceAuth = WiseChatContainer::getLazy('endpoints/maintenance/WiseChatMaintenanceAuth');

		/** @var WiseChatMaintenanceI18n maintenanceI18n */
		$this->maintenanceI18n = WiseChatContainer::getLazy('endpoints/maintenance/WiseChatMaintenanceI18n');

		/** @var WiseChatMaintenanceRecentChats maintenanceRecentChats */
		$this->maintenanceRecentChats = WiseChatContainer::getLazy('endpoints/maintenance/WiseChatMaintenanceRecentChats');

		/** @var WiseChatMaintenanceChannels maintenanceChannels */
		$this->maintenanceChannels = WiseChatContainer::getLazy('endpoints/maintenance/WiseChatMaintenanceChannels');
	}

	/**
	 * Endpoint to perform periodic (every 10-20 seconds) maintenance services like:
	 * - user auto-authentication, authentication requests
	 * - getting the list of events to listen on the client side
	 * - maintenance actions in messages, bans, users, etc.
	 */
	public function maintenanceEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$response = array('events' => array());
		try {
			$this->checkGetParams(array('full', 'channelIds'));
			$isFull = $this->getGetParam('full') === 'true';

			// periodic maintenance:
			$this->userService->periodicMaintenance();
			$this->messagesService->periodicMaintenance();
			$this->bansService->periodicMaintenance();

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
	private function getPublicEvents($isFull) {
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
	private function getUserDependentEvents($isFull) {
		$events = array();

		if ($isFull || $this->userEvents->shouldTriggerEvent('browser', 'full')) {
			if ($this->options->isOptionEnabled('users_list_offline_enable', true) && $this->options->isOptionEnabled('enable_private_messages')) {
				$events[] = array(
					'name' => 'recentChats',
					'data' => $this->maintenanceRecentChats->getRecentChats()
				);
			}

			// public channels:
			if ($this->maintenanceChannels->arePublicChannelsEnabled()) {
				$events[] = array(
					'name' => 'publicChannels',
					'data' => $this->maintenanceChannels->getPublicChannels()
				);
			}

			// direct channels:
			if ($this->options->isOptionEnabled('show_users')) {
				$events[] = array(
					'name' => 'directChannels',
					'data' => $this->maintenanceChannels->getDirectChannels()
				);
			}

			// auto open channels:
			$events[] = array(
				'name' => 'autoOpenChannel',
				'data' => $this->maintenanceChannels->getAutoOpenChannel()
			);

			// global channels storage (only auto-open for now):
			$events[] = array(
				'name' => 'channels',
				'data' => $this->maintenanceChannels->getChannels()
			);
		}

		if ($isFull || $this->userEvents->shouldTriggerEvent('counter', 'full')) {
			$events[] = array(
				'name' => 'onlineUsersCounter',
				'data' => $this->maintenanceChannels->getDirectChannelsNumber()
			);
		}

		return $events;
	}

}