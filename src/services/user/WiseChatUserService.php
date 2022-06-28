<?php

/**
 * WiseChat user services.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatUserService {
	const USERS_ACTIVITY_TIME_FRAME = 30;
	const USERS_PRESENCE_TIME_FRAME = 86400;

	/**
	 * @var WiseChatClientSide
	 */
	private $clientSide;
	
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
	 * @var WiseChatUserSettingsDAO
	 */
	private $userSettingsDAO;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	 * @var WiseChatAuthorization
	 */
	protected $authorization;

	/**
	 * @var WiseChatUserEvents
	 */
	private $userEvents;

	/**
	 * @var WiseChatService
	 */
	private $service;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
		$this->userSettingsDAO = WiseChatContainer::getLazy('dao/user/WiseChatUserSettingsDAO');
		$this->actions = WiseChatContainer::getLazy('services/user/WiseChatActions');
		$this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
		$this->messagesDAO = WiseChatContainer::getLazy('dao/WiseChatMessagesDAO');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->userEvents = WiseChatContainer::getLazy('services/user/WiseChatUserEvents');
		$this->service = WiseChatContainer::getLazy('services/WiseChatService');
		$this->authorization = WiseChatContainer::getLazy('services/user/WiseChatAuthorization');
		$this->clientSide = WiseChatContainer::getLazy('services/client-side/WiseChatClientSide');
	}

	/**
	 * Auto-authenticate user if no additional steps need to be taken (no username forcing, etc.)
	 *
	 * @throws Exception
	 */
	public function autoAuthenticateOnMaintenance() {
		if ($this->authentication->isAuthenticated()) {
			return;
		}
		$user = null;

		// check if there is a WordPress user logged in:
		$currentWPUser = $this->usersDAO->getCurrentWpUser();
		if ($currentWPUser !== null) {
			$user = $this->authentication->authenticateWithWpUser($currentWPUser);
		}

		// authenticate only if anonymous login is not prohibited:
		if ($user === null && $this->options->getIntegerOption('access_mode', 0) != 1) {
			$user = $this->authentication->authenticateAnonymously();
		}

		if ($user !== null) {
			$this->actions->publishAction(
				'refreshPlainUserName', array('name' => $user->getName()), $user
			);

			$this->setCurrentUserOnlineStatus();
		}
	}

	/**
	 * Maintenance actions performed on users.
	 *
	 * @throws Exception
	 */
	public function periodicMaintenance() {
		$this->setCurrentUserOnlineStatus();
		$this->setInactiveUsersOfflineStatus();
	}
	
	/**
	 * Detects and marks offline users.
	 * TODO: possibly remove these queries or execute less often
	*/
	public function setInactiveUsersOfflineStatus() {
		$timeFrame = $this->options->getIntegerOption('user_name_lock_window_seconds', self::USERS_PRESENCE_TIME_FRAME);
		if ($timeFrame < 600) {
			$timeFrame = self::USERS_PRESENCE_TIME_FRAME;
		}
		$this->channelUsersDAO->deleteOlderByLastActivityTime($timeFrame);
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
	 * @throws Exception
	 */
	public function switchUser() {
		$currentWPUser = $this->usersDAO->getCurrentWpUser();

		if (!$this->authentication->isAuthenticated()) {
			if ($currentWPUser !== null) {
				$this->authentication->authenticateWithWpUser($currentWPUser);
			}
		} else {
			$user = $this->authentication->getUser();

			// anonymous switched to WP:
			if ($user->getWordPressId() === null && $currentWPUser !== null) {
				// forget the anonymous account:
				$this->authentication->dropAuthentication();

				$user = $this->authentication->authenticateWithWpUser($currentWPUser);
			}

			// WP switched to anonymous:
			if ($user->getWordPressId() !== null && $currentWPUser === null) {
				$this->authentication->dropAuthentication();
				$this->authentication->authenticateAnonymously();
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
			!$this->options->isOptionEnabled('allow_change_user_name', true) ||
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
		$this->refreshUserName($user);

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
		if ($color != '' && !preg_match("/^#[a-fA-F0-9]{6}$/", $color)) {
			throw new Exception('Invalid color signature');
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
	 * Gets user property.
	 *
	 * @param string $property
	 * @return mixed|null
	 *
	 * @throws Exception If an error occurred
	 */
	public function getProperty($property) {
		if (!$this->authentication->isAuthenticated()) {
			throw new Exception('Could not get a property on unauthenticated user');
		}

		return $this->authentication->getUser()->getDataProperty($property);
	}

	/**
	 * Sets user property.
	 *
	 * @param string $property
	 * @param mixed $value
	 *
	 * @throws Exception If an error occurred
	 */
	public function setProperty($property, $value) {
		if (!$this->authentication->isAuthenticated()) {
			throw new Exception('Could not set a property on unauthenticated user');
		}

		$user = $this->authentication->getUser();
		$user->setDataProperty($property, $value);
		$this->usersDAO->save($user);
	}

	/**
	 * Unsets all properties that match the prefix.
	 *
	 * @param string $prefix
	 *
	 * @throws Exception If an error occurred
	 */
	public function unsetPropertiesByPrefix($prefix) {
		if (!$this->authentication->isAuthenticated()) {
			throw new Exception('Could not unset a property on unauthenticated user');
		}

		$user = $this->authentication->getUser();
		$allProperties = $user->getData();
		if (is_array($allProperties)) {
			foreach ($allProperties as $key => $value) {
				if (strpos($key, $prefix) === 0) {
					unset($allProperties[$key]);
				}
			}
			$user->setData($allProperties);
			$this->usersDAO->save($user);
		}
	}

	/**
	 * Calculates hash for given user ID. Hash are unique across sites (multisite safe).
	 *
	 * @param string $userId
	 * @return string
	 */
	public static function getUserHash($userId) {
		return sha1(wp_salt().get_current_blog_id().$userId);
	}

	/**
	 * Handles WordPress user profile changes.
	 *
	 * @param integer $wpUserId
	 * @param WP_User $oldWpUser
	 */
	public function onWpUserProfileUpdate($wpUserId, $oldWpUser) {
		$wpUser = $this->usersDAO->getWpUserByID($wpUserId);

		if ($wpUser !== null && $oldWpUser !== false) {
			$userName = $this->usersDAO->getChatUserNameFromWpUser($wpUser);
			$this->usersDAO->updateNameByWordPressId($userName, $wpUser->ID);
			$this->messagesDAO->updateUserNameByWordPressUserId($userName, $wpUser->ID);
		}
	}

	/**
	 * Sets the status of the current user to "online".
	 *
	 * @throws Exception
	 */
	private function setCurrentUserOnlineStatus() {
		$user = $this->authentication->getUser();
		if ($user !== null) {
			$channelUser = $this->channelUsersDAO->getByUserId($user->getId());

			if ($channelUser === null) {
				$channelUser = new WiseChatChannelUser();
				$channelUser->setActive(true);
				$channelUser->setLastActivityTime(time());
				$channelUser->setUserId($user->getId());
				$this->channelUsersDAO->save($channelUser);
			} else {
				$channelUser->setActive(true);
				$channelUser->setLastActivityTime(time());
				$this->channelUsersDAO->save($channelUser);
			}
		}
	}

	/**
	 * Refreshes username after setting a new one.
	 *
	 * @param WiseChatUser $user
	 * @throws Exception
	 */
	private function refreshUserName($user) {
		$this->userEvents->resetEventTracker('browser');
        WiseChatContainer::load('dao/criteria/WiseChatMessagesCriteria');

		$updateCriteria = WiseChatMessagesCriteria::build()->setUserId($user->getId());
		if ($this->options->isOptionEnabled('enable_private_messages')) {
			$updateCriteria->setRecipientOrSenderId($user->getId());
		}
        $this->messagesDAO->updateUserNameByCriteria($user->getName(), $updateCriteria);
		$this->actions->publishAction(
			'refreshUserName', array(
				'name' => $user->getName(),
				'id' => $this->clientSide->encryptUserId($user->getId())
			)
		);
		$this->actions->publishAction(
			'refreshChannelName', array(
				'name' => $user->getName(),
				'id' => $this->clientSide->encryptDirectChannelId($user->getId())
			)
		);
	}

	/**
	 * Returns text color if the color is defined for user's role.
	 *
	 * @param WiseChatUser $user
	 * @return string|null
	 */
	public function getTextColorDefinedByUserRole($user) {
		$textColor = null;
		$userRoleToColorMap = $this->options->getOption('text_color_user_roles', array());

		if ($user !== null && $user->getWordPressId() > 0) {
			$wpUser = $this->usersDAO->getWpUserByID($user->getWordPressId());
			if (is_array($wpUser->roles)) {
				$commonRoles = array_intersect($wpUser->roles, array_keys($userRoleToColorMap));
				if (count($commonRoles) > 0 && array_key_exists(0, $commonRoles) && array_key_exists($commonRoles[0], $userRoleToColorMap)) {
					$userRoleColor = trim($userRoleToColorMap[$commonRoles[0]]);
					if (strlen($userRoleColor) > 0) {
						$textColor = $userRoleColor;
					}
				}
			}
		}

		return $textColor;
	}

	/**
	 * @param WiseChatUser $user
	 * @param integer $priorityWordPressId
	 *
	 * @return string|null
	 */
	public function getUserAvatar($user, $priorityWordPressId = null) {
		$imageSrc = null;
		if ($priorityWordPressId > 0 || ($user !== null && $user->getWordPressId() !== null)) {
			$imageTag = $priorityWordPressId > 0 ? get_avatar($priorityWordPressId) : get_avatar($user->getWordPressId());

			$doc = new DOMDocument();
			@$doc->loadHTML($imageTag);
			$imageTags = $doc->getElementsByTagName('img');
			foreach($imageTags as $tag) {
				$imageSrc = $tag->getAttribute('src');
			}
		} else {
			$imageSrc = $this->options->getIconsURL().'user.png';
		}

		return $imageSrc;
	}

	/**
	 * Returns CSS classes declared to user roles.
	 *
	 * @param WiseChatUser $user
	 *
	 * @return string
	 */
	public function getCssClassesForUserRoles($user, $wpUser = null) {
		$classes = array();

		if ($user === null) {
			if ($wpUser !== null && is_array($wpUser->roles)) {
				foreach ($wpUser->roles as $role) {
					$classes[] = 'wcUserRole-' . $role;
				}
			} else {
				$classes[] = 'wcUserRoleAnonymous';
			}
		} else {
			if ($user->getWordPressId() > 0) {
				if ($wpUser === null) {
					$wpUser = $this->usersDAO->getWpUserByID($user->getWordPressId());
				}
				if (is_array($wpUser->roles)) {
					foreach ($wpUser->roles as $role) {
						$classes[] = 'wcUserRole-' . $role;
					}
				}
			} else {
				$classes[] = 'wcUserRoleAnonymous';
			}
		}

		return implode(' ', $classes);
	}

	public function getUserTextColor($user) {
		// get text color defined by role:
		$textColor = $this->getTextColorDefinedByUserRole($user);

		// get custom color (higher priority):
		if ($this->options->isOptionEnabled('allow_change_text_color', true) && $user !== null && strlen($user->getDataProperty('textColor')) > 0) {
			$textColor = $user->getDataProperty('textColor');
		}

		return $textColor;
	}

	/**
	 * @param WiseChatUser $user
	 * @param string $userName
	 * @param integer $wordPressUserId
	 *
	 * @return string
	 */
	public function getUserProfileLink($user, $userName = null, $wordPressUserId = null) {
		$linkUserNameTemplate = $this->options->getOption('link_user_name_template', null);
		if ($wordPressUserId == null && $user != null) {
			$wordPressUserId = $user->getWordPressId();
		}
		if ($userName == null && $user != null) {
			$userName = $user->getName();
		}
		$wpUser = $wordPressUserId != null ? $this->usersDAO->getWpUserByID($wordPressUserId) : null;

		$variableId = '';
		$variableUserName = $variableDisplayName = $userName;
		if ($wpUser !== null) {
			$variableId = $wpUser->ID;
			$variableUserName = $wpUser->user_login;
			$variableDisplayName = $this->usersDAO->getChatUserNameFromWpUser($wpUser);
		}

		$profileLink = null;
		if ($linkUserNameTemplate != null) {
			$variables = array(
				'id' => $variableId,
				'username' => $variableUserName,
				'displayname' => $variableDisplayName
			);

			$profileLink = $this->getTemplatedString($variables, $linkUserNameTemplate);
		} else if ($wpUser !== null) {
			$profileLink = get_author_posts_url($wpUser->ID, $this->usersDAO->getChatUserNameFromWpUser($wpUser));
		}

		return $profileLink;
	}

	private function getTemplatedString($variables, $template, $encodeValues = true) {
		foreach ($variables as $key => $value) {
			$template = str_replace("{".$key."}", $encodeValues ? urlencode($value) : $value, $template);
		}

		return $template;
	}

	/**
	 * @param WiseChatMessage $message
	 *
	 * @return string|null
	 */
	public function getUserAvatarFromMessage($message) {
		if (strlen($message->getAvatarUrl()) > 0) {
			return $message->getAvatarUrl();
		} else {
			return $this->getUserAvatar($message->getUser(), $message->getWordPressUserId());
		}
	}

	/**
	 * Determines if the user is in fact an anonymous user.
	 *
	 * @param WiseChatUser $user
	 * @return bool
	 */
	public function isAnonymousUser($user) {
		return !($user->getWordPressId() > 0);
	}

}