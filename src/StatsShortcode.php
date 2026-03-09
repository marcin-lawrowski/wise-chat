<?php

namespace Kainex\WiseChat;

use Kainex\WiseChat\DAO\ChannelsDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Rendering\Renderer;
use Kainex\WiseChat\Services\MessagesService;
use Kainex\WiseChat\Services\ChatService;

/**
 * Shortcode that renders Wise Chat basic statistics for given channel.
 *
 * @author Kainex <contact@kaine.pl>
 */
class StatsShortcode {
    /**
     * @var Options
     */
    private $options;

    /**
     * @var ChatService
     */
    private $service;

    /**
     * @var MessagesService
     */
    private $messagesService;

    /**
     * @var ChannelsDAO
     */
    private $channelsDAO;

    /**
     * @var Renderer
     */
    private $renderer;

	/**
	 * @param Options $options
	 * @param ChatService $service
	 * @param MessagesService $messagesService
	 * @param ChannelsDAO $channelsDAO
	 * @param Renderer $renderer
	 */
	public function __construct(Options $options, ChatService $service, MessagesService $messagesService, ChannelsDAO $channelsDAO, Renderer $renderer) {
		$this->options = $options;
		$this->service = $service;
		$this->messagesService = $messagesService;
		$this->channelsDAO = $channelsDAO;
		$this->renderer = $renderer;
	}

	/**
     * Renders shortcode: [wise-chat-channel-stats]
     *
     * @param array $attributes
     * @return string
     */
    public function getRenderedChannelStatsShortcode($attributes) {
        if (!is_array($attributes)) {
            $attributes = array();
        }

        $attributes['channel'] = $this->service->getValidChatChannelName(
            array_key_exists('channel', $attributes) ? $attributes['channel'] : ''
        );

        $channel = $this->channelsDAO->getByName($attributes['channel']);
        if ($channel !== null) {
            $this->options->replaceOptions($attributes);

            $this->messagesService->startUpMaintenance();

            /**
             * Filters HTML outputted by channel stats shortcode:
             * [wise-chat-channel-stats template="Channel: {channel} Messages: {messages} Users: {users}"]
             *
             * @param string $html A HTML code outputted by channel stats shortcode
             * @param Channel $channel The channel
             *@since 2.3.2
             *
             */
            return apply_filters('wc_chat_channel_stats_html', $this->renderer->getRenderedChannelStats($channel), $channel);
        } else {
            return 'ERROR: channel does not exist';
        }
    }
}