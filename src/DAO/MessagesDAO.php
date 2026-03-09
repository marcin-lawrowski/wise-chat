<?php

namespace Kainex\WiseChat\DAO;

use Kainex\WiseChat\DAO\Criteria\MessagesCriteria;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Installer;

/**
 * WiseChat messages DAO
 *
 * @author Kainex <contact@kaine.pl>
 */
class MessagesDAO extends AbstractDAO {

	private ChannelsDAO $channelsDAO;
	private UsersDAO $usersDAO;

	/**
	 * @param ChannelsDAO $channelsDAO
	 * @param UsersDAO $usersDAO
	 */
	public function __construct(ChannelsDAO $channelsDAO, UsersDAO $usersDAO) {
		$this->channelsDAO = $channelsDAO;
		$this->usersDAO = $usersDAO;
	}

	protected function getTableName(): string {
		return Installer::getMessagesTable();
	}

	/**
	 * Creates or updates the message and returns it.
	 *
	 * @param Message $message
	 *
	 * @return Message
	 * @throws \Exception On validation error
	 */
	public function save(Message $message): Message {
		// low-level validation:
		if ($message->getTime() === null) {
			throw new \Exception('Time cannot be null');
		}
		if ($message->getUserId() === null) {
			throw new \Exception('User ID cannot be null');
		}
		if ($message->getText() === null) {
			throw new \Exception('Text cannot be null');
		}
		if ($message->getChannelId() === null) {
			throw new \Exception('Channel name cannot be null');
		}

		$message->setId($this->persist([
			'id' => $message->getId(),
			'time' => $message->getTime(),
			'admin' => $message->isAdmin() ? 1 : 0,
			'hidden' => $message->isHidden() ? 1 : 0,
			'user_id' => $message->getWordPressUserId(),
			'chat_user_id' => $message->getUserId(),
			'text' => $message->getText(),
			'channel_id' => $message->getChannelId(),
			'reply_to_message_id' => $message->getReplyToMessageId()
		]));

		return $message;
	}

	/**
	 * Returns a message by ID.
	 *
	 * @param integer $id
	 *
	 * @return Message|null
	 */
	public function get($id) {
		$raw = $this->getOneBy(['id' => [$id, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns messages by IDs.
	 *
	 * @param integer[] $ids
	 *
	 * @return Message[]
	 */
	public function getAllById(array $ids): array {
		if (empty($ids)) {
			return [];
		}

		return array_map([$this, 'populateData'], $this->getAllBy(['id' => [$ids, '%d']]));
	}

	/**
	 * Returns all messages that follow the criteria.
	 *
	 * @param MessagesCriteria $criteria
	 *
	 * @return Message[]
	 */
	public function getAllByCriteria($criteria) {
		global $wpdb;

		$conditions = $this->getSQLConditionsByCriteria($criteria);
		$sql = sprintf("SELECT * FROM %s WHERE %s ORDER BY id DESC", $this->getTableName(), implode(" AND ", $conditions));
		if ($criteria->getLimit() !== null) {
			$sql .= ' LIMIT '.$criteria->getLimit();
		}

		$messagesRaw = $wpdb->get_results($sql);
		$messagesRaw = $criteria->getOrderMode() == MessagesCriteria::ORDER_DESCENDING
			? $messagesRaw
			: array_reverse($messagesRaw, true);

		return $this->populateMultiData($messagesRaw);
	}

	/**
	 * Returns number of messages following the criteria.
	 *
	 * @param MessagesCriteria $criteria
	 *
	 * @return integer
	 */
	public function getNumberByCriteria($criteria) {
		global $wpdb;

		$conditions = $this->getSQLConditionsByCriteria($criteria);
		$sql = sprintf("SELECT count(*) AS quantity FROM %s WHERE %s;", $this->getTableName(), implode(" AND ", $conditions));
		$results = $wpdb->get_results($sql);

		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $result->quantity;
		}

		return 0;
	}

	/**
	 * Deletes a message by ID.
	 *
	 * @param integer $id
	 *
	 * @return null
	 */
	public function deleteById($id) {
		global $wpdb;

		$id = intval($id);
		$wpdb->get_results(sprintf("DELETE FROM %s WHERE id = '%d';", $this->getTableName(), $id));
	}

	/**
	 * Unhides a message by ID.
	 *
	 * @param integer $id
	 *
	 * @return null
	 */
	public function unhideById($id) {
		global $wpdb;

		$id = intval($id);
		$wpdb->get_results(sprintf("UPDATE %s SET hidden = '0' WHERE id = '%d';", $this->getTableName(), $id));
	}

	/**
	 * Deletes all messages that follow the criteria.
	 *
	 * @param MessagesCriteria $criteria
	 */
	public function deleteAllByCriteria($criteria) {
		global $wpdb;

		$conditions = $this->getSQLConditionsByCriteria($criteria);
		$sql = sprintf("DELETE FROM %s WHERE %s;", $this->getTableName(), implode(" AND ", $conditions));
		$wpdb->get_results($sql);
	}

	/**
	 * @param Message $message
	 *
	 * @return Message[]
	 */
	public function getAllRepliesToMessage($message) {
		global $wpdb;

		$sql = sprintf("SELECT * FROM %s WHERE reply_to_message_id = %d LIMIT 100", $this->getTableName(), $message->getId());
		$messagesRaw = $wpdb->get_results($sql);

		return $this->populateMultiData($messagesRaw);
	}

	/**
	 * Gets the newest message in each direct channel of the user.
	 *
	 * @param User $user
	 * @param integer $limit
	 *
	 * @return Message[]
	 */
	public function getAllNewestDirectMessages(User $user, int $limit): array {
		global $wpdb;

		$channelTable = Installer::getChannelsTable();
		$channelMembersTable = Installer::getChannelMembersTable();

		// get the newest direct messages (group by channel_id and get the max by time):
		$sql = sprintf("SELECT o.*
			FROM `%s` o
	    	LEFT JOIN `%s` c ON (c.id = o.channel_id)
		  	LEFT JOIN `%s` b ON o.channel_id = b.channel_id AND (o.time < b.time OR o.id < b.id) AND b.admin = 0
			WHERE b.time IS NULL AND c.type = %d AND EXISTS ( SELECT * FROM %s m WHERE m.user_id = %d AND m.channel_id = c.id AND m.confirmed = 1 ) AND o.admin = 0
			ORDER BY o.time DESC LIMIT %d
			", $this->getTableName(), $channelTable, $this->getTableName(), Channel::TYPE_DIRECT, $channelMembersTable, $user->getId(), $limit);

		$messagesRaw = $wpdb->get_results($sql);

		return $this->populateMultiData($messagesRaw);
	}

	/**
	 * Returns array of SQL WHERE conditions based on given criteria.
	 *
	 * @param MessagesCriteria $criteria
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getSQLConditionsByCriteria($criteria) {
		$conditions = array();
		if (count($criteria->getChannelIDs()) > 0) {
			$channelIDs = array_map('intval', $criteria->getChannelIDs());

			if (count($channelIDs) === 1) {
				$conditions[] = "channel_id = '{$channelIDs[0]}'";
			} else {
				$conditions[] = "channel_id IN ('".implode("', '", $channelIDs)."')";
			}
		}
		if ($criteria->getUserId() !== null) {
			$conditions[] = "chat_user_id = ".intval($criteria->getUserId());
		}
		if ($criteria->getOffsetId() !== null) {
			$conditions[] = "id > ".intval($criteria->getOffsetId());
		}
		if ($criteria->getMaximumMessageId() !== null) {
			$conditions[] = "id < ".intval($criteria->getMaximumMessageId());
		}
		if (!$criteria->isIncludeAdminMessages()) {
			$conditions[] = "admin = 0";
		}
		if ($criteria->getMaximumTime() !== null) {
			$conditions[] = "time < ".intval($criteria->getMaximumTime());
		}
		if ($criteria->getMinimumTime() !== null) {
			$conditions[] = "time >= ".intval($criteria->getMinimumTime());
		}
		if (count($conditions) == 0) {
			$conditions[] = '1 = 1';
		}

		return $conditions;
	}

	/**
	 * Converts stdClass object into WiseChatMessage object.
	 *
	 * @param \stdClass $rawRow
	 *
	 * @return Message
	 */
	protected function populateData(\stdClass $rawRow): Message {
		$message = new Message();
		if ($rawRow->id) {
			$message->setId(intval($rawRow->id));
		}
		$message->setAdmin($rawRow->admin == '1');
		$message->setChannelId($rawRow->channel_id);
		if ($rawRow->chat_user_id) {
			$message->setUserId(intval($rawRow->chat_user_id));
		}
		$message->setText($rawRow->text);
		if ($rawRow->time) {
			$message->setTime(intval($rawRow->time));
		}
		if ($rawRow->user_id) {
			$message->setWordPressUserId(intval($rawRow->user_id));
		}
		$message->setHidden($rawRow->hidden == '1');
		$message->setReplyToMessageId($rawRow->reply_to_message_id);

		return $message;
	}

	/**
	 * Converts an array of stdClass objects into an array of WiseChatMessage objects.
	 *
	 * @param array $messagesRaw
	 *
	 * @return Message[]
	 */
	private function populateMultiData(array $messagesRaw) {
		$messages = array();
		$messagesToComplete = array();
		foreach ($messagesRaw as $messageRaw) {
			$message = $this->populateData($messageRaw);
			$messagesToComplete[$message->getUserId()][] = $message;
			$messages[] = $message;
		}

		$users = $this->usersDAO->getAll(array_keys($messagesToComplete));
		foreach ($users as $user) {
			if (array_key_exists($user->getId(), $messagesToComplete)) {
				foreach ($messagesToComplete[$user->getId()] as $message) {
					$message->setUser($user);
				}
			}
		}

		return $messages;
	}

	/**
	* Returns array of various statistics for each channel.
	*
	* @return array Array of objects (fields: channel, messages, users, last_message)
	*/
	public function getChannelsSummary() {
		global $wpdb;
		
		$table = Installer::getMessagesTable();
		$channelsTable = Installer::getChannelsTable();

		$conditions = array();
		$conditions[] = "ch.type != ".Channel::TYPE_DIRECT;
		$conditions[] = 'ch.name != "__private"';
		$sql = "SELECT ch.name AS channelName, ch.type as channelType, ch.id AS channelId, count(*) AS messages, count(distinct(user_id)) AS users, max(time) AS last_message FROM {$table}".
				" LEFT JOIN $channelsTable ch ON (ch.id = channel_id)".
				" WHERE ".implode(" AND ", $conditions).
				" GROUP BY channel_id ".
				" LIMIT 1000;";
		$mainSummary = $wpdb->get_results($sql);
		
		$mainSummaryMap = array();
		foreach ($mainSummary as $mainDetails) {
			$mainSummaryMap[$mainDetails->channelId] = $mainDetails;
		}
		
		$channels = $this->channelsDAO->getAllNonDirect();
		$fullSummary = array();
		foreach ($channels as $channel) {
			if (strpos($channel->getName(), 'bp-chat') === 0 || $channel->getName() === '__private') {
				continue;
			}
			if (array_key_exists($channel->getId(), $mainSummaryMap)) {
				$channelPrepared = $mainSummaryMap[$channel->getId()];
				$channelPrepared->secured = $channel->getPassword() ? true : false;
				$fullSummary[] = $channelPrepared;
			} else {
				$fullSummary[] = (object) array(
					'channelId' => $channel->getId(),
					'channelName' => $channel->getName(),
					'channelType' => $channel->getType(),
					'messages' => 0,
					'users' => 0,
					'last_message' => null,
					'secured' => $channel->getPassword()
				);
			}
		}
		
		return $fullSummary;
	}

	/**
	 * @param array $channelIds
	 * @param int $userId
	 * @param int $limit
	 * @return Message[]
	 */
	public function getLatestMessages(array $channelIds, int $userId, int $limit = 10): array {
		$raw = $this->getAllBy(['channel_id' => [$channelIds, '%d'], 'chat_user_id' => [$userId, '%d']], ['id', 'desc'], $limit);

		return array_map([$this, 'populateData'], $raw);
	}

	/**
	 * Returns all unread messages of the user.
	 *
	 * @param User $user
	 * @return Message[]
	 */
	public function getUnreadMessages(User $user): array {
		global $wpdb;

		$sql = sprintf(
			'SELECT me.*
			FROM %s AS pe
			LEFT JOIN %s AS me ON (me.id = pe.message_id)
			WHERE pe.checked = "0" AND pe.recipient_id = %d
			ORDER BY pe.id DESC;',
			Installer::getPendingChatsTable(), $this->getTableName(), $user->getId()
		);

		$results = $wpdb->get_results($sql);

		return array_map([$this, 'populateData'], $results);
	}

	/**
	 * Returns the number of messages of each channel.
	 *
	 * @param array $channelsIDs
	 * @return array[][]
	 */
	public function getCountByChannelsIDs(array $channelsIDs): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			'SELECT count(m.id) AS messagesCount, m.channel_id AS channelId FROM %i m
			WHERE m.channel_id IN ('.implode(', ', $channelsIDs).')
			GROUP BY m.channel_id;',
			$this->getTableName()
		);

		return $wpdb->get_results($sql, ARRAY_A);
	}

}