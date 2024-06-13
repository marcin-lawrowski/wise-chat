<?php

/**
 * WiseChat messages DAO
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMessagesDAO {

	/**
	* @var WiseChatChannelsDAO
	*/
	private $channelsDAO;
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;

	/**
	 * @var string
	 */
	private $table;
	
	public function __construct() {
		WiseChatContainer::load('model/WiseChatMessage');
		WiseChatContainer::load('dao/criteria/WiseChatMessagesCriteria');
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->channelsDAO = WiseChatContainer::get('dao/WiseChatChannelsDAO');
		$this->channelUsersDAO = WiseChatContainer::get('dao/WiseChatChannelUsersDAO');

		$this->table = WiseChatInstaller::getMessagesTable();
	}

	/**
	 * Creates or updates the message and returns it.
	 *
	 * @param WiseChatMessage $message
	 *
	 * @return WiseChatMessage
	 * @throws Exception On validation error
	 */
	public function save($message) {
		global $wpdb;

		// low-level validation:
		if ($message->getTime() === null) {
			throw new Exception('Time cannot be null');
		}
		if ($message->getUserId() === null) {
			throw new Exception('User ID cannot be null');
		}
		if ($message->getUserName() === null) {
			throw new Exception('Username cannot be null');
		}
		if ($message->getText() === null) {
			throw new Exception('Text cannot be null');
		}
		if ($message->getChannelName() === null) {
			throw new Exception('Channel name cannot be null');
		}

		// prepare user data:
		$columns = array(
			'time' => $message->getTime(),
			'admin' => $message->isAdmin() ? 1 : 0,
			'user' => $message->getUserName(),
			'chat_user_id' => $message->getUserId(),
			'text' => $message->getText(),
			'avatar_url' => $message->getAvatarUrl(),
			'channel' => $message->getChannelName(),
			'ip' => $message->getIp()
		);

		// update or insert:
		if ($message->getId() !== null) {
			$columns['user_id'] = $message->getWordPressUserId();
			$columns['chat_recipient_id'] = $message->getRecipientId();
			$wpdb->update($this->table, $columns, array('id' => $message->getId()), '%s', '%d');
		} else {
			if ($message->getWordPressUserId() > 0) {
				$columns['user_id'] = $message->getWordPressUserId();
			}
			if ($message->getRecipientId() > 0) {
				$columns['chat_recipient_id'] = $message->getRecipientId();
			}
			$wpdb->insert($this->table, $columns);
			$message->setId($wpdb->insert_id);
		}

		return $message;
	}

	/**
	 * Updates username in all messages that follow the criteria.
	 *
	 * @param string userName
	 * @param WiseChatMessagesCriteria $criteria
	 */
	public function updateUserNameByCriteria($userName, $criteria) {
		global $wpdb;

		$userName = addslashes($userName);
		$conditions = $this->getSQLConditionsByCriteria($criteria);
		$sql = sprintf(
			'UPDATE %s SET user = "%s" WHERE %s;', $this->table, $userName, implode(" AND ", $conditions)
		);
		$wpdb->get_results($sql);
	}

	/**
	 * Returns messages by ID.
	 *
	 * @param integer $id
	 *
	 * @return WiseChatMessage|null
	 */
	public function get($id) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE id = %d;', $this->table, intval($id));
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return self::populateData($results[0]);
		}

		return null;
	}

	/**
	 * Returns all messages that follow the criteria.
	 *
	 * @param WiseChatMessagesCriteria $criteria
	 *
	 * @return WiseChatMessage[]
	 */
	public function getAllByCriteria($criteria) {
		global $wpdb;

		$conditions = $this->getSQLConditionsByCriteria($criteria);
		$sql = sprintf("SELECT * FROM %s WHERE %s ORDER BY id DESC", $this->table, implode(" AND ", $conditions));
		if ($criteria->getLimit() !== null) {
			$sql .= ' LIMIT '.$criteria->getLimit();
		}

		$messagesRaw = $wpdb->get_results($sql);
		$messagesRaw = $criteria->getOrderMode() == WiseChatMessagesCriteria::ORDER_DESCENDING
			? $messagesRaw
			: array_reverse($messagesRaw, true);

		return $this->populateMultiData($messagesRaw);
	}

	/**
	 * Returns number of messages following the criteria.
	 *
	 * @param WiseChatMessagesCriteria $criteria
	 *
	 * @return integer
	 */
	public function getNumberByCriteria($criteria) {
		global $wpdb;

		$conditions = $this->getSQLConditionsByCriteria($criteria);
		$sql = sprintf("SELECT count(*) AS quantity FROM %s WHERE %s;", $this->table, implode(" AND ", $conditions));
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
		$wpdb->get_results(sprintf("DELETE FROM %s WHERE id = '%d';", $this->table, $id));
	}

	/**
	 * Deletes all messages that follow the criteria.
	 *
	 * @param WiseChatMessagesCriteria $criteria
	 */
	public function deleteAllByCriteria($criteria) {
		global $wpdb;

		$conditions = $this->getSQLConditionsByCriteria($criteria);
		$sql = sprintf("DELETE FROM %s WHERE %s;", $this->table, implode(" AND ", $conditions));
		$wpdb->get_results($sql);
	}

	/**
	 * Updates user name by specified WordPress user ID.
	 *
	 * @param string $name
	 * @param integer $wpUserId
	 */
	public function updateUserNameByWordPressUserId($name, $wpUserId) {
		global $wpdb;

		$wpdb->update($this->table, array('user' => $name), array('user_id' => $wpUserId), '%s', '%d');
	}

	/**
	 * @param WiseChatMessage $message
	 *
	 * @return WiseChatMessage[]
	 */
	public function getAllRepliesToMessage($message) {
		global $wpdb;

		$sql = sprintf("SELECT * FROM %s WHERE reply_to_message_id = %d LIMIT 100", $this->table, $message->getId());
		$messagesRaw = $wpdb->get_results($sql);

		return $this->populateMultiData($messagesRaw);
	}

	/**
	 * @param WiseChatUser $user
	 * @param WiseChatChannel $channel
	 * @param integer $limit
	 *
	 * @return WiseChatMessage[]
	 */
	public function getAllNewestDirectMessages($user, $channel, $limit) {
		global $wpdb;

		$messages = array();

		// get the newest direct messages when the user is the sender  (group by chat_recipient_id and get the max by time):
		$sql = sprintf("SELECT o.*
			FROM `%s` o
		  	LEFT JOIN `%s` b ON o.chat_recipient_id = b.chat_recipient_id 
			      	AND o.time < b.time AND b.channel = '%s' AND b.chat_recipient_id IS NOT NULL
			      	AND b.chat_recipient_id != '' AND b.chat_user_id = %d AND b.admin = 0
			WHERE b.time IS NULL AND o.channel = '%s' AND o.chat_recipient_id IS NOT NULL
			      	AND o.chat_recipient_id != '' AND o.chat_user_id = %d AND o.admin = 0
			ORDER BY o.time DESC LIMIT %d
			", $this->table, $this->table, addslashes($channel->getName()), $user->getId(), addslashes($channel->getName()), $user->getId(), $limit);

		$ownMessages = $wpdb->get_results($sql);
		foreach ($ownMessages as $ownMessage) {
			$messages[$ownMessage->chat_recipient_id][$ownMessage->time] = $ownMessage;
		}

		// get the newest direct messages when the user is the recipient (group by chat_user_id and get the max by time):
		$sql = sprintf("SELECT o.*
			FROM `%s` o
		  	LEFT JOIN `%s` b ON o.chat_user_id = b.chat_user_id 
			      	AND o.time < b.time AND b.channel = '%s' AND b.chat_recipient_id IS NOT NULL
			      	AND b.chat_recipient_id != '' AND b.chat_recipient_id = %d AND b.admin = 0
			WHERE b.time IS NULL AND o.channel = '%s' AND o.chat_recipient_id IS NOT NULL
			      	AND o.chat_recipient_id != '' AND o.chat_recipient_id = %d AND o.admin = 0
			ORDER BY o.time DESC LIMIT %d
			", $this->table, $this->table, addslashes($channel->getName()), $user->getId(), addslashes($channel->getName()), $user->getId(), $limit);

		$receivedMessages = $wpdb->get_results($sql);
		foreach ($receivedMessages as $receivedMessage) {
			$messages[$receivedMessage->chat_user_id][$receivedMessage->time] = $receivedMessage;
		}

		// get the newest messages of each chat participant:
		$messagesByTime = array();
		foreach ($messages as $chatParticipantId => $directMessages) {
			krsort($directMessages);
			reset($directMessages);
			$firstTimestamp = key($directMessages);

			$messagesByTime[] = $directMessages[$firstTimestamp];
		}

		// sort by time descending:
		usort($messagesByTime, function($a, $b) { return $a->time > $b->time ? -1 : 1; });

		$messages = array();
		foreach ($messagesByTime as $messageRaw) {
			$messages[] = self::populateData($messageRaw);
		}

		return $messages;
	}

	/**
	 * Returns array of SQL WHERE conditions based on given criteria.
	 *
	 * @param WiseChatMessagesCriteria $criteria
	 *
	 * @return array
	 * @throws Exception
	 */
	private function getSQLConditionsByCriteria($criteria) {
		$conditions = array();
		if (count($criteria->getChannelNames()) > 0) {
			$channelNames = array_map('addslashes', $criteria->getChannelNames());

			if (count($channelNames) === 1) {
				$conditions[] = "channel = '{$channelNames[0]}'";
			} else {
				$conditions[] = "channel IN ('".implode("', '", $channelNames)."')";
			}
		}
		if ($criteria->getRecipientOrSenderId() !== null) {
			$id = intval($criteria->getRecipientOrSenderId());
			if ($criteria->isIncludeOnlyPrivateMessages()) {
				$conditions[] = "(chat_recipient_id IS NOT NULL AND chat_recipient_id != '' AND (chat_user_id = $id OR chat_recipient_id = $id))";
			} else {
				$conditions[] = "(chat_recipient_id IS NULL OR chat_recipient_id = '' OR chat_user_id = $id OR chat_recipient_id = $id)";
			}
		} else if ($criteria->isIncludeOnlyPrivateMessages()) {
			$conditions[] = "(chat_recipient_id IS NOT NULL AND chat_recipient_id != '')";
		} else {
			$conditions[] = "(chat_recipient_id IS NULL OR chat_recipient_id = '')";
		}
		if (count($criteria->getDirectChatters()) > 0) {
			$chatters = $criteria->getDirectChatters();
			if (count($chatters) !== 2) {
				throw new Exception('Invalid number of chatters');
			}
			$conditions[] = "((chat_user_id = {$chatters[0]} AND chat_recipient_id = {$chatters[1]}) OR (chat_user_id = {$chatters[1]} AND chat_recipient_id = {$chatters[0]}))";
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
		if ($criteria->getIp() !== null) {
			$ip = addslashes($criteria->getIp());
			$conditions[] = "ip = '{$ip}'";
		}
		if (count($conditions) == 0) {
			$conditions[] = '1 = 1';
		}

		return $conditions;
	}

	/**
	 * Converts stdClass object into WiseChatMessage object.
	 *
	 * @param stdClass $messageRawData
	 *
	 * @return WiseChatMessage
	 */
	public static function populateData($messageRawData) {
		$message = new WiseChatMessage();
		if ($messageRawData->id) {
			$message->setId(intval($messageRawData->id));
		}
		$message->setAdmin($messageRawData->admin == '1');
		$message->setUserName($messageRawData->user);
		$message->setChannelName($messageRawData->channel);
		if ($messageRawData->chat_user_id) {
			$message->setUserId(intval($messageRawData->chat_user_id));
		}
		$message->setText($messageRawData->text);
		$message->setAvatarUrl($messageRawData->avatar_url);
		$message->setIp($messageRawData->ip);
		if ($messageRawData->time) {
			$message->setTime(intval($messageRawData->time));
		}
		if ($messageRawData->user_id) {
			$message->setWordPressUserId(intval($messageRawData->user_id));
		}
		if ($messageRawData->chat_recipient_id) {
			$message->setRecipientId(intval($messageRawData->chat_recipient_id));
		}

		return $message;
	}

	/**
	 * Converts an array of stdClass objects into an array of WiseChatMessage objects.
	 *
	 * @param array $messagesRaw
	 *
	 * @return WiseChatMessage[]
	 */
	private function populateMultiData($messagesRaw) {
		if (!is_array($messagesRaw)) {
            return array();
        }

		$messages = array();
		$messagesToComplete = array();
		$messagesRecipientsToComplete = array();
		foreach ($messagesRaw as $messageRaw) {
			$message = self::populateData($messageRaw);
			$messagesToComplete[$message->getUserId()][] = $message;
			if ($message->getRecipientId() > 0) {
				$messagesRecipientsToComplete[$message->getRecipientId()][] = $message;
			}
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

		$users = $this->usersDAO->getAll(array_keys($messagesRecipientsToComplete));
		foreach ($users as $user) {
			if (array_key_exists($user->getId(), $messagesRecipientsToComplete)) {
				foreach ($messagesRecipientsToComplete[$user->getId()] as $message) {
					$message->setRecipient($user);
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
		
		$table = WiseChatInstaller::getMessagesTable();
		
		$conditions = array();
		$conditions[] = "user != 'System'";
		$sql = "SELECT channel, count(*) AS messages, count(distinct(user_id)) AS users, max(time) AS last_message FROM {$table} ".
				" WHERE ".implode(" AND ", $conditions).
				" GROUP BY channel ".
				" ORDER BY channel ASC ".
				" LIMIT 1000;";
		$mainSummary = $wpdb->get_results($sql);
		
		$mainSummaryMap = array();
		foreach ($mainSummary as $mainDetails) {
			$mainSummaryMap[$mainDetails->channel] = $mainDetails;
		}
		
		$channels = $this->channelsDAO->getAll();
		$fullSummary = array();
		foreach ($channels as $channel) {
			if (strpos($channel->getName(), 'bp-chat') === 0) {
				continue;
			}
			if (array_key_exists($channel->getName(), $mainSummaryMap)) {
				$channelPrepared = $mainSummaryMap[$channel->getName()];
				$channelPrepared->secured = $channel->getPassword() ? true : false;
				$fullSummary[] = $channelPrepared;
			} else {
				$fullSummary[] = (object) array(
					'channel' => $channel->getName(),
					'messages' => 0,
					'users' => 0,
					'last_message' => null,
					'secured' => $channel->getPassword()
				);
			}
		}
		
		return $fullSummary;
	}
}