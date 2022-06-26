<?php

/**
 * WiseChat message reaction model.
 */
class WiseChatMessageReaction {

	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var integer
	 */
	private $reaction1;

	/**
	 * @var integer
	 */
	private $reaction2;

	/**
	 * @var integer
	 */
	private $reaction3;

	/**
	 * @var integer
	 */
	private $reaction4;

	/**
	 * @var integer
	 */
	private $reaction5;

	/**
	 * @var integer
	 */
	private $reaction6;

	/**
	 * @var integer
	 */
	private $reaction7;

	/**
	 * @var integer
	 */
	private $messageId;

	/**
	 * @var integer
	 */
	private $updated;

	/**
	 * WiseChatMessageReaction constructor.
	 */
	public function __construct() {
		$this->reaction1 = 0;
		$this->reaction2 = 0;
		$this->reaction3 = 0;
		$this->reaction4 = 0;
		$this->reaction5 = 0;
		$this->reaction6 = 0;
		$this->reaction7 = 0;
	}

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
	public function getReaction1()
	{
		return $this->reaction1;
	}

	/**
	 * @param int $reaction1
	 */
	public function setReaction1($reaction1)
	{
		$this->reaction1 = $reaction1;
	}

	/**
	 * @return int
	 */
	public function getReaction2()
	{
		return $this->reaction2;
	}

	/**
	 * @param int $reaction2
	 */
	public function setReaction2($reaction2)
	{
		$this->reaction2 = $reaction2;
	}

	/**
	 * @return int
	 */
	public function getReaction3()
	{
		return $this->reaction3;
	}

	/**
	 * @param int $reaction3
	 */
	public function setReaction3($reaction3)
	{
		$this->reaction3 = $reaction3;
	}

	/**
	 * @return int
	 */
	public function getReaction4()
	{
		return $this->reaction4;
	}

	/**
	 * @param int $reaction4
	 */
	public function setReaction4($reaction4)
	{
		$this->reaction4 = $reaction4;
	}

	/**
	 * @return int
	 */
	public function getReaction5()
	{
		return $this->reaction5;
	}

	/**
	 * @param int $reaction5
	 */
	public function setReaction5($reaction5)
	{
		$this->reaction5 = $reaction5;
	}

	/**
	 * @return int
	 */
	public function getReaction6()
	{
		return $this->reaction6;
	}

	/**
	 * @param int $reaction6
	 */
	public function setReaction6($reaction6)
	{
		$this->reaction6 = $reaction6;
	}

	/**
	 * @return int
	 */
	public function getReaction7()
	{
		return $this->reaction7;
	}

	/**
	 * @param int $reaction7
	 */
	public function setReaction7($reaction7)
	{
		$this->reaction7 = $reaction7;
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
	public function getUpdated()
	{
		return $this->updated;
	}

	/**
	 * @param int $updated
	 */
	public function setUpdated($updated)
	{
		$this->updated = $updated;
	}

}