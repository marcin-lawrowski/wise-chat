<?php

/**
 * WiseChat messages DAO criteria
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatMessagesCriteria {
    const ORDER_DESCENDING = 'descending';
    const ORDER_ASCENDING = '';

    /**
     * @var string
     */
    private $channelName;

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
    private $maximumTime;

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
        $this->orderMode = self::ORDER_ASCENDING;
    }

    /**
     * @return WiseChatMessagesCriteria
     */
    public static function build() {
        return new WiseChatMessagesCriteria();
    }

    /**
     * @return string
     */
    public function getChannelName() {
        return $this->channelName;
    }

    /**
     * @param string $channelName
     *
     * @return WiseChatMessagesCriteria
     * @throws Exception If channel name is empty string
     */
    public function setChannelName($channelName) {
        $channelName = trim($channelName);
        if (strlen($channelName) == 0) {
            throw new Exception("Channel name cannot be empty");
        }
        $this->channelName = $channelName;

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
}