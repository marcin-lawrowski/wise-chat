<?php

/**
 * Wise Chat user abuses
 */
class WiseChatAbuses {
	const PROPERTY_NAME = 'ban_detector_counter';

	/**
	 * @var WiseChatUserService
	 */
	private $userService;

	/**
	 * WiseChatAbuses constructor.
	 */
	public function __construct() {
		$this->userService = WiseChatContainer::get('services/user/WiseChatUserService');
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