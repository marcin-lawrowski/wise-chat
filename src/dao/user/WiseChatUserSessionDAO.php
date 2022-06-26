<?php

/**
 * Provides object-oriented access to PHP session.
 */
class WiseChatUserSessionDAO {

    /**
     * Returns session variable by key.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key) {
        if (!$this->isRunning()) {
            $this->start();
        }

        return is_array($_SESSION) && array_key_exists($key, $_SESSION) ? $_SESSION[$key] : null;
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
        if (!$this->isRunning()) {
            $this->start();
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Starts PHP session.
     * @throws \Exception
     */
    public function start() {
        if (!headers_sent()) {
            session_start();
            if (!$this->isRunning()) {
                throw new Exception('Session could not be started');
            }
        } else {
            throw new Exception('Session could not be started because headers have been sent');
        }
    }

    /**
     * Closes PHP session.
     * @throws \Exception
     */
    public function close() {
        if ($this->isRunning()) {
            session_write_close();
        }
    }

    /**
     * Determines if PHP session is running.
     *
     * @return boolean
     */
    public function isRunning() {
        if (session_id() == '' || !isset($_SESSION)) {
            return false;
        }

        return true;
    }
}