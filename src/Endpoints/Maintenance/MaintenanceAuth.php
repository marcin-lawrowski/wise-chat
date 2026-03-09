<?php

namespace Kainex\WiseChat\Endpoints\Maintenance;

use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\User\UserSettingsDAO;
use Kainex\WiseChat\Model\Channel\ChannelUser;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Rendering\UITemplates;
use Kainex\WiseChat\Services\Channels\Listing\ChannelsSourcesService;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Services\MessagesService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\ChannelsService;
use Kainex\WiseChat\Services\HttpRequestService;
use Kainex\WiseChat\Services\ChatService;
use Kainex\WiseChat\Crypt;
use Kainex\WiseChat\Options;
use Kainex\WiseChat\Services\User\UserService;

/**
 * Class WiseChatMaintenanceAuth
 *
 * Adds user / auth related functionalities to the maintenance endpoint.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MaintenanceAuth {

	/**
	 * @var ClientSide
	 */
	private $clientSide;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var AuthenticationService
	 */
	private $authentication;

	/**
	 * @var UserSettingsDAO
	 */
	private $userSettingsDAO;

	/**
	 * @var HttpRequestService
	 */
	private $httpRequestService;

	/**
	 * @var ChatService
	 */
	private $service;

	/**
	 * @var UsersDAO
	 */
	private $usersDAO;

	private ChannelsService $channelsService;

	/**
	 * @param ClientSide $clientSide
	 * @param Options $options
	 * @param AuthenticationService $authentication
	 * @param UserSettingsDAO $userSettingsDAO
	 * @param HttpRequestService $httpRequestService
	 * @param ChatService $service
	 * @param UsersDAO $usersDAO
	 * @param ChannelsService $channelsService
	 */
	public function __construct(ClientSide $clientSide, Options $options, AuthenticationService $authentication, UserSettingsDAO $userSettingsDAO, HttpRequestService $httpRequestService, ChatService $service, UsersDAO $usersDAO, ChannelsService $channelsService) {
		$this->clientSide = $clientSide;
		$this->options = $options;
		$this->authentication = $authentication;
		$this->userSettingsDAO = $userSettingsDAO;
		$this->httpRequestService = $httpRequestService;
		$this->service = $service;
		$this->usersDAO = $usersDAO;
		$this->channelsService = $channelsService;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getEvents(): array {
		$events = array();
		$user = null;
		$auth = $this->getAuth();
		if (!$auth) {
			$user = $this->getUser();
		}

		$events[] = array(
			'name' => 'user',
			'data' => $user
		);
		$events[] = array(
			'name' => 'auth',
			'data' => $auth
		);

		return $events;
	}

	/**
	 * @return bool
	 */
	public function needsAuth() {
		return $this->getAuth() !== null;
	}

	/**
	 * Returns all user settings (including cookie-stored settings).
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getUser(): array {
		$user = $this->authentication->getUser();
		$userData = $user->getData();

		$userSettings = array(
			'enableNotifications' => !array_key_exists('disableNotifications', $userData) ? true : !$userData['disableNotifications'],
			'textColor' => array_key_exists('textColor', $userData) ? $userData['textColor'] : null,
			'allowChangeTextColor' => $this->options->isOptionEnabled('allow_change_text_color'),
			'allowControlUserNotifications' => $this->options->isOptionEnabled('allow_control_user_notifications') && $this->options->isOptionEnabled('enable_private_messages', false) && $user->getWordPressId() > 0,
			'allowMuteSound' => $this->options->isOptionEnabled('allow_mute_sound') && $this->options->getEncodedOption('sound_notification'),
			'allowChangeUserName' => $this->options->isOptionEnabled('allow_change_user_name') && !($user->getWordPressId() > 0) && !$this->authentication->isAuthenticatedExternally(),
		);

		$userSettings['allowCustomize'] = $userSettings['allowChangeTextColor'] || $userSettings['allowControlUserNotifications'] || $userSettings['allowMuteSound'] || $userSettings['allowChangeUserName'];

		return array(
			'id' => Crypt::encryptToString($user->getId()),
			'cacheId' => $this->clientSide->getUserCacheId($user),
			'name' => $user->getName(),
			'settings' => array_merge($userSettings, $this->userSettingsDAO->getAll()),
			'rights' => $this->getUserRights(),
			'openChannels' => $this->getOpenChannels()
		);
	}

	private function getAuth() {
		static $auth = false;

		if ($auth !== false) {
			return $auth;
		}

		$auth = null;
		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			$auth = $this->getAccessDeniedResponse(__('Only logged in users are allowed to enter the chat', 'wise-chat'));
		}

		if ($this->service->isChatRestrictedForCurrentUserRole() || $this->service->isChatRestrictedToCurrentUser()) {
			$auth = $this->getAccessDeniedResponse(__('You are not allowed to enter the chat.', 'wise-chat'));
		}

		if ($this->service->isBanned()) {
			$auth = $this->getAccessDeniedResponse(__('You are blocked from using the chat', 'wise-chat'));
		}

		if (!$this->service->isChatOpen()) {
			$auth = $this->getAccessDeniedResponse(__('The chat is closed now', 'wise-chat'));
		}

		if ($this->service->hasUserToBeForcedToEnterName()) {
			$auth = array(
				'mode' => 'auth-username',
				'nonce' => wp_create_nonce('un'.$this->httpRequestService->getRemoteAddress())
			);
		}

		return $auth;
	}

	private function getAccessDeniedResponse($error) {
		return array(
			'mode' => 'access-denied',
			'error' => $error
		);
	}

	private function getUserRights() {
		return array(
			'approveMessages' => false,
			'deleteMessages' => $this->usersDAO->hasCurrentWpUserRight('delete_message') || $this->usersDAO->hasCurrentBpUserRight('delete_message'),
			'deleteOwnMessages' => false,
			'editMessages' => false,
			'editOwnMessages' => false,
			'muteUsers' => $this->usersDAO->hasCurrentWpUserRight('mute_user') || $this->usersDAO->hasCurrentBpUserRight('mute_user'),
			'banUsers' => $this->usersDAO->hasCurrentWpUserRight('ban_user') || $this->usersDAO->hasCurrentBpUserRight('ban_user'),
			'spamReport' => $this->options->isOptionEnabled('spam_report_enable_all', true) || $this->usersDAO->hasCurrentWpUserRight('spam_report') || $this->usersDAO->hasCurrentBpUserRight('spam_report'),
			'replyToMessages' => $this->options->isOptionEnabled('enable_reply_to_messages', true),
			'createChannels' => false,
			'searchChannels' => false,
		);
	}

	private function getOpenChannels(): array {
		$openChannels = $this->channelsService->getOpenChannels($this->authentication->getUserIdOrNull());

		$output = [];
		foreach ($openChannels as $channel) {
			$output[] = $this->clientSide->encryptChannelId($channel);
		}

		return $output;
	}

}