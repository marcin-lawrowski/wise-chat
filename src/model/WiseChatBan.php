<?php

/**
 * WiseChat ban model.
 */
class WiseChatBan {
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
}