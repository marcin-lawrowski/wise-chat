<?php

namespace Kainex\WiseChat\DAO;

use Kainex\WiseChat\Model\PendingChat;
use Kainex\WiseChat\Installer;

/**
 * Wise Chat pending chats DAO.
 *
 * @author Kainex <contact@kaine.pl>
 */
class PendingChatsDAO extends AbstractDAO {

	protected function getTableName(): string {
		return Installer::getPendingChatsTable();
	}

	/**
	 * Creates or updates a pending chat.
	 *
	 * @param PendingChat $pendingChat
	 *
	 * @return PendingChat
	 * @throws \Exception On validation error
	 */
	public function save(PendingChat $pendingChat): PendingChat {
		global $wpdb;

		// low-level validation:
		if ($pendingChat->getUserId() === null) {
			throw new \Exception('User ID is required');
		}
		if ($pendingChat->getRecipientId() === null) {
			throw new \Exception('Recipient ID is required');
		}
		if ($pendingChat->getMessageId() === null) {
			throw new \Exception('Message ID is required');
		}
		if ($pendingChat->getChannelId() === null) {
			throw new \Exception('Channel ID is required');
		}

		$pendingChat->setId($this->persist([
			'id' => $pendingChat->getId(),
			'channel_id' => $pendingChat->getChannelId(),
			'user_id' => $pendingChat->getUserId(),
			'recipient_id' => $pendingChat->getRecipientId(),
			'message_id' => $pendingChat->getMessageId(),
			'checked' => $pendingChat->isChecked() === true ? '1' : '0',
			'time' => $pendingChat->getTime()
		]));

		return $pendingChat;
	}

	/**
	 * Returns unchecked pending chats for sender, recipient and channel.
	 *
	 * @param integer $recipientId
	 * @param integer $channelId
	 *
	 * @return PendingChat[]
	 */
	public function getAllUnreadByRecipientAndChannel(int $recipientId, int $channelId): array {
		$raw = $this->getAllBy(['checked' => ['0', '%s'], 'channel_id' => [$channelId, '%d'], 'recipient_id' => [$recipientId, '%d']], ['id', 'desc']);

		return array_map([$this, 'populateData'], $raw);
	}

	/**
	 * Converts stdClass object into WiseChatPendingChat object.
	 *
	 * @param \stdClass $rawRow
	 *
	 * @return PendingChat
	 */
	protected function populateData(\stdClass $rawRow): object {
		$pendingChat = new PendingChat();
		if ($rawRow->id > 0) {
			$pendingChat->setId(intval($rawRow->id));
		}
		if ($rawRow->user_id > 0) {
			$pendingChat->setUserId(intval($rawRow->user_id));
		}
		if ($rawRow->recipient_id > 0) {
			$pendingChat->setRecipientId(intval($rawRow->recipient_id));
		}
		if ($rawRow->channel_id > 0) {
			$pendingChat->setChannelId(intval($rawRow->channel_id));
		}
		if ($rawRow->message_id > 0) {
			$pendingChat->setMessageId(intval($rawRow->message_id));
		}
		$pendingChat->setChecked($rawRow->checked == '1');
		if ($rawRow->time > 0) {
			$pendingChat->setTime(intval($rawRow->time));
		}

		return $pendingChat;
	}

}