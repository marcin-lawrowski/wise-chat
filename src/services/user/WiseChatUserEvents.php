<?php

/**
 * Wise Chat user events service.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatUserEvents {
    const SESSION_KEY_EVENT_TIME = 'wise_chat_activity_time';

    /**
     * @var WiseChatUserSessionDAO
     */
    private $userSessionDAO;

    /**
     * @var array Events thresholds in seconds
     */
    private $eventTimeThresholds = array(
        'usersList' => 20,
        'ping' => 40,
        'default' => 120
    );

    /**
     * WiseChatUserEvents constructor.
     */
    public function __construct() {
        $this->userSessionDAO = WiseChatContainer::getLazy('dao/user/WiseChatUserSessionDAO');
    }

    /**
     * Checks whether it is time to trigger an event identified by group and id.
     *
     * @param string $group Event group
     * @param string $id Event id
     *
     * @return boolean
     */
    public function shouldTriggerEvent($group, $id) {
        $sessionKey = self::SESSION_KEY_EVENT_TIME.md5($group).'_'.md5($id);

        if (!$this->userSessionDAO->contains($sessionKey)) {
            $this->userSessionDAO->set($sessionKey, time());
            return true;
        } else {
            $diff = time() - $this->userSessionDAO->get($sessionKey);
            if ($diff > $this->getEventTimeThreshold($group)) {
                $this->userSessionDAO->set($sessionKey, time());
                return true;
            }
        }

        return false;
    }

    /**
     * Resets tracking of the given event. Resets all events if event ID equals null.
     *
     * @param string $group Event group
     * @param string|null $id Event id
     *
     * @return null
     */
    public function resetEventTracker($group, $id = null) {
        $prefix = self::SESSION_KEY_EVENT_TIME.md5($group).'_';
        if ($id !== null) {
            $sessionKey = $prefix.md5($id);

            if ($this->userSessionDAO->contains($sessionKey)) {
                $this->userSessionDAO->drop($sessionKey);
            }
        } else {
            $this->userSessionDAO->dropAllByPrefix($prefix);
        }
    }

    /**
     * Returns time threshold for given event group.
     *
     * @param string $eventGroup Name of the event group
     *
     * @return integer
     */
    private function getEventTimeThreshold($eventGroup) {
        if (array_key_exists($eventGroup, $this->eventTimeThresholds)) {
            return $this->eventTimeThresholds[$eventGroup];
        } else {
            return $this->eventTimeThresholds['default'];
        }
    }
}