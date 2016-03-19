<?php

/**
 * Wise Chat bans DAO
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatBansDAO {
	/**
	* @var WiseChatOptions
	*/
	private $options;

	/**
	 * @var string
	 */
	private $table;
	
	public function __construct() {
		WiseChatContainer::load('model/WiseChatBan');
		$this->options = WiseChatOptions::getInstance();
		$this->table = WiseChatInstaller::getBansTable();
	}

	/**
	 * Creates or updates the ban and returns it.
	 *
	 * @param WiseChatBan $ban
	 *
	 * @return WiseChatBan
	 * @throws Exception On validation error
	 */
	public function save($ban) {
		global $wpdb;

		// low-level validation:
		if ($ban->getTime() === null) {
			throw new Exception('Time cannot equal null');
		}
		if ($ban->getCreated() === null) {
			throw new Exception('Created time cannot equal null');
		}
		if ($ban->getIp() === null) {
			throw new Exception('IP address cannot equal null');
		}

		// prepare ban data:
		$columns = array(
			'time' => $ban->getTime(),
			'created' => $ban->getCreated(),
			'ip' => $ban->getIp()
		);

		// update or insert:
		if ($ban->getId() !== null) {
			$wpdb->update($this->table, $columns, array('id' => $ban->getId()), '%s', '%d');
		} else {
			$wpdb->insert($this->table, $columns);
			$ban->setId($wpdb->insert_id);
		}

		return $ban;
	}

	/**
	 * Returns ban by ID.
	 *
	 * @param integer $id
	 *
	 * @return WiseChatBan|null
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
	 * Returns ban by IP address.
	 *
	 * @param string $ip
	 *
	 * @return WiseChatBan|null
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
	 * Returns all bans sorted by time.
	 *
	 * @return WiseChatBan[]
	 */
	public function getAll() {
		global $wpdb;

		$bans = array();
		$sql = sprintf('SELECT * FROM %s ORDER BY time ASC;', $this->table);
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$bans[] = $this->populateData($result);
			}
		}

		return $bans;
	}

	/**
	 * Deletes bans that are older than the given time.
	 *
	 * @param integer $time
	 *
	 * @return null
	 */
	public function deleteOlder($time) {
		global $wpdb;

		$time = intval($time);
		$wpdb->get_results("DELETE FROM {$this->table} WHERE time < $time");
	}

	/**
	 * Deletes bans by IP address.
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
	 * Converts raw object into WiseChatBan object.
	 *
	 * @param stdClass $rawBanData
	 *
	 * @return WiseChatBan
	 */
	private function populateData($rawBanData) {
		$ban = new WiseChatBan();
		if ($rawBanData->id > 0) {
			$ban->setId(intval($rawBanData->id));
		}
		if ($rawBanData->time > 0) {
			$ban->setTime(intval($rawBanData->time));
		}
		if ($rawBanData->created > 0) {
			$ban->setCreated(intval($rawBanData->created));
		}
		$ban->setIp($rawBanData->ip);

		return $ban;
	}
}