<?php

namespace Kainex\WiseChat\Model;

/**
 * WiseChat mute.
 */
class UserMute {
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $time;

    /**
     * @var integer
     */
    private $created;

	/**
     * @var integer
     */
    private $userId;

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
     * @return integer
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * @param integer $time
     */
    public function setTime($time) {
        $this->time = $time;
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