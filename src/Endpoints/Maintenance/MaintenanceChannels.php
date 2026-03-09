<?php

namespace Kainex\WiseChat\Endpoints\Maintenance;

use Kainex\WiseChat\Services\Channels\Listing\ChannelsSourcesService;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Services\User\UserFeedService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Options;

/**
 * Class for loading channels.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MaintenanceChannels {

	private UserFeedService $userFeedService;
	private ChannelsSourcesService $channelsSourcesService;
	private ClientSide $clientSide;
	private AuthenticationService $authentication;
	private Options $options;

	/**
	 * @param UserFeedService $userFeedService
	 * @param ChannelsSourcesService $channelsSourcesService
	 * @param ClientSide $clientSide
	 * @param AuthenticationService $authentication
	 * @param Options $options
	 */
	public function __construct(UserFeedService $userFeedService, ChannelsSourcesService $channelsSourcesService, ClientSide $clientSide, AuthenticationService $authentication, Options $options) {
		$this->userFeedService = $userFeedService;
		$this->channelsSourcesService = $channelsSourcesService;
		$this->clientSide = $clientSide;
		$this->authentication = $authentication;
		$this->options = $options;
	}

	/**
	 * Returns all channels to display in the browser.
	 *
	 * @return array Channels in plain-array version
	 * @throws \Exception
	 */
	public function getBrowserChannels(): array {
		$result = [];
		$channels = $this->channelsSourcesService->getConstantAndBookmarkedChannels();
		$clientChannels = $this->clientSide->convertToClientChannels($channels);

		if ($this->options->isOptionEnabled('enable_private_messages')) {
			$directChannelsUsers = $this->channelsSourcesService->getDirectChannels();
			$clientChannels = array_merge($clientChannels, $this->clientSide->convertUsersToClientChannels($directChannelsUsers));
		}

		foreach ($clientChannels as $clientChannel) {
			$result[] = $this->clientSide->clientChannelToPlain($clientChannel);
		}

		return $result;
	}

	/**
	 * Returns all opened channels.
	 *
	 * @return array Channels in plain-array version
	 * @throws \Exception
	 */
	public function getOpenChannels(): array {
		$result = [];
		$channels = $this->channelsSourcesService->getOpenChannels();
		foreach ($this->clientSide->convertToClientChannels($channels) as $clientChannel) {
			$result[] = $this->clientSide->clientChannelToPlain($clientChannel);
		}

		return $result;
	}

	public function getUserFeed(): array {
		return $this->userFeedService->getLatest($this->authentication->getUser());
	}

	public function getAutoOpenChannels(): array {
		$operators = $this->channelsSourcesService->getOperators();
		$clientChannels = $this->clientSide->convertUsersToClientChannels($operators);

		$result = [];
		foreach ($clientChannels as $clientChannel) {
			$result[] = $this->clientSide->clientChannelToPlain($clientChannel);
		}

		return $result;
	}

}