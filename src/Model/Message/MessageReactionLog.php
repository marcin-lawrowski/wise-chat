<?php

namespace Kainex\WiseChat\Model\Message;

use Kainex\WiseChat\Model\User;

/**
 * WiseChat message reactions log model.
 */
class MessageReactionLog {

	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var integer
	 */
	private $reactionId;

	/**
	 * @var integer
	 */
	private $userId;

	/**
	 * @var integer
	 */
	private $messageId;

	/**
	 * @var integer
	 */
	private $time;

	private ?User $user;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getReactionId()
	{
		return $this->reactionId;
	}

	/**
	 * @param int $reactionId
	 */
	public function setReactionId($reactionId)
	{
		$this->reactionId = $reactionId;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param int $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @return int
	 */
	public function getMessageId()
	{
		return $this->messageId;
	}

	/**
	 * @param int $messageId
	 */
	public function setMessageId($messageId)
	{
		$this->messageId = $messageId;
	}

	/**
	 * @return int
	 */
	public function getTime()
	{
		return $this->time;
	}

	/**
	 * @param int $time
	 */
	public function setTime($time)
	{
		$this->time = $time;
	}

	public function getUser(): ?User {
		return $this->user;
	}

	public function setUser(?User $user): void {
		$this->user = $user;
	}

}