<?php

namespace Kainex\WiseChat\Model;

/**
 * Wise Chat user channels list.
 */
class UserChannel {

	/**
	* @var integer|null
	*/
    private $id;

    /**
     * @var integer
     */
    private $channelId;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $sort;

	/**
	 * @return int|null
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int|null $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getChannelId(): int
	{
		return $this->channelId;
	}

	/**
	 * @param int $channelId
	 */
	public function setChannelId(int $channelId): void
	{
		$this->channelId = $channelId;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->userId;
	}

	/**
	 * @param int $userId
	 */
	public function setUserId(int $userId): void
	{
		$this->userId = $userId;
	}

	/**
	 * @return int
	 */
	public function getSort(): int
	{
		return $this->sort;
	}

	/**
	 * @param int $sort
	 */
	public function setSort(int $sort): void
	{
		$this->sort = $sort;
	}

}