<?php

namespace Kainex\WiseChat\DAO\Channels;

use Exception;
use Kainex\WiseChat\DAO\AbstractDAO;
use Kainex\WiseChat\Model\Channel\OpenUserChannel;
use Kainex\WiseChat\Installer;

/**
 * Wise Chat user's opened channels.
 *
 * @author Kainex <contact@kainex.pl>
 */
class OpenUserChannelsDAO extends AbstractDAO {

	protected function getTableName(): string {
		return Installer::getOpenUserChannelsTable();
	}

	/**
	 * Creates or updates the channel member and returns it.
	 *
	 * @param OpenUserChannel $userChannel
	 *
	 * @return OpenUserChannel
	 * @throws Exception On validation error
	 */
	public function save(OpenUserChannel $userChannel): OpenUserChannel {
		if ($userChannel->getUserId() === null) {
			throw new Exception('Please provide user ID');
		}
		if ($userChannel->getChannelId() === null) {
			throw new Exception('Please provide channel ID');
		}

		$userChannel->setId($this->persist([
			'id' => $userChannel->getId(),
			'user_id' => $userChannel->getUserId(),
			'channel_id' => $userChannel->getChannelId(),
			'sort' => $userChannel->getSort()
		]));

		return $userChannel;
	}

	/**
	 * @param integer $userId
	 *
	 * @return OpenUserChannel[]
	 */
	public function getByUserId(int $userId): array {
		$rawArray = $this->getAllBy(['user_id' => [$userId, '%d']], ['sort', 'asc']);

		$userChannels = [];
		foreach ($rawArray as $result) {
			$userChannels[] = $this->populateData($result);
		}

		return $userChannels;
	}

	/**
	 * Returns by user ID and channel ID.
	 *
	 * @param integer $userId
	 * @param integer $channelId
	 * @return OpenUserChannel[]
	 */
	public function getAllByUserIdAndChannelId(int $userId, int $channelId): array {
		$rawArray = $this->getAllBy(['channel_id' => [$channelId, '%d'], 'user_id' => [$userId, '%d']], ['sort', 'asc']);

		$userChannels = [];
		foreach ($rawArray as $result) {
			$userChannels[] = $this->populateData($result);
		}

		return $userChannels;
	}

	/**
	 * Returns max sort by user ID.
	 *
	 * @param integer $userId
	 *
	 * @return integer
	 */
	public function getMaxSortUserId(int $userId): int {
		global $wpdb;

		$sql = $wpdb->prepare('SELECT max(`sort`) AS maxSort FROM %i WHERE `user_id` = %d;', $this->getTableName(), $userId);
		$results = $wpdb->get_results($sql);
		return is_array($results) ? (int) $results[0]->maxSort : 0;
	}

	/**
	 * Converts raw object into WiseChatOpenUserChannel object.
	 *
	 * @param \stdClass $rawRow
	 *
	 * @return OpenUserChannel
	 */
	protected function populateData(\stdClass $rawRow): OpenUserChannel {
		$channel = new OpenUserChannel();
		if ($rawRow->id > 0) {
			$channel->setId(intval($rawRow->id));
		}
		$channel->setUserId($rawRow->user_id);
		$channel->setChannelId($rawRow->channel_id);
		$channel->setSort(intval($rawRow->sort));

		return $channel;
	}

	/**
	 * Deletes the channel from all lists.
	 *
	 * @param int $channelId
	 * @return void
	 */
	public function deleteByChannelId(int $channelId) {
		$this->deleteBy(['channel_id' => [$channelId, '%d']]);
	}

	/**
	 * Deletes the channel from open lists.
	 *
	 * @param int $channelId
	 * @return void
	 */
	public function deleteByChannelIdAndUserId(int $channelId, int $userId) {
		$this->deleteBy(['channel_id' => [$channelId, '%d'], 'user_id' => [$userId, '%d']]);
	}

	public function deleteAll(): void {
		$this->deleteBy(['id' => [0, '%d', '>']]);
	}

}