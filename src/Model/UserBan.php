<?php

namespace Kainex\WiseChat\Model;

/**
 * WiseChat ban.
 */
class UserBan {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var integer
	 */
	private $created;

	/**
	 * @var string
	 */
	private $ip;

	/**
	 * @var integer
	 */
	private $userId;

	/**
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param integer $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return integer
	 */
	public function getCreated() {
		return $this->created;
	}

	/**
	 * @param integer $created
	 */
	public function setCreated($created) {
		$this->created = $created;
	}

	/**
	 * @return string
	 */
	public function getIp() {
		return $this->ip;
	}

	/**
	 * @param string $ip
	 */
	public function setIp($ip) {
		$this->ip = $ip;
	}

	/**
	 * @return integer
	 */
	public function getUserId() {
		return $this->userId;
	}

	/**
	 * @param integer $userId
	 */
	public function setUserId($userId) {
		$this->userId = $userId;
	}

}