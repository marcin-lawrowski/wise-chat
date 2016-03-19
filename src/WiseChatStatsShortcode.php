<?php

/**
 * Shortcode that renders Wise Chat basic statistics for given channel.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
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

            $this->messagesService->startUpMaintenance($channel);

            return $this->renderer->getRenderedChannelStats($channel);
        } else {
            return 'ERROR: channel does not exist';
        }
    }
}