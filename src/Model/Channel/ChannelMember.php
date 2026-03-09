<?php

namespace Kainex\WiseChat\Model\Channel;

use Kainex\WiseChat\Model\User;

/**
 * WiseChat channel member model.
 */
class ChannelMember {

	const TYPE_OWNER = 1;
	const TYPE_MEMBER = 2;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $channelId;

    /** @var integer */
    private $type;

    /** @var User|null */
    private $user;

	/** @var bool */
	private $confirmed;

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
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @param int $type
	 */
	public function setType(int $type): void
	{
		$this->type = $type;
	}

	/**
	 * @return User|null
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @param User|null $user
	 */
	public function setUser($user): void
	{
		$this->user = $user;
	}

	public function isConfirmed(): bool {
		return $this->confirmed;
	}

	public function setConfirmed(bool $confirmed): void {
		$this->confirmed = $confirmed;
	}

}