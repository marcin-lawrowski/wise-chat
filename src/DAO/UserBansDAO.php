<?php

namespace Kainex\WiseChat\DAO;

use Kainex\WiseChat\Model\UserBan;
use Kainex\WiseChat\Model\UserMute;
use Kainex\WiseChat\Installer;

/**
 * Wise Chat bans DAO
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserBansDAO extends AbstractDAO {

	protected function getTableName(): string {
		return Installer::getBansTable();
	}

	/**
	 * Creates or updates the ban and returns it.
	 *
	 * @param UserBan $userBan
	 *
	 * @return UserBan
	 * @throws \Exception On validation error
	 */
	public function save(UserBan $userBan): UserBan {
		// low-level validation:
		if ($userBan->getCreated() === null) {
			throw new \Exception('Created time cannot equal null');
		}
		if ($userBan->getIp() === null) {
			throw new \Exception('IP address cannot equal null');
		}

		$userBan->setId($this->persist([
			'created' => $userBan->getCreated(),
			'ip' => $userBan->getIp(),
			'user_id' => $userBan->getUserId()
		]));

		return $userBan;
	}

	/**
	 * @param integer $id
	 *
	 * @return UserBan|null
	 */
	public function get(int $id): ?UserBan {
		$raw = $this->getOneBy(['id' => [$id, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns ban by IP address.
	 *
	 * @param string $ip
	 *
	 * @return UserBan|null
	 */
	public function getByIp(string $ip): ?UserBan {
		$raw = $this->getOneBy(['ip' => [$ip, '%s']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns ban by user ID.
	 *
	 * @param integer $userId
	 *
	 * @return UserBan|null
	 */
	public function getByUserId(int $userId): ?UserBan {
		$raw = $this->getOneBy(['user_id' => [$userId, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns all ban sorted by time.
	 *
	 * @return UserBan[]
	 */
	public function getAll(): array {
		$rawArray = $this->getAllBy([], ['created', 'asc']);

		$userMutesArray = [];
		foreach ($rawArray as $result) {
			$userMutesArray[] = $this->populateData($result);
		}

		return $userMutesArray;
	}

	/**
	 * Converts raw object into UserBan object.
	 *
	 * @param \stdClass $rawRow
	 *
	 * @return UserBan
	 */
	protected function populateData(\stdClass $rawRow): UserBan {
		$ban = new UserBan();
		if ($rawRow->id > 0) {
			$ban->setId(intval($rawRow->id));
		}
		if ($rawRow->created > 0) {
			$ban->setCreated(intval($rawRow->created));
		}
		$ban->setIp($rawRow->ip);
		if ($rawRow->user_id > 0) {
			$ban->setUserId($rawRow->user_id);
		}

		return $ban;
	}
}