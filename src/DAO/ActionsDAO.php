<?php

namespace Kainex\WiseChat\DAO;

use Exception;
use Kainex\WiseChat\Model\Action;
use Kainex\WiseChat\Installer;

/**
 * Wise Chat actions DAO. Actions are commands sent from the chat server to each client.
 *
 * @author Kainex <contact@kaine.pl>
 */
class ActionsDAO {
	const ACTIONS_LIMIT = 100;

	/**
	 * Creates or updates the action and returns it.
	 *
	 * @param Action $action
	 *
	 * @return Action
	 * @throws Exception On validation error
	 */
	public function save($action) {
		global $wpdb;

		// low-level validation:
		if ($action->getCommand() === null) {
			throw new Exception('Command of the action cannot equal null');
		}

		// prepare action data:
		$columns = array(
			'time' => $action->getTime(),
			'command' => json_encode($action->getCommand())
		);

		// update or insert:
		if ($action->getId() !== null) {
			$columns['user_id'] = $action->getUserId();
			$wpdb->update(Installer::getActionsTable(), $columns, array('id' => $action->getId()), '%s', '%d');
		} else {
			if ($action->getUserId() !== null) {
				$columns['user_id'] = $action->getUserId();
			}
			$wpdb->insert(Installer::getActionsTable(), $columns);
			$action->setId($wpdb->insert_id);
		}

		return $action;
	}

	/**
	 * Returns action by ID.
	 *
	 * @param integer $id
	 *
	 * @return Action|null
	 */
	public function get($id) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE id = %d;', Installer::getActionsTable(), $id);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $this->populateActionData($results[0]);
		}

		return null;
	}

	/**
	 * Returns the last action according to the ID.
	 *
	 * @return Action|null
	 */
	public function getLast() {
		global $wpdb;

		$actions = $wpdb->get_results("SELECT max(id) AS id FROM ".Installer::getActionsTable().";");
		if (is_array($actions) && count($actions) > 0) {
			$action = $actions[0];
			return $this->get($action->id);
		}

		return null;
	}

	/**
	 * Returns actions beginning from the specified ID and (optionally) by user ID.
	 *
	 * @param integer $fromId Offset
	 * @param integer $userId
	 *
	 * @return Action[]
	 */
	public function getBeginningFromIdAndByUser($fromId, $userId = null) {
		global $wpdb;

		$conditions = array();
		$conditions[] = "id > ".intval($fromId);
		if ($userId === null) {
			$conditions[] = "user_id IS NULL";
		} else {
			$conditions[] = sprintf("(user_id IS NULL OR user_id = '%d')", intval($userId));
		}
		$sql = sprintf(
			"SELECT * FROM %s WHERE %s ORDER BY id ASC LIMIT %d",
			Installer::getActionsTable(), implode(" AND ", $conditions), self::ACTIONS_LIMIT
		);

		$actions = array();
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$actions[] = $this->populateActionData($result);
			}
		}

		return $actions;
	}

	/**
	 * Converts raw object into Action object.
	 *
	 * @param \stdClass $rawActionData
	 *
	 * @return Action
	 */
	private function populateActionData($rawActionData) {
		$action = new Action();
		if ($rawActionData->id > 0) {
			$action->setId(intval($rawActionData->id));
		}
		$action->setTime(intval($rawActionData->time));
		if ($rawActionData->user_id > 0) {
			$action->setUserId(intval($rawActionData->user_id));
		}
		$action->setCommand(json_decode($rawActionData->command, true));

		return $action;
	}
}