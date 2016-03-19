<?php

/**
 * WiseChat actions model.
 */
class WiseChatAction {
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
    private $userId;

    /**
     * @var array
     */
    private $command;

    /**
     * WiseChatAction constructor.
     */
    public function __construct() {
        $this->command = array();
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime($time) {
        $this->time = $time;
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

    /**
     * @return array
     */
    public function getCommand() {
        return $this->command;
    }

    /**
     * @param array $command
     */
    public function setCommand($command) {
        $this->command = $command;
    }
}