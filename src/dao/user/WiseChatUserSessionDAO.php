<?php

/**
 * Provides object-oriented access to the PHP session.
 */
class WiseChatUserSessionDAO {

    /**
     * WiseChatUserSessionDAO constructor.
     */
    public function __construct() {
        $this->ensureSessionIsRunning();
    }

    /**
     * @return string
     */
    public function getSessionId() {
        return session_id();
    }

    /**
     * Returns session variable by key.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key) {
        if (is_array($_SESSION) && array_key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }

        return null;
    }

    /**
     * Drops all session variables by key.
     *
     * @param string $prefix
     *
     * @return null
     */
    public function dropAllByPrefix($prefix) {
        if (is_array($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                if (strpos($key, $prefix) === 0) {
                    unset($_SESSION[$key]);
                }
            }
        }
    }

    /**
     * Checks if session variable exists.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function contains($key) {
        return is_array($_SESSION) && array_key_exists($key, $_SESSION);
    }

    /**
     * Sets session variable.
     *
     * @param string $key
     * @param string $value
     *
     * @return null
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Drops session variable.
     *
     * @param string $key
     *
     * @return null
     */
    public function drop($key) {
        unset($_SESSION[$key]);
    }

    /**
     * Ensures that PHP session is running.
     *
     * @return null
     */
    private function ensureSessionIsRunning() {
        if (session_id() == '' || !isset($_SESSION)) {
            session_start();
        }
    }
}