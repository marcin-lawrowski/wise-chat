<?php

/**
 * WiseChat bans services.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatChannelsService {

	const PRIVATE_MESSAGES_CHANNEL = '__private';

	/**
	 * @var WiseChatChannelsDAO
	 */
	private $channelsDAO;

	/**
	 * @var WiseChatOptions
	 */
	private $options;

	public function __construct() {
		WiseChatContainer::load('model/WiseChatChannel');

		$this->options = WiseChatOptions::getInstance();
		$this->channelsDAO = WiseChatContainer::getLazy('dao/WiseChatChannelsDAO');
	}

	/**
	 * @param integer[] $channelIds
	 * @return WiseChatChannel[]
	 * @throws Exception If a channel cannot be found
	 */
	public function getChannelsByIds($channelIds) {
		$channels = array();

		foreach ($channelIds as $channelId) {
			$requestedChannel = $this->channelsDAO->get($channelId);
			if ($requestedChannel === null) {
				throw new Exception('The channel does not exist: '.$channelId);
			}
			$channels[] = $requestedChannel;
		}

		return $channels;
	}

}