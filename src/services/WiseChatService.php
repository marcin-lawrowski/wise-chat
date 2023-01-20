<?php

/**
 * Wise Chat main services class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatService {

	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var WiseChatChannelsDAO
	*/
	private $channelsDAO;
	
	/**
	* @var WiseChatUserService
	*/
	private $userService;

	/**
	* @var WiseChatKicksService
	*/
	private $kicksService;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	 * @var WiseChatAuthorization
	 */
	private $authorization;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		WiseChatContainer::load('model/WiseChatChannel');
		$this->options = WiseChatOptions::getInstance();
		$this->channelsDAO = WiseChatContainer::get('dao/WiseChatChannelsDAO');
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->channelUsersDAO = WiseChatContainer::get('dao/WiseChatChannelUsersDAO');
		$this->userService = WiseChatContainer::get('services/user/WiseChatUserService');
		$this->kicksService = WiseChatContainer::getLazy('services/WiseChatKicksService');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->authorization = WiseChatContainer::getLazy('services/user/WiseChatAuthorization');
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
	 * @return WiseChatChannel
	 */
	public function createAndGetChannel($channelName) {
		$channel = $this->channelsDAO->getByName($channelName);
		if ($channel === null) {
			$channel = new WiseChatChannel();
			$channel->setName($channelName);
			$this->channelsDAO->save($channel);
		}

		return $channel;
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
	 * Determines whether IP is kicked.
	 *
	 * @return boolean
	 */
	public function isIpKicked() {
		return isset($_SERVER['REMOTE_ADDR']) && $this->kicksService->isIpAddressKicked($_SERVER['REMOTE_ADDR']);
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

			$timezone = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone( 'UTC' );
			
			$chatOpeningHours = $this->options->getOption('opening_hours');
			$openingHour = $chatOpeningHours['opening'];
			$openingMode = $chatOpeningHours['openingMode'];
			$startHourDate = null;
			if ($openingMode != '24h') {
				$startHourDate = DateTime::createFromFormat('Y-m-d h:i a', date('Y-m-d').' '.$openingHour.' '.$openingMode, $timezone);
			} else {
				$startHourDate = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d').' '.$openingHour, $timezone);
			}
			
			$closingHour = $chatOpeningHours['closing'];
			$closingMode = $chatOpeningHours['closingMode'];
			$endHourDate = null;
			if ($closingMode != '24h') {
				$endHourDate = DateTime::createFromFormat('Y-m-d h:i a', date('Y-m-d').' '.$closingHour.' '.$closingMode, $timezone);
			} else {
				$endHourDate = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d').' '.$closingHour, $timezone);
			}
			
			if ($startHourDate != null && $endHourDate != null) {
				$nowDate = new DateTime('now', $timezone);
				
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
		return $this->options->isOptionEnabled('force_user_name_selection') && !$this->authentication->isAuthenticated();
	}
}