<?php

/**
 * Class for loading recent chats.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMaintenanceRecentChats {

	/**
	 * @var WiseChatMessagesDAO
	 */
	protected $messagesDAO;

	/**
	 * @var WiseChatChannelsDAO
	 */
	protected $channelsDAO;

	/**
	 * @var WiseChatChannelUsersDAO
	 */
	protected $channelUsersDAO;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	 * @var WiseChatRenderer
	 */
	protected $renderer;

	/**
	 * @var WiseChatMessagesService
	 */
	protected $messagesService;

	/**
	 * @var WiseChatUserService
	 */
	protected $userService;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	 * @var WiseChatAuthorization
	 */
	protected $authorization;

	/**
	 * @var WiseChatPendingChatsService
	 */
	protected $pendingChatsService;

	/**
	 * @var WiseChatUITemplates
	 */
	protected $uiTemplates;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->channelsDAO = WiseChatContainer::getLazy('dao/WiseChatChannelsDAO');
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
		$this->pendingChatsService = WiseChatContainer::getLazy('services/WiseChatPendingChatsService');
		$this->messagesDAO = WiseChatContainer::get('dao/WiseChatMessagesDAO');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->userService = WiseChatContainer::getLazy('services/user/WiseChatUserService');
		$this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
		$this->uiTemplates = WiseChatContainer::getLazy('rendering/WiseChatUITemplates');

		WiseChatContainer::load('services/WiseChatChannelsService');
	}

	/**
	 * @return array
	 */
	public function getRecentChats() {
		$pmChannel = $this->channelsDAO->getByName(WiseChatChannelsService::PRIVATE_MESSAGES_CHANNEL);
		if (!$pmChannel) {
			return array();
		}

		$recentChats = array();
		$duplicationCheck = array();
		$unread = $this->pendingChatsService->getUnreadMessages($this->authentication->getUser(), $pmChannel);
		foreach ($unread as $message) {
			$recentChat = $this->convertMessageToRecentChat($message, false);
			if ($recentChat !== null) {
				$recentChats[] = $recentChat;
				$duplicationCheck[$message->getUserId()] = 1;
			}
		}

		$read = $this->messagesDAO->getAllNewestDirectMessages($this->authentication->getUser(), $pmChannel, $this->options->getIntegerOption('recent_chats_limit', 20));
		foreach ($read as $message) {
			if (array_key_exists($message->getUserId(), $duplicationCheck) || array_key_exists($message->getRecipientId(), $duplicationCheck)) {
				continue;
			}

			$recentChat = $this->convertMessageToRecentChat($message, true);
			if ($recentChat !== null) {
				$recentChats[] = $recentChat;
			}
		}

		return $recentChats;
	}

	/**
	 * @param WiseChatMessage $message
	 * @param boolean $read
	 * @return array|null
	 */
	private function convertMessageToRecentChat($message, $read) {
		$directUserId = $this->authentication->getUserIdOrNull() === $message->getRecipientId() ? $message->getUserId() : $message->getRecipientId();

		$directUser = $this->usersDAO->get($directUserId);
		if ($directUser === null) {
			return null;
		}

		$isAllowed = $this->options->isOptionEnabled('enable_private_messages', false) || !$this->options->isOptionEnabled('users_list_linking', false);

		return array(
			'id' => WiseChatCrypt::encryptToString($message->getId()),
			'channel' => array(
				'id' => WiseChatCrypt::encryptToString('d|'.$directUserId),
				'name' => $directUser->getName(),
				'type' => 'direct',
				'readOnly' => !$isAllowed,
				'avatar' => $this->options->isOptionEnabled('show_users_list_avatars', false) ? $this->userService->getUserAvatar($directUser) : null,
				'online' => $this->channelUsersDAO->isOnline($directUserId),
				'intro' => $this->uiTemplates->getDirectChannelIntro($directUser)
			),
			'text' => $message->getText(),
			'timeUTC' => gmdate('c', $message->getTime()),
			'sortKey' => $message->getTime().$message->getId(),
			'read' => $read
		);
	}
}