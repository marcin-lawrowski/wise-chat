<?php

namespace Kainex\WiseChat\Services;

use Exception;
use Kainex\WiseChat\DAO\MessagesDAO;
use Kainex\WiseChat\DAO\PendingChatsDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\PendingChat;
use Kainex\WiseChat\Model\User;

/**
 * WiseChat pending chats services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class PendingChatsService extends PendingChatsDAO {

	/**
	 * @var MessagesDAO
	 */
	protected $messagesDAO;

	/**
	 * @var PendingChatsDAO
	 */
	private $pendingChatsDAO;

	/**
	 * @param MessagesDAO $messagesDAO
	 * @param PendingChatsDAO $pendingChatsDAO
	 */
	public function __construct(MessagesDAO $messagesDAO, PendingChatsDAO $pendingChatsDAO) {
		$this->messagesDAO = $messagesDAO;
		$this->pendingChatsDAO = $pendingChatsDAO;
	}

	/**
	 * Creates new pending chat for given message and channel.
	 *
	 * @param int $recipientId
	 * @param Message $message
	 * @param Channel $channel
	 * @throws Exception
	 */
	public function addPendingChat(int $recipientId, Message $message, Channel $channel) {
		$pendingChat = new PendingChat();
		$pendingChat->setChannelId($channel->getId());
		$pendingChat->setUserId($message->getUserId());
		$pendingChat->setRecipientId($recipientId);
		$pendingChat->setMessageId($message->getId());
		$pendingChat->setTime(time());
		$pendingChat->setChecked(false);
		$this->pendingChatsDAO->save($pendingChat);
	}

	/**
	 * Sets pending chats as checked.
	 *
	 * @param User $recipient
	 * @param Channel $channel
	 * @throws Exception
	 */
	public function setPendingChatChecked(User $recipient, Channel $channel) {
		$pendingChats = $this->pendingChatsDAO->getAllUnreadByRecipientAndChannel($recipient->getId(), $channel->getId());
		foreach ($pendingChats as $pendingChat) {
			$pendingChat->setChecked(true);
			$this->pendingChatsDAO->save($pendingChat);
		}
	}

}