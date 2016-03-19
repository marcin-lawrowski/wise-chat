<?php

/**
 * WiseChat bans services.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatBansService {

	/**
	* @var WiseChatBansDAO
	*/
	private $bansDAO;

	/**
	 * @var WiseChatUsersDAO
	 */
	private $usersDAO;
	
	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		WiseChatContainer::load('model/WiseChatBan');
		$this->options = WiseChatOptions::getInstance();
		$this->bansDAO = WiseChatContainer::getLazy('dao/WiseChatBansDAO');
		$this->messagesDAO = WiseChatContainer::getLazy('dao/WiseChatMessagesDAO');
		$this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
	}
	
	/**
	* Maintenance actions performed at start-up.
	*
	* @return null
	*/
	public function startUpMaintenance() {
		$this->bansDAO->deleteOlder(time());
	}
	
	/**
	* Maintenance actions performed periodically.
	*
	* @return null
	*/
	public function periodicMaintenance() {
		$this->bansDAO->deleteOlder(time());
	}
	
	/**
	* Bans an user by message ID.
	*
	* @param integer $messageId
	* @param WiseChatChannel $channel
	* @param string $durationString
	*
	* @throws Exception If the message or user was not found
	*/
	public function banByMessageId($messageId, $channel, $durationString = '1d') {
		$message = $this->messagesDAO->get($messageId);
		if ($message === null) {
			throw new Exception('Message was not found');
		}
		
		$channelUser = $this->channelUsersDAO->getByUserIdAndChannelId($message->getUserId(), $channel->getId());
		if ($channelUser !== null) {
			$user = $this->usersDAO->get($message->getUserId());
			if ($user !== null) {
				$duration = $this->getDurationFromString($durationString);
				$this->banIpAddress($user->getIp(), $duration);

				return;
			}
		}

		throw new Exception('User was not found in this channel');
	}

	/**
	 * Creates and saves a new ban on IP address if the IP was not banned previously.
	 *
	 * @param string $ip Given IP address
	 * @param integer $duration Duration of the ban (in seconds)
	 *
	 * @return boolean Returns true the ban was created
	 */
	public function banIpAddress($ip, $duration) {
		if ($this->bansDAO->getByIp($ip) === null) {
			$ban = new WiseChatBan();
			$ban->setCreated(time());
			$ban->setTime(time() + $duration);
			$ban->setIp($ip);
			$this->bansDAO->save($ban);

			return true;
		}

		return false;
	}

    /**
     * Checks if given IP address is banned,
     *
     * @param string $ip
     * @return bool
     */
    public function isIpAddressBanned($ip) {
        return $this->bansDAO->getByIp($ip) !== null;
    }

	/**
	 * Converts duration string into amount of seconds.
	 * If the value cannot be determined the default value is returned.
	 *
	 * @param string $durationString Eg. 1h, 2d, 7m
	 * @param integer $defaultValue One hour
	 *
	 * @return integer
	 */
	public function getDurationFromString($durationString, $defaultValue = 3600) {
		$duration = $defaultValue;

		if (strlen($durationString) > 0) {
			if (preg_match('/\d+m/', $durationString)) {
				$duration = intval($durationString) * 60;
			}
			if (preg_match('/\d+h/', $durationString)) {
				$duration = intval($durationString) * 60 * 60;
			}
			if (preg_match('/\d+d/', $durationString)) {
				$duration = intval($durationString) * 60 * 60 * 24;
			}

			if ($duration === 0) {
				$duration = $defaultValue;
			}
		}

		return $duration;
	}
}