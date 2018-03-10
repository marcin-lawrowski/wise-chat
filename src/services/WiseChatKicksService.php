<?php

/**
 * WiseChat kicks services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatKicksService {

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	 * @var WiseChatKicksDAO
	 */
	private $kicksDAO;

	/**
	 * @var WiseChatUsersDAO
	 */
	private $usersDAO;

	/**
	 * @var WiseChatMessagesDAO
	 */
	private $messagesDAO;

	/**
	 * @var WiseChatOptions
	 */
	private $options;

	public function __construct() {
		WiseChatContainer::load('model/WiseChatKick');
		$this->options = WiseChatOptions::getInstance();
		$this->kicksDAO = WiseChatContainer::getLazy('dao/WiseChatKicksDAO');
		$this->messagesDAO = WiseChatContainer::getLazy('dao/WiseChatMessagesDAO');
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
		$this->actions = WiseChatContainer::getLazy('services/user/WiseChatActions');
	}

	/**
	 * Kicks the user by message ID.
	 *
	 * @param integer $messageId
	 *
	 * @throws Exception If the message or user was not found
	 */
	public function kickByMessageId($messageId) {
		$message = $this->messagesDAO->get($messageId);
		if ($message === null) {
			throw new Exception('Message was not found');
		}

		$user = $this->usersDAO->get($message->getUserId());
		if ($user !== null) {
			$this->kickIpAddress($user->getIp(), $user->getName());
			$this->actions->publishAction('reload', array(), $user);

			return;
		}

		throw new Exception('User was not found');
	}

	/**
	 * Creates and saves a new kick on IP address if the IP was not kicked previously.
	 *
	 * @param string $ip Given IP address
	 * @param string $userName
	 *
	 * @return boolean Returns true the kick was created
	 */
	public function kickIpAddress($ip, $userName) {
		if ($this->kicksDAO->getByIp($ip) === null) {
			$kick = new WiseChatKick();
			$kick->setCreated(time());
			$kick->setLastUserName($userName);
			$kick->setIp($ip);
			$this->kicksDAO->save($kick);

			return true;
		}

		return false;
	}

	/**
	 * Checks if given IP address is kicked,
	 *
	 * @param string $ip
	 * @return bool
	 */
	public function isIpAddressKicked($ip) {
		return $this->kicksDAO->getByIp($ip) !== null;
	}

}