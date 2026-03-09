<?php

namespace Kainex\WiseChat\Model;

/**
 * Wise Chat user feed model.
 */
class UserFeed {

	/**
	 * @var integer
	 */
	private $id;

	 /**
     * @var integer
     */
    private $userId;

    /** @var integer */
    private $targetId;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var array
	 */
	private $data;

	/**
	 * @var bool
	 */
	private $seen;

	 /**
     * @var \DateTime
     */
    private $created;

	/**
	 * WiseChatUserFeed constructor.
	 */
	public function __construct()
	{
		$this->data = array();
		$this->created = new \DateTime();
		$this->seen = false;
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
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param array $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param \DateTime $created
	 */
	public function setCreated($created)
	{
		$this->created = $created;
	}

	/**
	 * @return bool
	 */
	public function isSeen()
	{
		return $this->seen;
	}

	/**
	 * @param bool $seen
	 */
	public function setSeen($seen)
	{
		$this->seen = $seen;
	}

	/**
	 * @return int
	 */
	public function getTargetId()
	{
		return $this->targetId;
	}

	/**
	 * @param int $targetId
	 */
	public function setTargetId($targetId)
	{
		$this->targetId = $targetId;
	}

}