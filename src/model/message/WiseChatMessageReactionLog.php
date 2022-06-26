<?php

/**
 * WiseChat message reactions log model.
 */
class WiseChatMessageReactionLog {

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

}