<?php

/**
 * WiseChat messages DAO criteria
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMessagesCriteria {
    const ORDER_DESCENDING = 'descending';
    const ORDER_ASCENDING = '';

    /**
     * @var array
     */
    private $channelNames;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var integer
     */
    private $offsetId;

    /**
     * @var boolean
     */
    private $includeAdminMessages;

    /**
     * @var integer
     */
    private $recipientOrSenderId;

    /**
     * @var boolean
     */
    private $privateMessages;

    /**
     * @var integer
     */
    private $maximumTime;

    /**
     * @var integer
     */
    private $maximumMessageId;

    /**
     * @var integer
     */
    private $minimumTime;

    /**
     * @var integer
     */
    private $limit;

    /**
     * @var string
     */
    private $orderMode;

    /**
     * WiseChatMessagesCriteria constructor.
     */
    public function __construct() {
        $this->includeAdminMessages = false;
        $this->privateMessages = false;
        $this->orderMode = self::ORDER_ASCENDING;
        $this->channelNames = array();
    }

    /**
     * @return WiseChatMessagesCriteria
     */
    public static function build() {
        return new WiseChatMessagesCriteria();
    }

    /**
     * @return array
     */
    public function getChannelNames() {
        return $this->channelNames;
    }

    /**
     * @param array $channelNames
     *
     * @return WiseChatMessagesCriteria
     * @throws Exception If channel name is empty
     */
    public function setChannelNames($channelNames) {
        if (count($channelNames) == 0) {
            throw new Exception("Channel names cannot be empty");
        }
        $this->channelNames = $channelNames;

        return $this;
    }

    /**
     * @return integer
     */
    public function getOffsetId() {
        return $this->offsetId;
    }

    /**
     * @param integer $offsetId
     *
     * @return WiseChatMessagesCriteria
     */
    public function setOffsetId($offsetId) {
        $this->offsetId = $offsetId;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isIncludeAdminMessages() {
        return $this->includeAdminMessages;
    }

    /**
     * @param boolean $includeAdminMessages
     *
     * @return WiseChatMessagesCriteria
     */
    public function setIncludeAdminMessages($includeAdminMessages) {
        $this->includeAdminMessages = $includeAdminMessages;
        return $this;
    }

    /**
     * @return integer
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @param integer $limit
     *
     * @return WiseChatMessagesCriteria
     */
    public function setLimit($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderMode() {
        return $this->orderMode;
    }

    /**
     * @param string $orderMode
     *
     * @return WiseChatMessagesCriteria
     */
    public function setOrderMode($orderMode) {
        $this->orderMode = $orderMode;
        return $this;
    }

    /**
     * @return integer
     */
    public function getMaximumTime() {
        return $this->maximumTime;
    }

    /**
     * @param integer $maximumTime
     *
     * @return WiseChatMessagesCriteria
     */
    public function setMaximumTime($maximumTime) {
        $this->maximumTime = $maximumTime;
        return $this;
    }

     /**
     * @return integer
     */
    public function getMaximumMessageId() {
        return $this->maximumMessageId;
    }

    /**
     * @param integer $maximumMessageId
     *
     * @return WiseChatMessagesCriteria
     */
    public function setMaximumMessageId($maximumMessageId) {
        $this->maximumMessageId = $maximumMessageId;
        return $this;
    }

    /**
     * @return integer
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param integer $userId
     *
     * @return WiseChatMessagesCriteria
     */
    public function setUserId($userId) {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return WiseChatMessagesCriteria
     */
    public function setIp($ip) {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinimumTime()
    {
        return $this->minimumTime;
    }

    /**
     * @param int $minimumTime
     *
     * @return WiseChatMessagesCriteria
     */
    public function setMinimumTime($minimumTime) {
        $this->minimumTime = $minimumTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getRecipientOrSenderId() {
        return $this->recipientOrSenderId;
    }

    /**
     * @param int $recipientOrSenderId
     */
    public function setRecipientOrSenderId($recipientOrSenderId) {
        $this->recipientOrSenderId = $recipientOrSenderId;
    }

    /**
     * @return boolean
     */
    public function isIncludeOnlyPrivateMessages() {
        return $this->privateMessages;
    }

    /**
     * @param boolean $privateMessages
     *
     * @return WiseChatMessagesCriteria
     */
    public function setIncludeOnlyPrivateMessages($privateMessages) {
        $this->privateMessages = $privateMessages;
        return $this;
    }
}