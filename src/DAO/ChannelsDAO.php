<?php

namespace Kainex\WiseChat\DAO;

use Exception;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Installer;

/**
 * Wise Chat channels DAO
 *
 * @author Kainex <contact@kainex.pl>
 */
class ChannelsDAO extends AbstractDAO {

	const DEFAULT_CONFIGURATION = [
		'enableImages' => true,
		'enableAttachments' => true,
		'enableVoiceMessages' => true
	];

	protected function getTableName(): string {
		return Installer::getChannelsTable();
	}

	/**
	 * Creates or updates the channel and returns it.
	 *
	 * @param Channel $channel
	 *
	 * @return Channel
	 * @throws Exception On validation error
	 */
	public function save(Channel $channel): Channel {
		// low-level validation:
		if ($channel->getName() === null) {
			throw new Exception('Name of the channel cannot equal null');
		}

		$channel->setId($this->persist([
			'id' => $channel->getId(),
			'name' => $channel->getName(),
			'password' => $channel->getPassword(),
			'type' => $channel->getType(),
			'configuration' => json_encode($channel->getConfiguration())
		]));

		return $channel;
	}

	/**
	 * Returns channel by ID.
	 *
	 * @param integer $id
	 *
	 * @return Channel|null
	 */
	public function get(int $id): ?Channel {
		$raw = $this->getOneBy(['id' => [$id, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns channels by IDs.
	 *
	 * @param integer[] $ids
	 *
	 * @return Channel[]
	 */
	public function getAllById(array $ids): array {
		if (!$ids) {
			return [];
		}

		return array_map([$this, 'populateData'], $this->getAllBy(['id' => [$ids, '%d']]));
	}

	/**
	 * Search non-direct channels.
	 *
	 * @param array $filters
	 * @return Channel[]
	 */
	public function searchChannels(array $filters): array {
		$limit = 20;
		$keyword = $filters['search'];
		$exclude = $filters['exclude'] ?? [];
		$excludeName = $filters['excludeNames'] ?? [];
		$page = $filters['page'] > 0 ? $filters['page'] : 1;
		$offset = ($page - 1) * $limit;
		$conditions = ['type' => [Channel::TYPE_DIRECT, '%d', '!=']];
		if (strlen($keyword) > 0) {
			$conditions['name'] = [$keyword, '%s', 'search'];
		}
		if (count($exclude) > 0) {
			$conditions[] = ['id', $exclude, '%d', 'not in'];
		}
		foreach ($excludeName as $name) {
			$conditions[] = ['name', $name, '%s', 'not like'];
		}

		$channels = array_map([$this, 'populateData'], $this->getAllBy($conditions, ['name', 'asc'], $limit, $offset));

		return $channels;
	}

	/**
	 * Returns all non-direct channels sorted by name.
	 *
	 * @return Channel[]
	 */
	public function getAllNonDirect(): array {
		global $wpdb;

		$channels = array();
		$table = Installer::getChannelsTable();
		$sql = sprintf('SELECT * FROM %s WHERE `type` != %d ORDER BY name ASC;', $table, Channel::TYPE_DIRECT);
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$channels[] = $this->populateData($result);
			}
		}

		return $channels;
	}

	/**
	 * Returns channel by name.
	 *
	 * @param string $name
	 *
	 * @return Channel|null
	 */
	public function getByName($name): ?Channel {
		$raw = $this->getOneBy(['name' => [$name, '%s']]);

		return $raw ? $this->populateData($raw) : null;
	}


	/**
	 * @param string $name
	 * @param int $typeId
	 * @return Channel|null
	 */
	public function getByNameAndType(string $name, int $typeId): ?Channel {
		$raw = $this->getOneBy(['type' => [$typeId, '%d'], 'name' => $name]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns channels by names. The method is cached.
	 *
	 * @param string[] $names
	 *
	 * @return Channel[]
	 */
	public function getByNames($names) {
		global $wpdb;
		static $cache = array();

		$names = array_filter(array_map('addslashes', $names));
		if (count($names) === 0) {
			return array();
		}
		$namesCondition = implode("', '", $names);

		$cacheKey = md5($namesCondition);
		if (array_key_exists($cacheKey, $cache)) {
			return $cache[$cacheKey];
		}

		$table = Installer::getChannelsTable();
		$sql = sprintf("SELECT * FROM %s WHERE name IN ('%s');", $table, $namesCondition);
		$results = $wpdb->get_results($sql);
		$channels = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$channels[] = $this->populateData($result);
			}
		}

		$cache[$cacheKey] = $channels;

		return $channels;
	}

	/**
	 * Gets user's personal list of channels. If channel is public then there is no need to be a member of such channel.
	 *
	 * @param $userId
	 * @return Channel[]
	 */
	public function getUserChannels($userId): array {
		global $wpdb;

		$channelMembersTable = Installer::getChannelMembersTable();
		$userChannelsTable = Installer::getUserChannelsTable();
		$table = Installer::getChannelsTable();
		$sql = sprintf(
			"SELECT c.*
			FROM %s uc
			LEFT JOIN %s c ON (c.id = uc.channel_id)
			WHERE uc.user_id = %d AND (c.type = %d OR EXISTS ( SELECT * FROM %s m WHERE m.user_id = uc.user_id AND m.channel_id = uc.channel_id AND m.confirmed = 1 ) );",
			$userChannelsTable, $table, $userId, Channel::TYPE_PUBLIC, $channelMembersTable
		);

		$results = $wpdb->get_results($sql);
		$channels = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$channels[] = $this->populateData($result);
			}
		}

		return $channels;
	}

	/**
	 * Gets user's opened channels:
	 * - public - without limitation
	 * - private / direct - only if the user is a confirmed member of it
	 *
	 * @param integer $userId
	 * @return Channel[]
	 */
	public function getOpenChannels(int $userId): array {
		global $wpdb;

		$openUserChannelsTable = Installer::getOpenUserChannelsTable();
		$channelMembersTable = Installer::getChannelMembersTable();
		$usersTable = Installer::getUsersTable();
		$table = Installer::getChannelsTable();
		$sql = sprintf(
			"SELECT c.*
			FROM %s uc
			LEFT JOIN %s c ON (c.id = uc.channel_id)
			WHERE uc.user_id = %d AND (c.type = %d OR EXISTS ( SELECT * FROM %s m LEFT JOIN %s u ON (m.user_id = u.id) WHERE m.user_id = uc.user_id AND m.channel_id = uc.channel_id AND m.confirmed = 1 ) )
			ORDER BY uc.sort ASC;",
			$openUserChannelsTable, $table, $userId, Channel::TYPE_PUBLIC, $channelMembersTable, $usersTable
		);

		$results = $wpdb->get_results($sql);
		$channels = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$channels[] = $this->populateData($result);
			}
		}

		return $channels;
	}

	/**
	 * Converts raw object into WiseChatChannel object.
	 *
	 * @param \stdClass $rawChannelData
	 *
	 * @return Channel
	 */
	protected function populateData(\stdClass $rawChannelData): object {
		$channel = new Channel();
		if ($rawChannelData->id > 0) {
			$channel->setId(intval($rawChannelData->id));
		}
		$channel->setName($rawChannelData->name);
		$channel->setPassword($rawChannelData->password);
		if ($rawChannelData->type > 0) {
			$channel->setType(intval($rawChannelData->type));
		}
		if ($rawChannelData->configuration) {
			$channel->setConfiguration(json_decode($rawChannelData->configuration, true));
		}

		return $channel;
	}

}