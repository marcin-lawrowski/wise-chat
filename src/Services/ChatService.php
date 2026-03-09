<?php

namespace Kainex\WiseChat\Services;

use DateTime;
use Exception;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\ChannelsDAO;
use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Options;

/**
 * Wise Chat main services class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class ChatService {

	/**
	* @var UsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var ChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var ChannelsDAO
	*/
	private $channelsDAO;
	
	/**
	* @var UserService
	*/
	private $userService;

	/**
	* @var UserBansService
	*/
	private $bansService;

	/**
	 * @var AuthenticationService
	 */
	private $authentication;

	/**
	* @var Options
	*/
	private $options;

	/**
	 * @param UsersDAO $usersDAO
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param ChannelsDAO $channelsDAO
	 * @param UserService $userService
	 * @param UserBansService $bansService
	 * @param AuthenticationService $authentication
	 * @param Options $options
	 */
	public function __construct(UsersDAO $usersDAO, ChannelUsersDAO $channelUsersDAO, ChannelsDAO $channelsDAO, UserService $userService, UserBansService $bansService, AuthenticationService $authentication, Options $options) {
		$this->usersDAO = $usersDAO;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->channelsDAO = $channelsDAO;
		$this->userService = $userService;
		$this->bansService = $bansService;
		$this->authentication = $authentication;
		$this->options = $options;
	}

	/**
	 * Validates channel name and returns it.
	 *
	 * @param string $channelName
	 * @return string
	 */
	public function getValidChatChannelName($channelName) {
		return $channelName === null || $channelName === '' ? 'global' : $channelName;
	}

	/**
	 * Creates a channel if it does not exist and returns it.
	 * If channel exists it is just returned.
	 *
	 * @param string $channelName
	 *
	 * @return Channel
	 */
	public function createAndGetChannel($channelName) {
		$channel = $this->channelsDAO->getByName($channelName);
		if ($channel === null) {
			$channel = new Channel();
			$channel->setName($channelName);
			$this->channelsDAO->save($channel);
		}

		return $channel;
	}

	/**
	 * @param string[] $channelNames
	 * @return Channel[]
	 * @throws Exception
	 */
	public function createAndGetChannels($channelNames) {
		$channels = array();

		foreach ($channelNames as $channelName) {
			$channel = $this->channelsDAO->getByName($channelName);
			if ($channel === null) {
				$channel = new Channel();
				$channel->setName($channelName);
				$channel->setType(Channel::TYPE_PUBLIC);
				$this->channelsDAO->save($channel);
			}

			$channels[] = $channel;
		}

		return $channels;
	}
	
	/**
	* Returns unique ID for the plugin.
	* NOTICE: It generates a new ID every time it is called (!)
	*
	* @return string
	*/
	public function getChatID() {
		return 'wc'.md5(uniqid('', true));
	}
	
	/**
	* Determines whether the chat is restricted for anonymous users.
	*
	* @return boolean
	*/
	public function isChatRestrictedForAnonymousUsers() {
		return $this->options->getOption('access_mode') == 1 && !$this->usersDAO->isWpUserLogged();
	}

	/**
	 * Determines whether the chat is restricted for user roles.
	 *
	 * @return boolean
	 */
	public function isChatRestrictedForCurrentUserRole() {
		if ($this->options->getOption('access_mode') == 1 && $this->usersDAO->isWpUserLogged()) {
			$targetRoles = (array) $this->options->getOption('access_roles', null);
			if ($targetRoles === null) {
				return false;
			}
			if (!is_array($targetRoles) || count($targetRoles) == 0) {
				return true;
			}

			$wpUser = $this->usersDAO->getCurrentWpUser();
			if (!is_array($wpUser->roles) || count($wpUser->roles) == 0) {
				return true;
			}

			return count(array_intersect($targetRoles, $wpUser->roles)) == 0;
		} else {
			return false;
		}
	}

	/**
	 * @return boolean
	 */
	public function isChatRestrictedToCurrentUser() {
		$accessUsers = $this->options->getOption('access_users', array());
		if (is_array($accessUsers) && count($accessUsers) > 0) {
			$wpUser = $this->usersDAO->getCurrentWpUser();
			return $wpUser === null || !in_array($wpUser->ID, $accessUsers);
		}

		return false;
	}

	/**
	 * Determines whether user is banned.
	 *
	 * @return boolean
	 */
	public function isBanned() {
		return $this->bansService->isBanned();
	}

	/**
	 * Determines whether the chat is allowed only for logged in WP users.
	 *
	 * @return boolean
	 */
	public function isChatAllowedForWPUsersOnly() {
		return $this->options->getOption('access_mode') == 1;
	}

	/**
	 * Determines whether Facebook, Twitter or Google login is enabled.
	 *
	 * @return bool
	 */
	public function isExternalLoginEnabled() {
		return false;
	}
	
	/**
	* Determines whether the chat is open according to the settings.
	*
	* @return boolean
	*/
	public function isChatOpen() {
		if ($this->options->isOptionEnabled('enable_opening_control', false)) {
			$chatOpeningDays = $this->options->getOption('opening_days');
			if (is_array($chatOpeningDays) && !in_array(date('l'), $chatOpeningDays)) {
				return false;
			}
			
			$chatOpeningHours = $this->options->getOption('opening_hours');
			$openingHour = $chatOpeningHours['opening'];
			$openingMode = $chatOpeningHours['openingMode'];
			$startHourDate = null;
			if ($openingMode != '24h') {
				$startHourDate = DateTime::createFromFormat('Y-m-d h:i a', date('Y-m-d').' '.$openingHour.' '.$openingMode);
			} else {
				$startHourDate = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d').' '.$openingHour);
			}
			
			$closingHour = $chatOpeningHours['closing'];
			$closingMode = $chatOpeningHours['closingMode'];
			$endHourDate = null;
			if ($closingMode != '24h') {
				$endHourDate = DateTime::createFromFormat('Y-m-d h:i a', date('Y-m-d').' '.$closingHour.' '.$closingMode);
			} else {
				$endHourDate = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d').' '.$closingHour);
			}
			
			if ($startHourDate != null && $endHourDate != null) {
				$nowDate = new DateTime();
				
				$nowU = $nowDate->format('U');
				$startHourDateU = $startHourDate->format('U');
				$endHourDateU = $endHourDate->format('U');
				
				if ($startHourDateU <= $endHourDateU) {
					if ($nowU < $startHourDateU || $nowU > $endHourDateU) {
						return false;
					}
				} else {
					if ($nowU > $endHourDateU && $nowU < $startHourDateU) {
						return false;
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	* Determines if the chat is full according to the users limit.
	*
    * TODO: do not send messages if chat is full
	*
	* @return boolean
	*/
	public function isChatFull() {
		$limit = $this->options->getIntegerOption('channel_users_limit', 0);
		if ($limit > 0) {
			$this->userService->setInactiveUsersOfflineStatus();
			$amountOfCurrentUsers = $this->channelUsersDAO->countOnlineUsers();
			$user = $this->authentication->getUser();
			
			if ($user === null || !$this->channelUsersDAO->isOnline($user->getId())) {
				$amountOfCurrentUsers++;
			}
			
			if ($amountOfCurrentUsers > $limit) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Determines if the current user has to enter his/her name.
	 *
	 * @return bool
	 */
	public function hasUserToBeForcedToEnterName() {
		return $this->options->getOption('auth_mode', 'auto') === 'username' && !$this->authentication->isAuthenticated();
	}

	/**
	 * Determines if the current user has to be authorized externally.
	 *
	 * @return bool
	 */
	public function hasUserToBeAuthenticatedExternally() {
		return $this->isExternalLoginEnabled() && !$this->authentication->isAuthenticated();
	}

}