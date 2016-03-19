<?php

/**
 * WiseChat abstract command.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
abstract class WiseChatAbstractCommand {

	/**
	* @var WiseChatChannel
	*/
	protected $channel;
	
	/**
	* @var string
	*/
	protected $arguments;
	
	/**
	* @var WiseChatMessagesDAO
	*/
	protected $messagesDAO;
	
	/**
	* @var WiseChatUsersDAO
	*/
	protected $usersDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	protected $channelUsersDAO;
	
	/**
	* @var WiseChatBansDAO
	*/
	protected $bansDAO;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	 * @var WiseChatBansService
	 */
	protected $bansService;

	/**
	 * @var WiseChatMessagesService
	 */
	private $messagesService;

	/**
	 * @param WiseChatChannel $channel
	 * @param array $arguments
	 */
	public function __construct($channel, $arguments) {
		$this->messagesDAO = WiseChatContainer::get('dao/WiseChatMessagesDAO');
		$this->bansDAO = WiseChatContainer::get('dao/WiseChatBansDAO');
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->channelUsersDAO = WiseChatContainer::get('dao/WiseChatChannelUsersDAO');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->bansService = WiseChatContainer::get('services/WiseChatBansService');
		$this->messagesService = WiseChatContainer::get('services/WiseChatMessagesService');
		$this->arguments = $arguments;
		$this->channel = $channel;
	}
	
	protected function addMessage($message) {
		$this->messagesService->addMessage($this->authentication->getSystemUser(), $this->channel, $message, true);
	}

    /**
     * Executes command using arguments.
     *
     * @return null
     */
    abstract public function execute();
}