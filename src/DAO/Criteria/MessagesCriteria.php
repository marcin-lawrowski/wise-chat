<?php

namespace Kainex\WiseChat\DAO\Criteria;

use Exception;

/**
 * WiseChat messages DAO criteria
 *
 * @author Kainex <contact@kaine.pl>
 */
class MessagesCriteria {
    const ORDER_DESCENDING = 'descending';
    const ORDER_ASCENDING = '';

    /**
     * @var integer[]
     */
    private $channelIDs;

    /**
     * @var integer
     */
    private $userId;

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
        $this->orderMode = self::ORDER_ASCENDING;
        $this->channelIDs = array();
    }

    /**
     * @return MessagesCriteria
     */
    public static function build() {
        return new MessagesCriteria();
    }

    /**
     * @return integer[]
     */
    public function getChannelIDs() {
        return $this->channelIDs;
    }

    /**
     * @param integer[] $channelIDs
     *
     * @return MessagesCriteria
     * @throws Exception If channel name is empty
     */
    public function setChannelIDs($channelIDs) {
        if (count($channelIDs) == 0) {
            throw new Exception("Channel IDs cannot be empty");
        }
        $this->channelIDs = $channelIDs;

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
     * @return MessagesCriteria
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
     * @return MessagesCriteria
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
     * @return MessagesCriteria
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
     * @return MessagesCriteria
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
     * @return MessagesCriteria
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
     * @return MessagesCriteria
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
     * @return MessagesCriteria
     */
    public function setUserId($userId) {
        $this->userId = $userId;
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
     * @return MessagesCriteria
     */
    public function setMinimumTime($minimumTime) {
        $this->minimumTime = $minimumTime;
        return $this;
    }

}