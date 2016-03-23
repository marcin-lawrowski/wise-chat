<?php

/**
 * WiseChat user services.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatUserService {
	const USERS_ACTIVITY_TIME_FRAME = 80;
	const USERS_PRESENCE_TIME_FRAME = 86400;
	
	/**
	* @var WiseChatActions
	*/
	private $actions;

	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;

	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	 * @var WiseChatUserEvents
	 */
	private $userEvents;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
		$this->actions = WiseChatContainer::getLazy('services/user/WiseChatActions');
		$this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
		$this->messagesDAO = WiseChatContainer::getLazy('dao/WiseChatMessagesDAO');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->userEvents = WiseChatContainer::getLazy('services/user/WiseChatUserEvents');
	}
	
	/**
	* Maintenance actions performed on the init phase.
	*
	* @return null
	*/
	public function initMaintenance() {
		$userSettingsDAO = WiseChatContainer::get('dao/user/WiseChatUserSettingsDAO');
		$userSettingsDAO->initialize();
	}
	
	/**
	* Maintenance actions performed at start-up.
	*
	* @param WiseChatChannel $channel
	*
	* @return null
	*/
	public function startUpMaintenance($channel) {
		$this->userEvents->resetEventTracker('usersList', $channel->getName());
		$this->userEvents->resetEventTracker('ping', $channel->getName());
	}
	
	/**
	* Maintenance actions performed periodically. The method authenticates user if there was
	* no authentication performed.
	*
	* @param WiseChatChannel $channel
	*
	* @return null
	*/
	public function periodicMaintenance($channel) {
		// check and authenticate user:
		if (!$this->authentication->isAuthenticated()) {
			$user = $this->authentication->authenticateAnonymously();
			$this->actions->publishAction(
				'refreshPlainUserName', array('name' => $user->getName()), $user
			);
		}

		// signal presence in the channel:
		if ($this->userEvents->shouldTriggerEvent('ping', $channel->getName())) {
			$this->markPresenceInChannel($channel);
		}
		
		$this->refreshChannelUsersData();
	}
	
	/**
	* Refreshes channel users data.
	*
	* @return null
	*/
	public function refreshChannelUsersData() {
		$this->channelUsersDAO->deleteOlderByLastActivityTime(self::USERS_PRESENCE_TIME_FRAME);
		$this->channelUsersDAO->updateActiveForOlderByLastActivityTime(false, self::USERS_ACTIVITY_TIME_FRAME);
	}

	/**
	 * Checks if the current user has right to send a message.
	 *
	 * @return bool
	 */
	public function isSendingMessagesAllowed() {
		if ($this->usersDAO->isWpUserLogged()) {
			$targetRoles = (array) $this->options->getOption("read_only_for_roles", array());
			if (count($targetRoles) > 0) {
				$wpUser = $this->usersDAO->getCurrentWpUser();

				return !is_array($wpUser->roles) || count(array_intersect($targetRoles, $wpUser->roles)) == 0;
			} else {
				return true;
			}
		} else {
			return !$this->options->isOptionEnabled('read_only_for_anonymous', false);
		}
	}
	
	/**
	* If the user has logged in then replace anonymous username with WordPress user name.
	* If WordPress user logs out then the anonymous username is restored.
	*
	* @return null
	*/
	public function switchUser() {
		$currentWPUser = $this->usersDAO->getCurrentWpUser();

		if (!$this->authentication->isAuthenticated()) {
			if ($currentWPUser !== null && strlen($currentWPUser->display_name) > 0) {
				$user = $this->authentication->authenticateAnonymously();
				$this->authentication->setOriginalUserName($user->getName());

				// update username and WP user ID:
				$user->setWordPressId(intval($currentWPUser->ID));
				$user->setName($currentWPUser->display_name);
				$this->usersDAO->save($user);
			}
			return;
		} else {
			$user = $this->authentication->getUser();
			if ($user === null) {
				$this->authentication->dropAuthentication();
				$user = $this->authentication->authenticateAnonymously();
			}
			$userName = $user->getName();

			if ($currentWPUser !== null) {
				$displayName = $currentWPUser->display_name;
				if (strlen($displayName) > 0 && $userName != $displayName) {
					if ($this->authentication->getOriginalUserName() === null) {
						$this->authentication->setOriginalUserName($userName);
					}

					// update username and WP user ID:
					$user->setName($displayName);
					$user->setWordPressId(intval($currentWPUser->ID));
					$this->usersDAO->save($user);

					$this->refreshUserName($user);
				}
			} else {
				$originalUserName = $this->authentication->getOriginalUserName();
				if ($originalUserName !== null && $userName != $originalUserName) {
					// update username and WP user ID:
					$user->setName($originalUserName);
					$user->setWordPressId(null);
					$this->usersDAO->save($user);

					$this->refreshUserName($user);
				}
			}
		}
	}
	
	/**
	* Sets a new name for current user.
	*
	* @param string $userName A new username to set
	*
	* @return string New username
	* @throws Exception On validation error
	*/
	public function changeUserName($userName) {
		if (
			!$this->options->isOptionEnabled('allow_change_user_name') ||
			$this->usersDAO->getCurrentWpUser() !== null ||
			!$this->authentication->isAuthenticated()
		) {
			throw new Exception('Unsupported operation');
		}

		$userName = $this->authentication->validateUserName($userName);
		$user = $this->authentication->getUser();

		// set new username and refresh it:
		$user->setName($userName);
		$this->usersDAO->save($user);
		$this->refreshNewUserName($user);
		$this->authentication->setOriginalUserName($userName);

		return $userName;
	}
	
	/**
	* Sets text color for messages typed by the current user.
	*
	* @param string $color
	*
	* @throws Exception If an error occurred
	*/
	public function setUserTextColor($color) {
		if (!$this->authentication->isAuthenticated()) {
			throw new Exception('Unsupported operation');
		}
		if ($color != 'null' && !preg_match("/^#[a-fA-F0-9]{6}$/", $color)) {
			throw new Exception('Invalid color signature');
		}
		if ($color == 'null') {
			$color = '';
		}

		$user = $this->authentication->getUser();
		$user->setDataProperty('textColor', $color);
		$this->usersDAO->save($user);
		$this->userEvents->resetEventTracker('usersList');
		$this->actions->publishAction(
			'setMessagesProperty', array(
				'chatUserId' => $user->getId(),
				'propertyName' => 'textColor',
				'propertyValue' => $color
			)
		);
	}
	
	/**
	* Marks presence of the current user in the given channel.
	*
	* @param WiseChatChannel $channel
	*
	* @return null
	*/
	private function markPresenceInChannel($channel) {
		$user = $this->authentication->getUser();
		if ($user !== null) {
			$channelUser = $this->channelUsersDAO->getByUserIdAndChannelId($user->getId(), $channel->getId());

			if ($channelUser === null) {
				$channelUser = new WiseChatChannelUser();
				$channelUser->setActive(true);
				$channelUser->setLastActivityTime(time());
				$channelUser->setUserId($user->getId());
				$channelUser->setChannelId($channel->getId());
				$this->channelUsersDAO->save($channelUser);
			} else {
				$channelUser->setActive(true);
				$channelUser->setLastActivityTime(time());
				$this->channelUsersDAO->save($channelUser);
			}
		}
	}
	
	/**
	* Refreshes username on user interface. Resets event tracker for users list and
	* publishes an action that refreshes username in plain places.
	*
	* @param WiseChatUser $user
	*
	* @return null
	*/
	private function refreshUserName($user) {
		$this->userEvents->resetEventTracker('usersList');
		$this->actions->publishAction('refreshPlainUserName', array('name' => $user->getName()), $user);
	}
	
	/**
	* Refreshes username after setting a new one.
	*
	* @param WiseChatUser $user
	*
	* @return null
	*/
	private function refreshNewUserName($user) {
        WiseChatContainer::load('dao/criteria/WiseChatMessagesCriteria');

        $this->refreshUserName($user);
        $this->messagesDAO->updateUserNameByCriteria(
			$user->getName(), WiseChatMessagesCriteria::build()->setUserId($user->getId())
		);

        /** @var WiseChatRenderer $renderer */
        $renderer = WiseChatContainer::get('rendering/WiseChatRenderer');

        $criteria = new WiseChatMessagesCriteria();
		$criteria->setUserId($user->getId());
		$messages = $this->messagesDAO->getAllByCriteria($criteria);
		if (count($messages) > 0) {
			$messagesIds = array();
			$renderedUserName = null;
			foreach ($messages as $message) {
				$messagesIds[] = $message->getId();
				if ($renderedUserName === null) {
					$renderedUserName = $renderer->getRenderedUserName($message);
				}
			}

			$this->actions->publishAction(
				'replaceUserNameInMessages', array(
					'renderedUserName' => $renderedUserName,
					'messagesIds' => $messagesIds
				)
			);
		}
	}
}