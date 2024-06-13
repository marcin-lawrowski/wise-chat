<?php

/**
 * Wise Chat pending chats
 */
class WiseChatPendingChat {
	/**
	 * @var integer
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
	private $recipientId;

	/**
	 * @var WiseChatUser
	 */
	private $user;

	/**
	 * @var WiseChatUser
	 */
	private $recipient;

	/**
	 * @var integer
	 */
	private $messageId;

	/**
	 * @var boolean
	 */
	private $checked;

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
	public function getRecipientId()
	{
		return $this->recipientId;
	}

	/**
	 * @param int $recipientId
	 */
	public function setRecipientId($recipientId)
	{
		$this->recipientId = $recipientId;
	}

	/**
	 * @return WiseChatUser
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @param WiseChatUser $user
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}

	/**
	 * @return WiseChatUser
	 */
	public function getRecipient()
	{
		return $this->recipient;
	}

	/**
	 * @param WiseChatUser $recipient
	 */
	public function setRecipient($recipient)
	{
		$this->recipient = $recipient;
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
	 * @return boolean
	 */
	public function isChecked()
	{
		return $this->checked;
	}

	/**
	 * @param boolean $checked
	 */
	public function setChecked($checked)
	{
		$this->checked = $checked;
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