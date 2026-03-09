<?php

namespace Kainex\WiseChat\DAO;

use Exception;
use Kainex\WiseChat\Model\Channel\ChannelUser;
use Kainex\WiseChat\Installer;

/**
 * User status class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class ChannelUsersDAO extends AbstractDAO {

	protected function getTableName(): string {
		return Installer::getChannelUsersTable();
	}

	/**
	 * @var ChannelsDAO
	 */
	protected ChannelsDAO $channelsDAO;

	/**
	 * @param ChannelsDAO $channelsDAO
	 */
	public function __construct(ChannelsDAO $channelsDAO) {
		$this->channelsDAO = $channelsDAO;
	}

	public function save(ChannelUser $channelUser): ChannelUser {
		if ($channelUser->getUserId() === null) {
			throw new Exception('User ID is required');
		}

		$channelUser->setId($this->persist([
			'id' => $channelUser->getId(),
			'user_id' => $channelUser->getUserId(),
			'channel_id' => $channelUser->getChannelId(),
			'active' => $channelUser->isActive() === true ? '1' : '0',
			'last_activity_time' => $channelUser->getLastActivityTime()
		]));

		return $channelUser;
	}

	/**
	 * Returns the status of the user. It returns the most recent version.
	 *
	 * @param integer $userId
	 *
	 * @return ChannelUser|null
	 */
	public function getByUserId($userId) {
		global $wpdb;
		static $cache = [];
		$userId = intval($userId);

		if (isset($cache[$userId])) {
			return $cache[$userId];
		}

		$table = Installer::getChannelUsersTable();
		$sql = sprintf(
			'SELECT * FROM %s WHERE `user_id` = %d ORDER BY `last_activity_time` DESC LIMIT 1;', $table, intval($userId)
		);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$cache[$userId] = $this->populateData($results[0]);
		} else {
			$cache[$userId] = null;
		}

		return $cache[$userId];
	}

	/**
	 * Checks if the user is online (status: active).
	 *
	 * @param integer $userId
	 * @param \WP_User|false $wpUser
	 * @return bool
	 */
	public function isOnline($userId, $wpUser = false) {
		if ($wpUser) {
			// AI bots are always online:
			return get_user_meta($wpUser->ID, 'wc_ai_bot', true) === '1';
		}
		$status = $this->getByUserId($userId);

		return $status !== null && $status->isActive();
	}

	/**
	 * @param integer $userId
	 * @param boolean $status
	 * @throws Exception
	 */
	public function setStatus($userId, $status) {
		$userStatus = $this->getByUserId($userId);
		if ($userStatus) {
			$userStatus->setActive($status);
			$this->save($userStatus);
		}
	}

	/**
	 * Updates the status of statuses older than the given amount of seconds.
	 *
	 * @param boolean $active
	 * @param integer $time
	 */
	public function updateActiveForOlderByLastActivityTime($active, $time) {
		global $wpdb;

		$table = Installer::getChannelUsersTable();
		$threshold = time() - $time;

		$wpdb->get_results(
			sprintf("UPDATE %s SET active = %d WHERE `last_activity_time` < %d;", $table, $active === true ? 1 : 0, $threshold)
		);
	}

	/**
	 * Deletes statuses older than the given amount of seconds.
	 *
	 * @param integer $time
	 */
	public function deleteOlderByLastActivityTime($time) {
		global $wpdb;

		$table = Installer::getChannelUsersTable();
		$threshold = time() - $time;

		$wpdb->get_results(
			sprintf("DELETE FROM %s WHERE `last_activity_time` < %s;", $table, $threshold)
		);
	}

	/**
	 * Converts stdClass object into WiseChatChannelUser object.
	 *
	 * @param \stdClass $rawRow
	 *
	 * @return ChannelUser
	 */
	protected function populateData(\stdClass $rawRow): ChannelUser {
		$channelUser = new ChannelUser();
		if ($rawRow->id > 0) {
			$channelUser->setId(intval($rawRow->id));
		}
		if ($rawRow->user_id > 0) {
			$channelUser->setUserId(intval($rawRow->user_id));
		}
		if ($rawRow->channel_id > 0) {
			$channelUser->setChannelId(intval($rawRow->channel_id));
		}
		$channelUser->setActive($rawRow->active == '1');
		if ($rawRow->last_activity_time > 0) {
			$channelUser->setLastActivityTime(intval($rawRow->last_activity_time));
		}

		return $channelUser;
	}

	/**
	 * Returns the number of online users.
	 *
	 * @return integer
	 */
	public function countOnlineUsers() {
		global $wpdb;

		$table = Installer::getChannelUsersTable();
		$sql = sprintf('SELECT count(DISTINCT `user_id`) AS quantity FROM %s WHERE `active` = 1;', $table);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];

			return $result->quantity;
		}

		return 0;
	}

	/**
	* Checks whether the given user name belongs to a different user.
	*
	* @param string $userName Username to check
	* @param boolean $includeActiveOnly
	*
	* @return boolean
	*/
	public function isUserNameOccupied($userName, $includeActiveOnly = false) {
		global $wpdb;

		$userName = addslashes($userName);
		$table = Installer::getChannelUsersTable();
		$usersTable = Installer::getUsersTable();
		$activeOnlyCondition = $includeActiveOnly ? ' AND usc.active = 1 ' : '';
		$sql = sprintf(
			'SELECT * '.
			'FROM %s AS usc '.
			'LEFT JOIN %s AS us ON (usc.user_id = us.id) '.
			'WHERE us.name = "%s" %s LIMIT 1;',
			$table, $usersTable, $userName, $activeOnlyCondition
		);
		$results = $wpdb->get_results($sql);
		
		return is_array($results) && count($results) > 0;
	}

	public function deleteAll(): void {
		$this->deleteBy(['id' => [0, '%d', '>']]);
	}
	
}