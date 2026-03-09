<?php

namespace Kainex\WiseChat\DAO\Message;

use Exception;
use Kainex\WiseChat\DAO\AbstractDAO;
use Kainex\WiseChat\Model\Message\MessageReactionLog;
use Kainex\WiseChat\Installer;
use Kainex\WiseChat\Model\User;

/**
 * Wise Chat message reaction log DAO.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MessageReactionLogDAO extends AbstractDAO {

	protected function getTableName(): string {
		return Installer::getReactionsLogTable();
	}

	/**
	 * @param MessageReactionLog $reactionLog
	 *
	 * @return MessageReactionLog
	 * @throws Exception On validation error
	 */
	public function save(MessageReactionLog $reactionLog): MessageReactionLog {
		$reactionLog->setId($this->persist([
			'id' => $reactionLog->getId(),
			'time' => $reactionLog->getTime(),
			'message_id' => $reactionLog->getMessageId(),
			'user_id' => $reactionLog->getUserId(),
			'reaction_id' => $reactionLog->getReactionId(),
		]));

		return $reactionLog;
	}

	/**
	 * Returns reaction logs by message ID.
	 *
	 * @param integer $id
	 *
	 * @return MessageReactionLog[]
	 */
	public function getAllByMessageId(int $id): array {
		global $wpdb;

		$log = array();
		$sql = $wpdb->prepare(
			'SELECT cm.*, u.name AS joined_user_name, u.wp_id AS joined_user_wp_id, u.data AS joined_user_data, u.avatar_url AS joined_user_avatar_url, u.external_type AS joined_user_external_type
			FROM %i cm 
			LEFT JOIN %i AS u ON (u.id = cm.user_id) 
			WHERE cm.message_id = %d AND u.id IS NOT NULL
			ORDER BY u.name;',
			$this->getTableName(), Installer::getUsersTable(), $id
		);
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$reactionLog = $this->populateData($result);
				$user = new User();
				$user->setId((int) $result->user_id);
				$user->setName($result->joined_user_name);
				$user->setAvatarUrl($result->joined_user_avatar_url);
				$user->setExternalType($result->joined_user_external_type);
				if ($result->joined_user_wp_id) {
					$user->setWordPressId((int) $result->joined_user_wp_id);
				}
				$user->setData($result->joined_user_data ? json_decode($result->joined_user_data, true) : []);
				$reactionLog->setUser($user);

				$log[] = $reactionLog;
			}
		}

		return $log;
	}

	/**
	 * Returns reaction logs by message ID and user ID.
	 *
	 * @param integer $messageId
	 * @param integer $userId
	 * @return MessageReactionLog[]
	 */
	public function getAllByMessageIdAndUserId($messageId, $userId) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE message_id = %d AND user_id = %d ORDER BY time ASC;', $this->getTableName(), intval($messageId), intval($userId));
		$results = $wpdb->get_results($sql);
		$logs = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$logs[] = $this->populateData($result);
			}
		}

		return $logs;
	}

	/**
	 * Returns reaction logs by message IDs and user ID.
	 *
	 * @param integer[] $messageIds
	 * @param integer $userId
	 * @return MessageReactionLog[]
	 */
	public function getAllByMessageIdsAndUserId($messageIds, $userId) {
		if (count($messageIds) === 0) {
			return array();
		}

		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE message_id IN (%s) AND user_id = %d ORDER BY time ASC;', $this->getTableName(), implode(', ', $messageIds), intval($userId));
		$results = $wpdb->get_results($sql);
		$logs = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$logs[] = $this->populateData($result);
			}
		}

		return $logs;
	}

	/**
	 * Returns reaction logs by message ID and user ID and reaction ID.
	 *
	 * @param integer $messageId
	 * @param integer $userId
	 * @param integer$reactionId
	 * @return MessageReactionLog[]
	 */
	public function getAllByMessageIdAndUserIdAndReactionId($messageId, $userId, $reactionId) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE message_id = %d AND user_id = %d AND reaction_id = %d ORDER BY time ASC;', $this->getTableName(), intval($messageId), intval($userId), intval($reactionId));
		$results = $wpdb->get_results($sql);
		$logs = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$logs[] = $this->populateData($result);
			}
		}

		return $logs;
	}

	/**
	 * Deletes reaction logs by message ID and user ID and reaction ID.
	 *
	 * @param integer $messageId
	 * @param integer $userId
	 * @param integer$reactionId
	 */
	public function deleteAllByMessageIdAndUserIdAndReactionId($messageId, $userId, $reactionId) {
		global $wpdb;

		$sql = sprintf('DELETE FROM %s WHERE message_id = %d AND user_id = %d AND reaction_id = %d;', $this->getTableName(), intval($messageId), intval($userId), intval($reactionId));
		$wpdb->get_results($sql);
	}

	/**
	 * Removes all reactions logs.
	 */
	public function deleteAll() {
		global $wpdb;

        $wpdb->get_results(sprintf("DELETE FROM %s;", $this->getTableName()));
	}

	/**
	 * Converts raw object into WiseChatMessageReactionLog object.
	 *
	 * @param \stdClass $rawData
	 *
	 * @return MessageReactionLog
	 */
	protected function populateData(\stdClass $rawData): object {
		$reaction = new MessageReactionLog();
		if ($rawData->id > 0) {
			$reaction->setId(intval($rawData->id));
		}
		$reaction->setTime($rawData->time);
		$reaction->setMessageId($rawData->message_id);
		$reaction->setUserId($rawData->user_id);
		$reaction->setReactionId(intval($rawData->reaction_id));

		return $reaction;
	}
}