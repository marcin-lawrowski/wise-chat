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
	 * @var WiseChatAuthorization
	 */
	protected $authorization;

	/**
	 * @var WiseChatOptions
	 */
	private $options;

	public function __construct() {
		WiseChatContainer::load('model/WiseChatChannel');

		$this->options = WiseChatOptions::getInstance();
		$this->channelsDAO = WiseChatContainer::getLazy('dao/WiseChatChannelsDAO');
		$this->authorization = WiseChatContainer::getLazy('services/user/WiseChatAuthorization');
	}

	/**
	 * @param WiseChatChannel $channel
	 * @return bool
	 * @throws Exception
	 */
	public function hasPublicChannelAccess($channel) {
		if ($this->options->getIntegerOption('mode', 0) === 0 && $this->options->isOptionEnabled('classic_disable_channel', false)) {
			return false;
		}

		if ($this->options->getIntegerOption('mode', 0) === 1 && $this->options->isOptionEnabled('fb_disable_channel', false)) {
			return false;
		}

		return $this->authorization->isUserAuthorizedForChannel($channel);
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

	/**
	 * Get the channel for storing direct messages.
	 *
	 * @return WiseChatChannel
	 * @throws Exception
	 */
	public function getDirectChannel() {
		$channel = $this->channelsDAO->getByName(self::PRIVATE_MESSAGES_CHANNEL);

		// create the direct messages channel if it does not exist:
		if (!$channel) {
			$channel = new WiseChatChannel();
			$channel->setName(self::PRIVATE_MESSAGES_CHANNEL);
			$this->channelsDAO->save($channel);
		}

		return $channel;
	}

	/**
	 * @param WiseChatChannel $channel
	 * @return bool
	 */
	public function isDirect($channel) {
		return $channel->getName() === self::PRIVATE_MESSAGES_CHANNEL;
	}

}