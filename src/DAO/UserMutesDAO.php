<?php

namespace Kainex\WiseChat\DAO;

use Exception;
use Kainex\WiseChat\Model\UserMute;
use Kainex\WiseChat\Installer;

/**
 * Wise Chat mutes DAO
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserMutesDAO extends AbstractDAO {

	protected function getTableName(): string {
		return Installer::getUserMutesTable();
	}

	/**
	 * @param UserMute $userMute
	 *
	 * @return UserMute
	 * @throws Exception On validation error
	 */
	public function save(UserMute $userMute): UserMute {
		if ($userMute->getTime() === null) {
			throw new Exception('Time cannot equal null');
		}
		if ($userMute->getCreated() === null) {
			throw new Exception('Created time cannot equal null');
		}
		if ($userMute->getIp() === null) {
			throw new Exception('IP address cannot equal null');
		}

		$userMute->setId($this->persist([
			'time' => $userMute->getTime(),
			'created' => $userMute->getCreated(),
			'ip' => $userMute->getIp(),
			'user_id' => $userMute->getUserId()
		]));

		return $userMute;
	}

	/**
	 * @param integer $id
	 *
	 * @return UserMute|null
	 */
	public function get(int $id): ?UserMute {
		$raw = $this->getOneBy(['id' => [$id, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * @param string $ip
	 *
	 * @return UserMute|null
	 */
	public function getByIp(string $ip): ?UserMute {
		$raw = $this->getOneBy(['ip' => [$ip, '%s']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * @param integer $userId
	 *
	 * @return UserMute|null
	 */
	public function getByUserId(int $userId): ?UserMute {
		$raw = $this->getOneBy(['user_id' => [$userId, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * @return UserMute[]
	 */
	public function getAll(): array {
		$rawArray = $this->getAllBy([], ['time', 'asc']);

		$userMutesArray = [];
		foreach ($rawArray as $result) {
			$userMutesArray[] = $this->populateData($result);
		}

		return $userMutesArray;
	}

	/**
	 * Deletes user mutes that are older than the given time.
	 *
	 * @param integer $time
	 */
	public function deleteOlder(int $time) {
		global $wpdb;

		$time = intval($time);
		$wpdb->get_results("DELETE FROM {$this->getTableName()} WHERE time < $time");
	}

	/**
	 * Converts raw object into UserMute object.
	 *
	 * @param \stdClass $rawRow
	 *
	 * @return UserMute
	 */
	protected function populateData(\stdClass $rawRow): UserMute {
		$userMute = new UserMute();
		if ($rawRow->id > 0) {
			$userMute->setId(intval($rawRow->id));
		}
		if ($rawRow->time > 0) {
			$userMute->setTime(intval($rawRow->time));
		}
		if ($rawRow->created > 0) {
			$userMute->setCreated(intval($rawRow->created));
		}
		$userMute->setIp($rawRow->ip);
		if ($rawRow->user_id) {
			$userMute->setUserId($rawRow->user_id);
		}

		return $userMute;
	}
}