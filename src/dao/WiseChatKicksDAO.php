<?php

/**
 * Wise Chat kicks DAO
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatKicksDAO {
	/**
	 * @var WiseChatOptions
	 */
	private $options;

	/**
	 * @var string
	 */
	private $table;

	public function __construct() {
		WiseChatContainer::load('model/WiseChatKick');
		$this->options = WiseChatOptions::getInstance();
		$this->table = WiseChatInstaller::getKicksTable();
	}

	/**
	 * Creates or updates the kick and returns it.
	 *
	 * @param WiseChatKick $kick
	 *
	 * @return WiseChatKick
	 * @throws Exception On validation error
	 */
	public function save($kick) {
		global $wpdb;

		// low-level validation:
		if ($kick->getLastUserName() === null) {
			throw new Exception('Time cannot equal null');
		}
		if ($kick->getCreated() === null) {
			throw new Exception('Created time cannot equal null');
		}
		if ($kick->getIp() === null) {
			throw new Exception('IP address cannot equal null');
		}

		// prepare ban data:
		$columns = array(
			'last_user_name' => $kick->getLastUserName(),
			'created' => $kick->getCreated(),
			'ip' => $kick->getIp()
		);

		// update or insert:
		if ($kick->getId() !== null) {
			$wpdb->update($this->table, $columns, array('id' => $kick->getId()), '%s', '%d');
		} else {
			$wpdb->insert($this->table, $columns);
			$kick->setId($wpdb->insert_id);
		}

		return $kick;
	}

	/**
	 * Returns kick by ID.
	 *
	 * @param integer $id
	 *
	 * @return WiseChatKick|null
	 */
	public function get($id) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE id = %d;', $this->table, $id);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $this->populateData($results[0]);
		}

		return null;
	}

	/**
	 * Returns kick by IP address.
	 *
	 * @param string $ip
	 *
	 * @return WiseChatKick|null
	 */
	public function getByIp($ip) {
		global $wpdb;

		$sql = sprintf("SELECT * FROM %s WHERE ip = '%s' LIMIT 1;", $this->table, addslashes($ip));
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $this->populateData($results[0]);
		}

		return null;
	}

	/**
	 * Returns all kicks sorted by time.
	 *
	 * @return WiseChatKick[]
	 */
	public function getAll() {
		global $wpdb;

		$bans = array();
		$sql = sprintf('SELECT * FROM %s ORDER BY created ASC;', $this->table);
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$bans[] = $this->populateData($result);
			}
		}

		return $bans;
	}

	/**
	 * Deletes kick by IP address.
	 *
	 * @param integer $id
	 *
	 * @return null
	 */
	public function delete($id) {
		global $wpdb;

		$id = intval($id);
		$wpdb->get_results("DELETE FROM {$this->table} WHERE id = '{$id}'");
	}

	/**
	 * Deletes kicks by IP address.
	 *
	 * @param string $ip Given IP address
	 *
	 * @return null
	 */
	public function deleteByIp($ip) {
		global $wpdb;

		$ip = addslashes($ip);
		$wpdb->get_results("DELETE FROM {$this->table} WHERE ip = '{$ip}'");
	}

	/**
	 * Converts raw object into WiseChatKick object.
	 *
	 * @param stdClass $rawKickData
	 *
	 * @return WiseChatKick
	 */
	private function populateData($rawKickData) {
		$kick = new WiseChatKick();
		if ($rawKickData->id > 0) {
			$kick->setId(intval($rawKickData->id));
		}
		if ($rawKickData->created > 0) {
			$kick->setCreated(intval($rawKickData->created));
		}
		$kick->setIp($rawKickData->ip);
		$kick->setLastUserName($rawKickData->last_user_name);

		return $kick;
	}
}