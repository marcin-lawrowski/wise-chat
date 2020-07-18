<?php

/**
 * Wise Chat user events service.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatUserEvents {
	const PROPERTY_NAME_PREFIX = 'activity_time';

	/**
	 * @var WiseChatUserService
	 */
	private $userService;

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
	    $this->userService = WiseChatContainer::get('services/user/WiseChatUserService');
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
		$propertyKey = self::PROPERTY_NAME_PREFIX.md5($group).'_'.md5($id);

		if ($this->userService->getProperty($propertyKey) === null) {
			$this->userService->setProperty($propertyKey, time());
			return true;
		} else {
			$diff = time() - $this->userService->getProperty($propertyKey);
			if ($diff > $this->getEventTimeThreshold($group)) {
				$this->userService->setProperty($propertyKey, time());
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
		$prefix = self::PROPERTY_NAME_PREFIX.md5($group).'_';
		if ($id !== null) {
			$propertyKey = $prefix.md5($id);

			if ($this->userService->getProperty($propertyKey) !== null) {
				$this->userService->setProperty($propertyKey, null);
			}
		} else {
			$this->userService->unsetPropertiesByPrefix($prefix);
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