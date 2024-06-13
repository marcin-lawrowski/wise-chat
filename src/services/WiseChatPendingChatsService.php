<?php

/**
 * WiseChat pending chats services.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatPendingChatsService {

	/**
	 * @var WiseChatMessagesDAO
	 */
	protected $messagesDAO;

	/**
	 * @var WiseChatPendingChatsDAO
	 */
	private $pendingChatsDAO;

	/**
	 * @var WiseChatRenderer
	 */
	private $renderer;

	/**
	 * @var WiseChatUsersDAO
	 */
	private $usersDAO;

	/**
	 * @var WiseChatOptions
	 */
	private $options;

	public function __construct() {
		WiseChatContainer::load('model/WiseChatPendingChat');
		$this->options = WiseChatOptions::getInstance();
		$this->messagesDAO = WiseChatContainer::get('dao/WiseChatMessagesDAO');
		$this->pendingChatsDAO = WiseChatContainer::getLazy('dao/WiseChatPendingChatsDAO');
		$this->renderer = WiseChatContainer::get('rendering/WiseChatRenderer');
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
	}

	/**
	 * Creates new pending chat for given message and channel.
	 *
	 * @param WiseChatMessage $message
	 * @param WiseChatChannel $channel
	 */
	public function addPendingChat($message, $channel) {
		$pendingChat = new WiseChatPendingChat();
		$pendingChat->setChannelId($channel->getId());
		$pendingChat->setUserId($message->getUserId());
		$pendingChat->setRecipientId($message->getRecipientId());
		$pendingChat->setMessageId($message->getId());
		$pendingChat->setTime(time());
		$pendingChat->setChecked(false);
		$this->pendingChatsDAO->save($pendingChat);
	}

	/**
	 * Sets pending chats as checked.
	 *
	 * @param integer $userId Sender encrypted ID
	 * @param WiseChatUser $recipient
	 * @param integer $channelId
	 * @throws Exception
	 */
	public function setPendingChatChecked($userId, $recipient, $channelId) {
		$pendingChats = $this->pendingChatsDAO->getAllUnreadByUserRecipientAndChannel($userId, $recipient->getId(), $channelId);
		foreach ($pendingChats as $pendingChat) {
			$pendingChat->setChecked(true);
			$this->pendingChatsDAO->save($pendingChat);
		}
	}

	/**
	 * Returns last unread unread messages for each user.
	 *
	 * @param WiseChatUser|null $user
	 * @param WiseChatChannel $channel
	 *
	 * @return WiseChatMessage[]
	 */
	public function getUnreadMessages($user, $channel) {
		if ($user === null) {
			return array();
		}

		$messages = array();
		$unreadMessages = $this->pendingChatsDAO->getAllUnreadDirectMessages($user->getId(), $channel->getId());
		foreach ($unreadMessages as $unreadMessage) {
			if (array_key_exists($unreadMessage->getUserId(), $messages)) {
				continue;
			}
			$messages[$unreadMessage->getUserId()] = $unreadMessage;
		}

		return array_values($messages);
	}
}