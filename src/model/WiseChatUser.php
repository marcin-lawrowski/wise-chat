<?php

/**
 * Wise Chat user model.
 */
class WiseChatUser {
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer WordPress user ID
     */
    private $wordPressId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $ip;

    /**
     * WiseChatUser constructor.
     */
    public function __construct() {
        $this->data = array();
    }

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
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     */
    public function setSessionId($sessionId) {
        $this->sessionId = $sessionId;
    }

    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Sets custom data property.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setDataProperty($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * Returns custom data property.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getDataProperty($key) {
        if (is_array($this->data) && array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * @return integer
     */
    public function getWordPressId() {
        return $this->wordPressId;
    }

    /**
     * @param integer $wordPressId
     */
    public function setWordPressId($wordPressId) {
        $this->wordPressId = $wordPressId;
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