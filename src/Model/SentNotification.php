<?php

namespace Kainex\WiseChat\Model;

/**
 * Wise Chat sent notification model.
 */
class SentNotification {

	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	private $notificationId;

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
	private $sentTime;

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
	 * @return string
	 */
	public function getNotificationId()
	{
		return $this->notificationId;
	}

	/**
	 * @param string $notificationId
	 */
	public function setNotificationId($notificationId)
	{
		$this->notificationId = $notificationId;
	}

	/**
	 * @return int
	 */
	public function getChannelId()
	{
		return $this->channelId;
	}

	/**
	 * @param int $channelId
	 */
	public function setChannelId($channelId)
	{
		$this->channelId = $channelId;
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
	public function getSentTime()
	{
		return $this->sentTime;
	}

	/**
	 * @param int $sentTime
	 */
	public function setSentTime($sentTime)
	{
		$this->sentTime = $sentTime;
	}

}