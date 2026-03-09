<?php

namespace Kainex\WiseChat\Endpoints\Utils;

use Kainex\WiseChat\Exceptions\UnauthorizedAccessException;
use Kainex\WiseChat\Options;
use Kainex\WiseChat\Services\ChatService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\UserBansService;

class EndpointUtils {

	private Options $options;
	private UserBansService $bansService;
	private ChatService $service;
	private AuthenticationService $authentication;

	/**
	 * @param Options $options
	 * @param UserBansService $bansService
	 * @param ChatService $service
	 * @param AuthenticationService $authentication
	 */
	public function __construct(Options $options, UserBansService $bansService, \Kainex\WiseChat\Services\ChatService $service, \Kainex\WiseChat\Services\User\AuthenticationService $authentication) {
		$this->options = $options;
		$this->bansService = $bansService;
		$this->service = $service;
		$this->authentication = $authentication;
	}

	public function getOptions(): Options {
		return $this->options;
	}

	public function checkBanned() {
		if ($this->bansService->isBanned()) {
			throw new UnauthorizedAccessException(__('You are blocked from using the chat', 'wise-chat'));
		}
	}

	public function checkChatOpen() {
		if (!$this->service->isChatOpen()) {
			throw new \Exception(__('The chat is closed now', 'wise-chat'));
		}
	}

	public function checkUserAuthentication() {
		if (!$this->authentication->isAuthenticated()) {
			throw new UnauthorizedAccessException('Not authenticated');
		}
	}

	public function checkUserAuthorization() {
		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			throw new UnauthorizedAccessException('Access denied');
		}
		if ($this->service->isChatRestrictedForCurrentUserRole()) {
			throw new UnauthorizedAccessException('Access denied');
		}
		if ($this->service->isChatRestrictedToCurrentUser()) {
			throw new UnauthorizedAccessException('Access denied');
		}
	}

}