<?php

/**
 * Wise Chat channel-to-user association
 */
class WiseChatChannelUser {
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
     * @var WiseChatUser
     */
    private $user;

    /**
     * @var boolean
     */
    private $active;

    /**
     * @var integer
     */
    private $lastActivityTime;

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
    public function getChannelId() {
        return $this->channelId;
    }

    /**
     * @param integer $channelId
     */
    public function setChannelId($channelId) {
        $this->channelId = $channelId;
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
     * @return WiseChatUser|null
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param WiseChatUser|null $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * @return boolean
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active) {
        $this->active = $active;
    }

    /**
     * @return int
     */
    public function getLastActivityTime() {
        return $this->lastActivityTime;
    }

    /**
     * @param int $lastActivityTime
     */
    public function setLastActivityTime($lastActivityTime) {
        $this->lastActivityTime = $lastActivityTime;
    }
}