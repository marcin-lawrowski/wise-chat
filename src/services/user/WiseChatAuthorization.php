<?php

/**
 * Wise Chat user authorization service.
 */
class WiseChatAuthorization {
    const PROPERTY_NAME = 'channel_authorization';

    /**
     * @var WiseChatUserService
     */
    private $userService;

    /**
     * @var WiseChatOptions
     */
    private $options;

    /**
     * WiseChatAuthorization constructor.
     */
    public function __construct() {
        $this->options = WiseChatOptions::getInstance();
        $this->userService = WiseChatContainer::get('services/user/WiseChatUserService');
    }

	/**
	 * Determines whether the current user is authorized to access the channel.
	 *
	 * @param WiseChatChannel $channel
	 *
	 * @return boolean
	 * @throws Exception
	 */
    public function isUserAuthorizedForChannel($channel) {
    	if (strlen($channel->getPassword()) === 0) {
    		return true;
	    }

        $grants = $this->userService->getProperty(self::PROPERTY_NAME);

        return is_array($grants) && array_key_exists($channel->getId(), $grants) && $grants[$channel->getId()] === $channel->getPassword();
    }

	/**
	 * Grants access to the channel for the current user.
	 *
	 * @param WiseChatChannel $channel
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