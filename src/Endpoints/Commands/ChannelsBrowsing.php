<?php

namespace Kainex\WiseChat\Endpoints\Commands;

use Kainex\WiseChat\Services\ChannelsService;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Traits\HttpUtils;

class ChannelsBrowsing {
	use HttpUtils;

	private ChannelsService $channelsService;
	private ClientSide $clientSide;

	/**
	 * @param ChannelsService $channelsService
	 * @param ClientSide $clientSide
	 */
	public function __construct(ChannelsService $channelsService, ClientSide $clientSide) {
		$this->channelsService = $channelsService;
		$this->clientSide = $clientSide;
	}

	/**
	 * @throws \Exception
	 */
	public function handle(): array {
		$command = $this->getPostParam('command');
		$parameters = $this->getPostParam('parameters');

		switch ($command) {
			case 'channels.browsing.search':
				$response = $this->search($parameters);

				break;
			default:
				throw new \Exception('Command not found');
		}

		return $response;
	}

	/**
	 * Searches through non-constant, non-BP, non-legacy channels.
	 *
	 * @param array $parameters
	 * @return array
	 * @throws \Exception
	 */
	private function search(array $parameters): array {
		$memberOf = $this->channelsService->getChannelsUserBelongsTo();
		$memberOfIDs = [];
		foreach ($memberOf as $member) {
			$memberOfIDs[] = $member->getChannelId();
		}
		$constantChannels = $this->channelsService->getConstantChannels();
		$constantChannelsIDs = [];
		foreach ($constantChannels as $constantChannel) {
			$constantChannelsIDs[] = $constantChannel->getId();
		}

		$channels = $this->channelsService->searchChannels([
			'search' => $parameters['search'],
			'exclude' => $constantChannelsIDs,
			'excludeNames' => ['bp-%', '__private'],
			'page' => (int) $parameters['page']
		]);
		$channelsStats = count($channels) > 0 ? $this->channelsService->getChannelsStats($channels) : [];

		$channelsPlain = [];
		foreach ($channels as $channel) {
			$channelsPlain[] = array_merge([
				'channel' => $this->clientSide->channelToPlain($channel),
				'member' => in_array($channel->getId(), $memberOfIDs)
			], $channelsStats[$channel->getId()] ?? []);
		}

		return ['entries' => $channelsPlain];
	}

}