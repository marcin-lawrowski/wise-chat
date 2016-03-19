<?php

/**
 * Wise Chat user abuses
 */
class WiseChatAbuses {
    const SESSION_KEY_ABUSES_COUNTER = 'wise_chat_ban_detector_counter';

    /**
     * @var WiseChatUserSessionDAO
     */
    private $userSessionDAO;

    /**
     * WiseChatAbuses constructor.
     */
    public function __construct() {
        $this->userSessionDAO = WiseChatContainer::getLazy('dao/user/WiseChatUserSessionDAO');
    }

    /**
     * Increments and returns abuses counter.
     * The counter is stored in user's session.
     *
     * @return integer
     */
    public function incrementAndGetAbusesCounter() {
        $key = self::SESSION_KEY_ABUSES_COUNTER;
        $counter = 1;

        if ($this->userSessionDAO->contains($key)) {
           $counter += $this->userSessionDAO->get(self::SESSION_KEY_ABUSES_COUNTER);
        }
        $this->userSessionDAO->set(self::SESSION_KEY_ABUSES_COUNTER, $counter);

        return $counter;
    }

    /**
     * Clears abuses counter. The counter is stored in user's session.
     *
     * @return null
     */
    public function clearAbusesCounter() {
        $this->userSessionDAO->set(self::SESSION_KEY_ABUSES_COUNTER, 0);
    }
}