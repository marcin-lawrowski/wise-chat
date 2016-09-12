<?php

/**
 * WiseChat ads services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatAds {

    /**
     * @var WiseChatOptions
     */
    private $options;

    public function __construct() {
        $this->options = WiseChatOptions::getInstance();
    }

    /**
     * Returns an ad for footer of the chat.
     *
     * @return string
     */
    public function getFooterAd() {
        if ($this->options->isOptionEnabled('show_powered_by', true)) {
            $domain = $_SERVER['SERVER_NAME'];
            $position = 0;
            if (strlen($domain) > 0) {
                $position = ord(strtoupper($domain[0])) - ord('A') + 1;
            }

            $groupIndex = abs($position % 2);
            if ($groupIndex === 0) {
                $urls = array(
                    'http://kaine.pl/projects/wp-plugins/wise-chat-pro/',
                    'http://kaine.pl/projects/wp-plugins/wise-chat-pro/',
                    'http://kaine.pl/projects/wp-plugins/wise-chat-pro/',
                    'http://kaine.pl/projects/wp-plugins/wise-chat-pro/',
                    'http://kaine.pl/projects/wp-plugins/wise-chat-pro/',
                    'http://kaine.pl/projects/wp-plugins/wise-chat-pro/',
                    'http://kaine.pl/projects/wp-plugins/wise-chat-pro/',
                );
                $titles = array(
                    'Chat plugin for WordPress',
                    'Chat plugin for BuddyPress',
                    'WordPress chat plugin',
                    'WordPress chat',
                    'Chat plugin WordPress',
                    'Chat plugin for WordPress',
                    'WordPress and BuddyPress chat plugin',
                );
                $index = abs($position % count($urls));

                return sprintf('<div class="wcPoweredBy">Powered by <a href="%s" title="%s">Wise Chat</a></div>', $urls[$index], $titles[$index]);
            } else {
                $urls = array(
                    'http://kaine.pl/',
                    'http://kaine.pl/',
                    'http://kaine.pl/',
                    'http://kaine.pl/about-us/',
                    'http://kaine.pl/about-us/',
                    'http://kaine.pl/about-us/',
                );
                $titles = array(
                    'WordPress plugins',
                    'WordPress development',
                    'Web solutions agency',
                    'WordPress solutions',
                    'Dedicated systems solutions',
                    'Websites development',
                );
                $index = abs($position % count($urls));

                return sprintf('<div class="wcPoweredBy">Created by <a href="%s" title="%s">Kainex</a></div>', $urls[$index], $titles[$index]);
            }
        }

        return '';
    }

}