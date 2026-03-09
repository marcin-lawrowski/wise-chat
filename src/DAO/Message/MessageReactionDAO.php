<?php

namespace Kainex\WiseChat\DAO\Message;

use Exception;
use Kainex\WiseChat\Model\Message\MessageReaction;
use Kainex\WiseChat\Installer;

/**
 * Wise Chat message reaction DAO.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MessageReactionDAO {

	/**
	* @var string
	*/
	private $table;

	public function __construct() {
		$this->table = Installer::getReactionsTable();
	}

	/**
	 * Creates or updates the reaction and returns it.
	 *
	 * @param MessageReaction $reaction
	 *
	 * @return MessageReaction
	 * @throws Exception On validation error
	 */
	public function save($reaction) {
		global $wpdb;

		// prepare action data:
		$columns = array(
			'updated' => $reaction->getUpdated(),
			'message_id' => $reaction->getMessageId(),
			'reaction_1' => $reaction->getReaction1(),
			'reaction_2' => $reaction->getReaction2(),
			'reaction_3' => $reaction->getReaction3(),
			'reaction_4' => $reaction->getReaction4(),
			'reaction_5' => $reaction->getReaction5(),
			'reaction_6' => $reaction->getReaction6(),
			'reaction_7' => $reaction->getReaction7(),
		);

		// update or insert:
		if ($reaction->getId() !== null) {
			$wpdb->update($this->table, $columns, array('id' => $reaction->getId()), '%s', '%d');
		} else {
			$wpdb->insert($this->table, $columns);
			$reaction->setId($wpdb->insert_id);
		}

		return $reaction;
	}

	/**
	 * Returns reaction by message ID.
	 *
	 * @param integer $id
	 *
	 * @return MessageReaction|null
	 */
	public function getByMessageId($id) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE message_id = %d LIMIT 1;', $this->table, intval($id));
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $this->populateData($results[0]);
		}

		return null;
	}

	/**
	 * Returns reactions by message IDs.
	 *
	 * @param integer[] $messageIds
	 * @return MessageReaction[]
	 */
	public function getAllByMessageIds($messageIds) {
		global $wpdb;

		if (count($messageIds) === 0) {
			return array();
		}

		$sql = sprintf('SELECT * FROM %s WHERE message_id IN (%s);', $this->table, implode(', ', $messageIds));
		$results = $wpdb->get_results($sql);
		$reactions = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$reactions[] = $this->populateData($result);
			}
		}

		return $reactions;
	}

	/**
	 * Removes all reactions.
	 */
	public function deleteAll() {
		global $wpdb;

        $wpdb->get_results(sprintf("DELETE FROM %s;", $this->table));
	}

	/**
	 * Converts raw object into WiseChatMessageReaction object.
	 *
	 * @param \stdClass $rawData
	 *
	 * @return MessageReaction
	 */
	private function populateData($rawData) {
		$reaction = new MessageReaction();
		if ($rawData->id > 0) {
			$reaction->setId(intval($rawData->id));
		}
		$reaction->setUpdated(intval($rawData->updated));
		$reaction->setMessageId(intval($rawData->message_id));
		$reaction->setReaction1(intval($rawData->reaction_1));
		$reaction->setReaction2(intval($rawData->reaction_2));
		$reaction->setReaction3(intval($rawData->reaction_3));
		$reaction->setReaction4(intval($rawData->reaction_4));
		$reaction->setReaction5(intval($rawData->reaction_5));
		$reaction->setReaction6(intval($rawData->reaction_6));
		$reaction->setReaction7(intval($rawData->reaction_7));

		return $reaction;
	}

}