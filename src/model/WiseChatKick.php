<?php

/**
 * WiseChat kick model.
 */
class WiseChatKick {
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
	private $lastUserName;

	/**
	 * @var string
	 */
	private $ip;

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
	 * @return string
	 */
	public function getLastUserName() {
		return $this->lastUserName;
	}

	/**
	 * @param string $lastUserName
	 */
	public function setLastUserName($lastUserName) {
		$this->lastUserName = $lastUserName;
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
}