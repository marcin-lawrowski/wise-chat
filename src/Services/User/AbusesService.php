<?php

namespace Kainex\WiseChat\Services\User;

/**
 * Wise Chat user abuses
 */
class AbusesService {
    const PROPERTY_NAME = 'ban_detector_counter';

    /**
     * @var UserService
     */
    private $userService;

	/**
	 * @param UserService $userService
	 */
	public function __construct(UserService $userService) {
		$this->userService = $userService;
	}

	/**
     * Increments and returns the abuses counter.
     *
     * @return integer
     */
    public function incrementAndGetAbusesCounter() {
        $counter = $this->userService->getProperty(self::PROPERTY_NAME);
        if ($counter === null) {
            $counter = 0;
        }
        $counter++;

        $this->userService->setProperty(self::PROPERTY_NAME, $counter);

        return $counter;
    }

    /**
     * Clears the abuses counter.
     *
     * @return null
     */
    public function clearAbusesCounter() {
        $this->userService->setProperty(self::PROPERTY_NAME, 0);
    }
}