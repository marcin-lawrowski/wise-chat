<?php

/**
 * Shortcode that renders Wise Chat basic statistics for given channel.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatStatsShortcode {
    /**
     * @var WiseChatOptions
     */
    private $options;

    /**
     * @var WiseChatService
     */
    private $service;

    /**
     * @var WiseChatMessagesService
     */
    private $messagesService;

    /**
     * @var WiseChatChannelsDAO
     */
    private $channelsDAO;

    /**
     * @var WiseChatRenderer
     */
    private $renderer;

    /**
     * WiseChatStatsShortcode constructor.
     */
    public function __construct() {
        $this->options = WiseChatOptions::getInstance();
        $this->service = WiseChatContainer::get('services/WiseChatService');
        $this->messagesService = WiseChatContainer::get('services/WiseChatMessagesService');
        $this->channelsDAO = WiseChatContainer::get('dao/WiseChatChannelsDAO');
        $this->renderer = WiseChatContainer::get('rendering/WiseChatRenderer');
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
             * @since 2.3.2
             *
             * @param string $html A HTML code outputted by channel stats shortcode
             * @param WiseChatChannel $channel The channel
             */
            return apply_filters('wc_chat_channel_stats_html', $this->renderer->getRenderedChannelStats($channel), $channel);
        } else {
            return 'ERROR: channel does not exist';
        }
    }
}