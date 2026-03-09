<?php

namespace Kainex\WiseChat\Services\User;

use Exception;
use Kainex\WiseChat\Model\Channel\Channel;

/**
 * Wise Chat user authorization service.
 */
class AuthorizationService {
    const PROPERTY_NAME = 'channel_authorization';

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
	 * Grants access to the channel for the current user.
	 *
	 * @param Channel $channel
	 * @throws Exception
	 */
    public function markAuthorizedForChannel($channel) {
        $grants = $this->userService->getProperty(self::PROPERTY_NAME);
        if (!is_array($grants)) {
            $grants = array();
        }

        $grants[$channel->getId()] = $channel->getPassword();
        $this->userService->setProperty(self::PROPERTY_NAME, $grants);
    }
}