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
	 * @var WiseChatHttpRequestService
	 */
	private $httpRequestService;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	 * @var WiseChatChannelsDAO
	 */
	private $channelsDAO;

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
	    $this->httpRequestService = WiseChatContainer::getLazy('services/WiseChatHttpRequestService');
	    $this->channelsDAO = WiseChatContainer::getLazy('dao/WiseChatChannelsDAO');
	    $this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
    }

	/**
	 * @param WiseChatChannel $channel
	 * @return string
	 */
	public function getChannelPasswordActionLoginURL($channel) {
		$parameters = array(
			'wcAuthorizationAction' => 'cp',
			'wcChannelId' => $channel->getId(),
			'nonce' => wp_create_nonce('cp'.$channel->getId().$this->httpRequestService->getRemoteAddress())
		);

		return $this->httpRequestService->getCurrentURLWithParameters($parameters);
	}

	public function handleAuthorization() {
		// validate parameters:
		$method = $this->httpRequestService->getParam('wcAuthorizationAction');
		if ($method === null) {
			return;
		}
		$nonce = $this->httpRequestService->getParam('nonce');
		$channelId = $this->httpRequestService->getParam('wcChannelId');
		if ($method !== null && ($nonce === null || $channelId === null)) {
			$this->httpRequestService->reload(array('wcAuthorizationAction', 'nonce', 'wcChannelId'));
		}

		// verify nonce:
		$nonceAction = null;
		switch ($method) {
			case 'cp':
				$nonceAction = $method.$channelId.$this->httpRequestService->getRemoteAddress();
				break;
			default:
				$this->httpRequestService->reload(array('wcAuthorizationAction', 'nonce', 'wcChannelId'));
		}
		if (!wp_verify_nonce($nonce, $nonceAction)) {
			$this->httpRequestService->setRequestParam('authorizationError', 'Bad request');
			return;
		}

		try {
			$password = $this->httpRequestService->getPostParam('wcChannelPassword');
			$channel = $this->channelsDAO->get($channelId);
			if ($channel === null) {
				throw new Exception('Unknown channel');
			}

			if ($channel->getPassword() === md5($password)) {
				if (!$this->authentication->isAuthenticated()) {
					$user = $this->authentication->authenticateAnonymously();
				}

				$this->markAuthorizedForChannel($channel);
			} else {
				throw new Exception($this->options->getOption('message_error_9', __('Invalid password.', 'wise-chat')));
			}

			$this->httpRequestService->reload(array('wcAuthorizationAction', 'nonce', 'wcChannelId'));
		} catch (Exception $e) {
			$this->httpRequestService->setRequestParam('authorizationError', $e->getMessage());
		}
	}

	/**
	 * Determines whether the current user is authorized to access the channel.
	 *
	 * @param WiseChatChannel $channel
	 *
	 * @return boolean
	 */
	public function isUserAuthorizedForChannel($channel) {
		$grants = $this->userService->getProperty(self::PROPERTY_NAME);

		return is_array($grants) && array_key_exists($channel->getId(), $grants);
	}

	/**
	 * Grants access to the channel for the current user.
	 *
	 * @param WiseChatChannel $channel
	 *
	 * @return null
	 */
	public function markAuthorizedForChannel($channel) {
		$grants = $this->userService->getProperty(self::PROPERTY_NAME);
		if (!is_array($grants)) {
			$grants = array();
		}

		$grants[$channel->getId()] = true;
		$this->userService->setProperty(self::PROPERTY_NAME, $grants);
	}
}