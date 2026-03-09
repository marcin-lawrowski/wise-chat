<?php

namespace Kainex\WiseChat\Model\Message;

use Kainex\WiseChat\Model\User;

/**
 * Wise Chat message model.
 */
class Message {
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $admin;

	/** @var integer */
	private $channelId;

    /**
     * @var integer WordPress user ID
     */
    private $wordPressUserId;

    /**
     * @var integer Chat plugin user ID
     */
    private $userId;

    /**
     * @var boolean
     */
    private $hidden;

    /**
     * @var integer
     */
    private $replyToMessageId;

    /**
     * @var User Chat plugin user
     */
    private $user;

    /**
     * @var string
     */
    private $text;

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
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param User $user
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
     * @return int
     */
    public function getReplyToMessageId() {
        return $this->replyToMessageId;
    }

    /**
     * @param int $replyToMessageId
     */
    public function setReplyToMessageId($replyToMessageId) {
        $this->replyToMessageId = $replyToMessageId;
    }

    /**
     * @return boolean
     */
    public function isHidden() {
        return $this->hidden;
    }

    /**
     * @param boolean $hidden
     */
    public function setHidden($hidden) {
        $this->hidden = $hidden;
    }

	public function getChannelId() {
		return $this->channelId;
	}

	public function setChannelId($channelId) {
		$this->channelId = $channelId;
	}

    /**
     * Returns a clone of the current message
     *
     * @returns Message
     */
    public function getClone() {
        $clone = new Message();

        $clone->setAdmin($this->isAdmin());
        $clone->setChannelId($this->getChannelId());
        $clone->setWordPressUserId($this->getWordPressUserId());
        $clone->setUserId($this->getUserId());
        $clone->setHidden($this->isHidden());
        $clone->setReplyToMessageId($this->getReplyToMessageId());
        $clone->setText($this->getText());
        $clone->setTime($this->getTime());

        return $clone;
    }
}