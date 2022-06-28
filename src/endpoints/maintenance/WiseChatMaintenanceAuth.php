<?php

/**
 * Class WiseChatMaintenanceAuth
 *
 * Adds user / auth related functionalities to the maintenance endpoint.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMaintenanceAuth {

	/**
	 * @var WiseChatClientSide
	 */
	protected $clientSide;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	 * @var WiseChatUserSettingsDAO
	 */
	protected $userSettingsDAO;

	/**
	 * @var WiseChatHttpRequestService
	 */
	private $httpRequestService;

	/**
	 * @var WiseChatService
	 */
	protected $service;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	public function __construct() {
		$this->clientSide = WiseChatContainer::getLazy('services/client-side/WiseChatClientSide');
		$this->options = WiseChatOptions::getInstance();
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->userSettingsDAO = WiseChatContainer::getLazy('dao/user/WiseChatUserSettingsDAO');
		$this->service = WiseChatContainer::getLazy('services/WiseChatService');
		$this->httpRequestService = WiseChatContainer::getLazy('services/WiseChatHttpRequestService');
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
	}

	/**
	 * @return array
	 */
	public function getEvents() {
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
	 */
	public function getUser() {
		$user = $this->authentication->getUser();
		$userData = $user->getData();

		$userSettings = array(
			'enableNotifications' => !array_key_exists('disableNotifications', $userData) ? true : !$userData['disableNotifications'],
			'textColor' => array_key_exists('textColor', $userData) ? $userData['textColor'] : null,
			'allowChangeTextColor' => $this->options->isOptionEnabled('allow_change_text_color', true),
			'allowControlUserNotifications' => $this->options->isOptionEnabled('allow_control_user_notifications') && $this->options->isOptionEnabled('enable_private_messages', false) && $user->getWordPressId() > 0,
			'allowMuteSound' => $this->options->isOptionEnabled('allow_mute_sound') && strlen($this->options->getEncodedOption('sound_notification')) > 0,
			'allowChangeUserName' => $this->options->isOptionEnabled('allow_change_user_name', true) && !($user->getWordPressId() > 0),
		);

		$userSettings['allowCustomize'] = $userSettings['allowChangeTextColor'] || $userSettings['allowControlUserNotifications'] || $userSettings['allowMuteSound'] || $userSettings['allowChangeUserName'];

		return array(
			'id' => WiseChatCrypt::encryptToString($user->getId()),
			'cacheId' => $this->clientSide->getUserCacheId($user),
			'name' => $user->getName(),
			'settings' => array_merge($userSettings, $this->userSettingsDAO->getAll()),
			'rights' => $this->getUserRights()
		);
	}

	private function getAuth() {
		static $auth = false;

		if ($auth !== false) {
			return $auth;
		}

		$auth = null;
		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			$auth = $this->getAccessDeniedResponse($this->options->getOption('message_error_4', __('Only logged in users are allowed to enter the chat', 'wise-chat')));
		}

		if ($this->service->isChatRestrictedForCurrentUserRole() || $this->service->isChatRestrictedToCurrentUser()) {
			$auth = $this->getAccessDeniedResponse($this->options->getOption('message_error_11', __('You are not allowed to enter the chat.', 'wise-chat')));
		}

		if ($this->service->isIpKicked()) {
			$auth = $this->getAccessDeniedResponse($this->options->getOption('message_error_12', __('You are blocked from using the chat', 'wise-chat')));
		}

		if (!$this->service->isChatOpen()) {
			$auth = $this->getAccessDeniedResponse($this->options->getOption('message_error_5', __('The chat is closed now', 'wise-chat')));
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
			'deleteMessages' => $this->usersDAO->hasCurrentWpUserRight('delete_message'),
			'deleteOwnMessages' => $this->options->isOptionEnabled('enable_delete_own_messages', false),
			'muteUsers' => $this->usersDAO->hasCurrentWpUserRight('ban_user'),
			'banUsers' => $this->usersDAO->hasCurrentWpUserRight('kick_user'),
			'spamReport' => $this->options->isOptionEnabled('spam_report_enable_all', true) || $this->usersDAO->hasCurrentWpUserRight('spam_report'),
		);
	}
}