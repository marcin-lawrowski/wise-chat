<?php

/**
 * WiseChat channels sources.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatChannelsSourcesService {

	/** @var WiseChatAuthentication */
	private $authentication;

	/** @var WiseChatUserService */
	private $userService;

	/** @var WiseChatService */
	private $service;

	/** @var WiseChatChannelsDAO */
	private $channelsDAO;

	/** @var WiseChatChannelUsersDAO */
	protected $channelUsersDAO;

	/** @var WiseChatUsersDAO */
	private $usersDAO;

	/** @var WiseChatOptions */
	private $options;

	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->channelsDAO = WiseChatContainer::getLazy('dao/WiseChatChannelsDAO');
		$this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->userService = WiseChatContainer::getLazy('services/user/WiseChatUserService');
		$this->service = WiseChatContainer::getLazy('services/WiseChatService');
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
	}

	/**
	 * @return WiseChatChannel[]
	 */
	public function getPublicChannels() {
		return $this->channelsDAO->getByNames((array) $this->options->getOption('channel'));
	}

	/**
	 * @return WiseChatChannelUser[]
	 */
	public function getDirectChannels() {
		$channelUsers = $this->channelUsersDAO->getAllActive();

		if ($this->options->isOptionEnabled('users_list_offline_enable', true)) {
			$channelUsers = $this->appendOfflineUsers($channelUsers);
		}

		$channelUsers = array_filter($channelUsers, function($channelUser) { return $this->isChannelUserVisible($channelUser); });

		return $channelUsers;
	}

	/**
	 * @param $channelUser
	 * @return bool
	 */
	private function isChannelUserVisible($channelUser) {
		if ($channelUser->getUser() === null) {
			return false;
		}

		// do not output anonymous users:
		if ($this->service->isChatAllowedForWPUsersOnly() && $this->userService->isAnonymousUser($channelUser->getUser())) {
			return false;
		}

		// hide chosen roles:
		$wpUser = null;
		$hideRoles = $this->options->getOption('users_list_hide_roles', array());
		if (is_array($hideRoles) && count($hideRoles) > 0 && $channelUser->getUser()->getWordPressId() > 0) {
			$wpUser = $this->usersDAO->getWpUserByID($channelUser->getUser()->getWordPressId());
			if (is_array($wpUser->roles) && count(array_intersect($hideRoles, $wpUser->roles)) > 0) {
				return false;
			}
		}

		// do not render anonymous users:
		if ($this->options->isOptionEnabled('users_list_hide_anonymous', false) && $this->userService->isAnonymousUser($channelUser->getUser())) {
			return false;
		}

		return true;
	}

	/**
	 * Append offline users to the list.
	 *
	 * @param WiseChatChannelUser[] $channelUsers
	 * @return WiseChatChannelUser[]
	 */
	private function appendOfflineUsers($channelUsers) {
		// collect map of channel users:
		$channelWPUsersMap = array();
		foreach ($channelUsers as $channelUser) {
			if ($channelUser->getUser() !== null) {
				$channelWPUsersMap[$channelUser->getUser()->getWordPressId()] = $channelUser;
			}
		}

		// append offline users:
		$wpUsers = $this->usersDAO->getWPUsers();
		$chatUsersMap = $this->usersDAO->getLatestChatUsersByWordPressIds($wpUsers);
		foreach ($wpUsers as $key => $wpUser) {

			if (array_key_exists($wpUser->ID, $channelWPUsersMap)) {
				continue;
			}

			$chatUser = array_key_exists($wpUser->ID, $chatUsersMap) ? $chatUsersMap[$wpUser->ID] : null;
			if ($chatUser === null) {
				// create an in-memory user:
				$chatUser = new WiseChatUser();
				$chatUser->setId('v' . $wpUser->ID);
				$chatUser->setName($this->usersDAO->getChatUserNameFromWpUser($wpUser));
				$chatUser->setWordPressId($wpUser->ID);
			}

			// create in-memory channel-user association:
			$channelUser = new WiseChatChannelUser();
			$channelUser->setUser($chatUser);
			$channelUser->setActive(false);
			$channelUser->setLastActivityTime(time());
			$channelUser->setUserId($chatUser->getId());

			$channelUsers[] = $channelUser;
		}

		return $channelUsers;
	}

}