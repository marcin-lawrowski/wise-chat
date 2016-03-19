<?php

/**
 * Wise Chat message model.
 */
class WiseChatMessage {
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $admin;

    /**
     * @var string User name stored with the message
     */
    private $userName;

    /**
     * @var string Channel name stored with the message
     */
    private $channelName;

    /**
     * @var integer WordPress user ID
     */
    private $wordPressUserId;

    /**
     * @var integer Chat plugin user ID
     */
    private $userId;

    /**
     * @var WiseChatUser Chat plugin user
     */
    private $user;

    /**
     * @var string
     */
    private $text;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var integer
     */
    private $time;

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
     * @return boolean
     */
    public function isAdmin() {
        return $this->admin;
    }

    /**
     * @param boolean $admin
     */
    public function setAdmin($admin) {
        $this->admin = $admin;
    }

    /**
     * @return string
     */
    public function getUserName() {
        return $this->userName;
    }

    /**
     * @param string $userName
     */
    public function setUserName($userName) {
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getChannelName() {
        return $this->channelName;
    }

    /**
     * @param string $channelName
     */
    public function setChannelName($channelName) {
        $this->channelName = $channelName;
    }

    /**
     * @return int
     */
    public function getWordPressUserId() {
        return $this->wordPressUserId;
    }

    /**
     * @param int $wordPressUserId
     */
    public function setWordPressUserId($wordPressUserId) {
        $this->wordPressUserId = $wordPressUserId;
    }

    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId) {
        $this->userId = $userId;
    }

    /**
     * @return WiseChatUser
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param WiseChatUser $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text) {
        $this->text = $text;
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
}