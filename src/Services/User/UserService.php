<?php

namespace Kainex\WiseChat\Services\User;

use Exception;
use Kainex\WiseChat\DAO\Channels\UserChannelsDAO;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\Model\Channel\ChannelUser;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Options;

/**
 * WiseChat user services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserService extends UsersDAO {
	const USERS_ACTIVITY_TIME_FRAME = 30;
	const USERS_PRESENCE_TIME_FRAME = 86400;

	/**
	 * @var ClientSide
	 */
	private $clientSide;
	
	/**
	* @var ActionsService
	*/
	private $actions;
	
	/**
	* @var ChannelUsersDAO
	*/
	private $channelUsersDAO;

	/**
	 * @var AuthenticationService
	 */
	private $authentication;

	/**
	 * @var UserEventsService
	 */
	private $userEvents;
	
	/**
	* @var Options
	*/
	private $options;

	private UserChannelsDAO $userChannelsDAO;

	/**
	 * @param ClientSide $clientSide
	 * @param ActionsService $actions
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param AuthenticationService $authentication
	 * @param UserEventsService $userEvents
	 * @param Options $options
	 * @param UserChannelsDAO $userChannelsDAO
	 */
	public function __construct(ClientSide $clientSide, ActionsService $actions, ChannelUsersDAO $channelUsersDAO, AuthenticationService $authentication, UserEventsService $userEvents, Options $options, UserChannelsDAO $userChannelsDAO) {
		parent::__construct($options);
		
		$this->clientSide = $clientSide;
		$this->actions = $actions;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->authentication = $authentication;
		$this->userEvents = $userEvents;
		$this->options = $options;
		$this->userChannelsDAO = $userChannelsDAO;
	}

	/**
	 * Auto-authenticate user if no additional steps need to be taken (no external auth, no username forcing, etc.)
	 *
	 * @throws Exception
	 */
	public function autoAuthenticateOnMaintenance() {
		if ($this->authentication->isAuthenticated()) {
			return;
		}
		$user = null;

		// check if there is a WordPress user logged in:
		$currentWPUser = $this->getCurrentWpUser();
		if ($currentWPUser !== null) {
			$user = $this->authentication->authenticateWithWpUser($currentWPUser);
		}

		// authenticate only if anonymous login is not prohibited:
		if ($user === null && $this->options->getIntegerOption('access_mode', 0) != 1) {
			$user = $this->authentication->authenticateAnonymously();
		}

		if ($user !== null) {
			/**
			 * Fires once user has started its session in the chat.
			 *
			 * @param User $user The user object
			 *@since 2.3.2
			 *
			 */
			do_action("wc_user_session_started", $user);

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
		if ($this->isWpUserLogged()) {
			$targetRoles = (array) $this->options->getOption("read_only_for_roles", array());
			if (count($targetRoles) > 0) {
				$wpUser = $this->getCurrentWpUser();

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
		$currentWPUser = $this->getCurrentWpUser();

		if (!$this->authentication->isAuthenticated()) {
			if ($currentWPUser !== null) {
				$user = $this->authentication->authenticateWithWpUser($currentWPUser);

				/**
				 * Fires once user has started its session in the chat.
				 *
				 * @param User $user The user object
				 *@since 2.3.2
				 *
				 */
				do_action("wc_user_session_started", $user);
			}
		} else {
			if ($this->authentication->isAuthenticatedExternally()) {
				return;
			}

			$wasAuthenticated = false;
			$user = $this->authentication->getUser();

			// anonymous switched to WP:
			if ($user->getWordPressId() === null && $currentWPUser !== null) {
				// forget the anonymous account:
				$this->authentication->dropAuthentication();

				$user = $this->authentication->authenticateWithWpUser($currentWPUser);
				$wasAuthenticated = true;
			}

			// WP switched to anonymous:
			if ($user->getWordPressId() !== null && $currentWPUser === null) {
				$this->authentication->dropAuthentication();
				$user = $this->authentication->authenticateAnonymously();
				$wasAuthenticated = true;
			}

			if ($wasAuthenticated) {
				/**
				 * Fires once user has started its session in the chat.
				 *
				 * @param User $user The user object
				 *@since 2.3.2
				 *
				 */
				do_action("wc_user_session_started", $user);
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
			$this->getCurrentWpUser() !== null ||
			$this->authentication->isAuthenticatedExternally() ||
			!$this->authentication->isAuthenticated()
		) {
			throw new Exception('Unsupported operation');
		}

		$userName = $this->authentication->validateUserName($userName);
		$user = $this->authentication->getUser();
		$oldName = $user->getName();

		// set new username and refresh it:
		$user->setName($userName);
		$this->save($user);
		$this->refreshUserName($user);

		/**
		 * Fires once user has changed its name. It applies to anonymous users only.
		 *
		 * @param string $oldName The old name
		 * @param string $userName The new name
		 * @param User $user The user object
		 *@since 2.3.2
		 *
		 */
		do_action("wc_username_changed", $oldName, $userName, $user);

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
		$this->save($user);
		$this->userEvents->resetEventTracker('usersList');
		$this->actions->publishAction(
			'setMessagesProperty', array(
				'chatUserId' => $user->getId(),
				'propertyName' => 'textColor',
				'propertyValue' => $color
			)
		);

		/**
		 * Fires once user has set its color.
		 *
		 * @param string $color The color code
		 * @param User $user The user object
		 *@since 2.3.2
		 *
		 */
		do_action("wc_usercolor_set", $color, $user);
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

		$value = $this->authentication->getUser()->getDataProperty($property);

		/**
		 * Filters user property
		 *
		 * @since 2.4
		 *
		 * @param string $value Property value
		 * @param string $property Property name
		 */
		return apply_filters('wc_userproperty_get', $value, $property);
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
		$this->save($user);

		/**
		 * Fires once user property has been set.
		 *
		 * @param string $property Property name
		 * @param mixed $value Property value
		 * @param User $user The user object
		 *@since 2.3.2
		 *
		 */
		do_action("wc_userproperty_set", $property, $value, $user);
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
			$this->save($user);
		}
	}

	/**
	 * Checks if the first given user can communicate with the second user.
	 *
	 * @param User $user
	 * @param User $associatedUser
	 * @return bool
	 */
	public function isUsersConnectionAvailable($user, $associatedUser) {
		return true;
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
	 * Loads data from WP user and updates it in corresponding user.
	 *
	 * @param integer $wpUserId
	 * @throws Exception
	 */
	public function refreshUserBasedOnWordPressUser(int $wpUserId) {
		$wpUser = $this->getWpUserByID($wpUserId);
		$user = $this->getLatestByWordPressId($wpUserId);
		if ($wpUser !== null && $user !== null) {
			$userName = $this->getChatUserNameFromWpUser($wpUser);
			$avatarURL = $this->getAvatarOfWordPressUser($wpUser);
			$user->setName($userName);
			$user->setAvatarUrl($avatarURL);
			$this->save($user);
		}
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function refreshUser() {
		$user = $this->authentication->getUser();
		if ($user !== null && $user->getWordPressId() > 0) {
			$this->refreshUserBasedOnWordPressUser($user->getWordPressId());
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
				$channelUser = new ChannelUser();
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
	 * @param User $user
	 * @throws Exception
	 */
	private function refreshUserName($user) {
		$this->userEvents->resetEventTracker('browser');

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
	 * @param User $user
	 * @return string|null
	 */
	public function getTextColorDefinedByUserRole($user) {
		$textColor = null;
		$userRoleToColorMap = (array) $this->options->getOption('text_color_user_roles', array());

		if ($user !== null && $user->getWordPressId() > 0) {
			$wpUser = $this->getWpUserByID($user->getWordPressId());
			if ($wpUser !== null && is_array($wpUser->roles)) {
				$commonRoles = array_intersect($wpUser->roles, array_keys($userRoleToColorMap));
				if (count($commonRoles) > 0 && array_key_exists(0, $commonRoles) && array_key_exists($commonRoles[0], $userRoleToColorMap)) {
					$userRoleColor = trim($userRoleToColorMap[$commonRoles[0]]);
					if ($userRoleColor) {
						$textColor = $userRoleColor;
					}
				}
			}
		}

		return $textColor;
	}

	/**
	 * @param User $user
	 *
	 * @return string
	 */
	public function getUserAvatar(User $user): ?string {
		if ($user->getAvatarUrl()) {
			return $user->getAvatarUrl();
		}
		if (!$user->getWordPressId()) {
			return $this->getUserDefaultAvatar();
		}
		$wpUser = $this->getWpUserByID($user->getWordPressId());
		if (!$wpUser) {
			return $this->getUserDefaultAvatar();
		}

		return $this->getAvatarOfWordPressUser($wpUser);
	}

	/**
	 * Returns URL of WordPress user avatar.
	 * Default avatar is returned.
	 *
	 * @param \WP_User $wpUser
	 * @return string
	 */
	public function getAvatarOfWordPressUser(\WP_User $wpUser): string {
		$imageTag = get_avatar($wpUser->ID); // ID is better than the object
		if (!$imageTag) {
			return $this->getUserDefaultAvatar();
		}
		$imageSrc = null;
		$doc = new \DOMDocument();
		@$doc->loadHTML($imageTag);
		$imageTags = $doc->getElementsByTagName('img');
		foreach($imageTags as $tag) {
			$imageSrc = $tag->getAttribute('src');
		}

		return strlen($imageSrc) > 0 ? $imageSrc : $this->getUserDefaultAvatar();
	}

	public function getUserDefaultAvatar(): string {
		return $this->options->getIconsURL().'user.png';
	}

	/**
	 * Returns CSS classes declared to user roles.
	 *
	 * @param User $user
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
					$wpUser = $this->getWpUserByID($user->getWordPressId());
				}
				if ($wpUser !== null && is_array($wpUser->roles)) {
					foreach ($wpUser->roles as $role) {
						$classes[] = 'wcUserRole-' . $role;
					}
				}
			} else if ($user->getExternalType()) {
				$classes[] = 'wcUserRoleExternal-' . $user->getExternalType();
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
		if ($this->options->isOptionEnabled('allow_change_text_color') && $user !== null && $user->getDataProperty('textColor')) {
			$textColor = $user->getDataProperty('textColor');
		}

		return $textColor;
	}

	/**
	 * @param User $user
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
		$wpUser = $wordPressUserId != null ? $this->getWpUserByID($wordPressUserId) : null;

		$variableId = '';
		$variableUserName = $variableDisplayName = $userName;
		if ($user !== null && $user->getExternalType()) {
			$variableId = $user->getExternalId();
		} else if ($wpUser !== null) {
			$variableId = $wpUser->ID;
			$variableUserName = $wpUser->user_login;
			$variableDisplayName = $this->getChatUserNameFromWpUser($wpUser);
		}

		$profileLink = null;
		if ($linkUserNameTemplate != null && $wpUser) {
			$variables = array(
				'id' => $variableId,
				'username' => $variableUserName,
				'displayname' => $variableDisplayName
			);

			$profileLink = $this->getTemplatedString($variables, $linkUserNameTemplate);
		} else if ($user !== null && $user->getExternalType()) {
			$profileLink = $user->getProfileUrl();
		} else if ($wpUser !== null) {
			$profileLink = get_author_posts_url($wpUser->ID, $this->getChatUserNameFromWpUser($wpUser));
		}

		return $profileLink;
	}

	/**
	 * Creates chat user based on given WordPress user ID.
	 *
	 * @param $wordPressUserId
	 * @return null|User
	 * @throws Exception
	 */
	public function createOrGetBasedOnWordPressUserId($wordPressUserId) {
		$chatUser = $this->getLatestByWordPressId($wordPressUserId);
		if ($chatUser !== null) {
			return $chatUser;
		}

		$wordPressUser = $this->getWpUserByID($wordPressUserId);
		if ($wordPressUser !== null) {
			$chatUser = new User();
			$chatUser->setName($this->getChatUserNameFromWpUser($wordPressUser));
			$chatUser->setWordPressId($wordPressUser->ID);
			$chatUser->setSessionId(wp_generate_password());
			$chatUser->setAvatarUrl($this->getAvatarOfWordPressUser($wordPressUser));

			return $this->save($chatUser);
		}

		return null;
	}

	private function getTemplatedString($variables, $template, $encodeValues = true) {
		foreach ($variables as $key => $value) {
			$template = str_replace("{".$key."}", $encodeValues ? urlencode($value) : $value, $template);
		}

		return $template;
	}

	/**
	 * Checks if the current user can get the message content.
	 *
	 * @param Message $message
	 *
	 * @return boolean
	 */
	public function isUserAllowedToSeeTheContentOfMessage($message) {
		return true;
	}

	/**
	 * Determines if the user is in fact an anonymous user.
	 *
	 * @param User $user
	 * @return bool
	 */
	public function isAnonymousUser($user) {
		return !($user->getWordPressId() > 0) && !$user->getExternalType();
	}

	/**
	 * @param Message[] $messages
	 * @return void
	 */
	public function cacheUsersOfMessages(array $messages) {
		foreach (array_chunk($messages, 200) as $messagesChunk) {
			$wpUserIds = [];
			/** @var Message[] $messagesChunk */
			foreach ($messagesChunk as $message) {
				if ($message->getWordPressUserId()) {
					$wpUserIds[] = $message->getWordPressUserId();
				}
			}

			if (!empty($wpUserIds)) {
				$this->cacheWPUsers(['include' => array_unique($wpUserIds)]);
			}
		}
	}

	public function deleteAllData(): void {
		$this->userChannelsDAO->deleteAll();
		$this->deleteAll();
	}

}