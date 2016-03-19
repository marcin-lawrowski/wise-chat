<?php

/**
 * Wise Chat channels DAO
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatChannelsDAO {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		WiseChatContainer::load('model/WiseChatChannel');
		$this->options = WiseChatOptions::getInstance();
	}

	/**
	 * Creates or updates the channel and returns it.
	 *
	 * @param WiseChatChannel $channel
	 *
	 * @return WiseChatChannel
	 * @throws Exception On validation error
	 */
	public function save($channel) {
		global $wpdb;

		// low-level validation:
		if ($channel->getName() === null) {
			throw new Exception('Name of the channel cannot equal null');
		}

		// prepare channel data:
		$table = WiseChatInstaller::getChannelsTable();
		$columns = array(
			'name' => $channel->getName(),
			'password' => $channel->getPassword()
		);

		// update or insert:
		if ($channel->getId() !== null) {
			$wpdb->update($table, $columns, array('id' => $channel->getId()), '%s', '%d');
		} else {
			$wpdb->insert($table, $columns);
			$channel->setId($wpdb->insert_id);
		}

		return $channel;
	}

	/**
	 * Returns channel by ID.
	 *
	 * @param integer $id
	 *
	 * @return WiseChatChannel|null
	 */
	public function get($id) {
		global $wpdb;

		$table = WiseChatInstaller::getChannelsTable();
		$sql = sprintf('SELECT * FROM %s WHERE id = %d;', $table, intval($id));
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $this->populateChannelData($results[0]);
		}

		return null;
	}

	/**
	 * Returns all channels sorted by name.
	 *
	 * @return WiseChatChannel[]
	 */
	public function getAll() {
		global $wpdb;

		$channels = array();
		$table = WiseChatInstaller::getChannelsTable();
		$sql = sprintf('SELECT * FROM %s ORDER BY name ASC;', $table);
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$channels[] = $this->populateChannelData($result);
			}
		}

		return $channels;
	}

	/**
	 * Returns channel by name.
	 *
	 * @param string $name
	 *
	 * @return WiseChatChannel|null
	 */
	public function getByName($name) {
		global $wpdb;

		$name = addslashes($name);
		$table = WiseChatInstaller::getChannelsTable();
		$sql = sprintf('SELECT * FROM %s WHERE name = "%s";', $table, $name);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $this->populateChannelData($results[0]);
		}

		return null;
	}

    /**
     * Deletes the channel by ID.
     *
     * @param integer $id
     *
     * @return null
     */
    public function deleteById($id) {
        global $wpdb;

        $id = intval($id);
        $table = WiseChatInstaller::getChannelsTable();
        $wpdb->get_results(sprintf("DELETE FROM %s WHERE id = '%d';", $table, $id));
    }

	/**
	 * Converts raw object into WiseChatChannel object.
	 *
	 * @param stdClass $rawChannelData
	 *
	 * @return WiseChatChannel
	 */
	private function populateChannelData($rawChannelData) {
		$channel = new WiseChatChannel();
		if ($rawChannelData->id > 0) {
			$channel->setId(intval($rawChannelData->id));
		}
		$channel->setName($rawChannelData->name);
		$channel->setPassword($rawChannelData->password);

		return $channel;
	}
}