<?php

namespace Kainex\WiseChat\Model\Channel;

/**
 * Wise Chat user's opened channels.
 */
class OpenUserChannel {

    private ?int $id;
    private int $channelId;
    private int $userId;
    private int $sort;

	public function __construct() {
		$this->id = null;
		$this->sort = 0;
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function setId(?int $id): void {
		$this->id = $id;
	}

	public function getChannelId(): int {
		return $this->channelId;
	}

	public function setChannelId(int $channelId): void {
		$this->channelId = $channelId;
	}

	public function getUserId(): int {
		return $this->userId;
	}

	public function setUserId(int $userId): void {
		$this->userId = $userId;
	}

	public function getSort(): int {
		return $this->sort;
	}

	public function setSort(int $sort): void {
		$this->sort = $sort;
	}

}