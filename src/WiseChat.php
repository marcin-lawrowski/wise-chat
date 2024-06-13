<?php

/**
 * WiseChat core class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChat {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;

	/**
	 * @var WiseChatEmoticonsDAO
	 */
	private $emoticonsDAO;
	
	/**
	* @var WiseChatRenderer
	*/
	private $renderer;
	
	/**
	* @var WiseChatCssRenderer
	*/
	private $cssRenderer;
	
	/**
	* @var WiseChatUserService
	*/
	private $userService;
	
	/**
	* @var WiseChatService
	*/
	private $service;
	
	/**
	* @var WiseChatAttachmentsService
	*/
	private $attachmentsService;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	 /**
     * @var WiseChatHttpRequestService
     */
    private $httpRequestService;
	
	/**
	* @var array
	*/
	private $shortCodeOptions;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->emoticonsDAO = WiseChatContainer::getLazy('dao/WiseChatEmoticonsDAO');
		$this->renderer = WiseChatContainer::get('rendering/WiseChatRenderer');
		$this->cssRenderer = WiseChatContainer::get('rendering/WiseChatCssRenderer');
		$this->userService = WiseChatContainer::get('services/user/WiseChatUserService');
		$this->service = WiseChatContainer::get('services/WiseChatService');
		$this->attachmentsService = WiseChatContainer::get('services/WiseChatAttachmentsService');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->httpRequestService = WiseChatContainer::getLazy('services/WiseChatHttpRequestService');
		WiseChatContainer::load('WiseChatCrypt');
		WiseChatContainer::load('rendering/WiseChatTemplater');

		$this->shortCodeOptions = array();
	}
	
	/*
	* Registers and enqueues all necessary resources (scripts or styles).
	*/
	public function enqueueResources() {
		if (getenv('WC_ENV') === 'DEV') {
			wp_enqueue_script('wise-chat', plugins_url('assets/js/wise-chat.js', dirname(__FILE__)), array('jquery'), WISE_CHAT_VERSION.'.'.time(), true);
		} else {
			wp_enqueue_script('wise-chat', plugins_url('assets/js/wise-chat.min.js', dirname(__FILE__)), array('jquery'), WISE_CHAT_VERSION, true);
		}
		wp_enqueue_style('wise-chat-libs', plugins_url('assets/css/wise-chat-libs.min.css', dirname(__FILE__)), array(), WISE_CHAT_VERSION);
		wp_enqueue_style('wise-chat-core', plugins_url('assets/css/wise-chat.min.css', dirname(__FILE__)), array(), WISE_CHAT_VERSION);
	}

	/**
	 * Shortcode backend function: [wise-chat]
	 *
	 * @param array $attributes
	 * @return string
	 * @throws Exception
	 */
	public function getRenderedShortcode($attributes) {
		if (!is_array($attributes)) {
			$attributes = array();
		}
		$attributes['channel'] = $this->service->getValidChatChannelName(
			array_key_exists('channel', $attributes) ? $attributes['channel'] : 'global'
		);
		
		$this->options->replaceOptions($attributes);
		$this->shortCodeOptions = $attributes;

		$channels = array_filter((array) $this->options->getOption('channel', array()));

		return $this->getRenderedChat($channels);
	}

	/**
	 * Returns rendered chat window.
	 *
	 * @param array $channelNames
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getRenderedChat($channelNames) {
		$channels = $this->service->createAndGetChannels($channelNames);
		$chatId = $this->service->getChatID();

		$jsOptions = array(
			'chatId' => $chatId,
			'checksum' => $this->getCheckSum(),
			'isMultisite' => is_multisite(),
			'blogId' => get_current_blog_id(),
			'theme' => $this->options->getEncodedOption('theme', 'balloon'),
			'themeClassName' => 'wc'.ucfirst($this->options->getEncodedOption('theme', 'balloon')).'Theme',
			'baseDir' => $this->options->getBaseDir(),
			'mode' => $this->options->getIntegerOption('mode', 0),
			'channelIds' => array_map(function($channel) { return $channel->getId(); }, $channels),
			'nowTime' => gmdate('c', time()),
			'messagesOrder' => $this->options->getEncodedOption('messages_order', '') == 'descending' ? 'descending' : 'ascending',
			'debug' => $this->options->isOptionEnabled('enabled_debug', false),
			'interface' => array(
				'auth' => array(
					'mode' => $this->options->getOption('auth_mode', 'auto'),
					'username' => array(
						'fields' => array_filter(json_decode($this->options->getOption('auth_username_fields', '[]')), function($field) { return $field->name ? true : false; }),
						'intro' => $this->options->getOption('auth_username_intro_template', '')
					),
					'error' => $this->httpRequestService->getRequestParam('authenticationError')
				),
				'chat' => array(
					'title' => $this->options->getOption('window_title', __('Chat', 'wise-chat')),
					'publicEnabled' => $this->options->getIntegerOption('mode', 0) === 0 && !($this->options->isOptionEnabled('classic_disable_channel', false)),
					'classic' => array(
						'channelsView' => 'tabs',
					),
					'mobile' => array(
						'tabs' => array(
							'chats' => $this->options->isOptionEnabled('mobile_mode_tab_chats_enabled', true),
						)
					)
				),
				'channel' => array(
					'inputLocation' => $this->options->getEncodedOption('input_controls_location') === 'top' ? 'top' : 'bottom',
					'directEnabled' => $this->options->isOptionEnabled('enable_private_messages'),
					'direct' => array(
						'closeConfirmation' => $this->options->isOptionEnabled('direct_channel_close_confirmation', false),
						'title' => $this->options->getEncodedOption('direct_channel_title', '')
					)
				),
				'message' => array(
					'compact' => in_array($this->options->getEncodedOption('theme', 'balloon'), array('lightgray', 'colddark', 'airflow', 'balloon')),
					'timeMode' => $this->options->getEncodedOption('messages_time_mode'),
					'dateFormat' => trim($this->options->getEncodedOption('messages_date_format')),
					'timeFormat' => trim($this->options->getEncodedOption('messages_time_format')),
					'senderMode' => $this->options->getIntegerOption('link_wp_user_name', 3),
					'links' => $this->options->isOptionEnabled('allow_post_links'),
					'attachments' => $this->options->isOptionEnabled('enable_attachments_uploader'),
					'attachmentsVideoPlayer' => $this->options->isOptionEnabled('attachments_video_player', true),
					'attachmentsSoundPlayer' => $this->options->isOptionEnabled('attachments_sound_player', true),
					'images' => $this->options->isOptionEnabled('allow_post_images'),
					'imagesViewer' => $this->options->getEncodedOption('images_viewer', 'internal'),
					'yt' => $this->options->isOptionEnabled('enable_youtube'),
					'ytWidth' => $this->options->getIntegerOption('youtube_width', 186),
					'ytHeight' => $this->options->getIntegerOption('youtube_height', 105),
					'tt' => $this->options->isOptionEnabled('enable_twitter_hashtags'),
				),
				'input' => array(
					'userName' => $this->options->isOptionEnabled('show_user_name'),
					'submit' => $this->options->isOptionEnabled('show_message_submit_button'),
					'multiline' => $this->options->isOptionEnabled('multiline_support'),
					'multilineEasy' => $this->options->isOptionEnabled('multiline_easy_mode', false),
					'maxLength' => $this->options->getIntegerOption('message_max_length', 100),
					'emoticons' => array(
						'enabled' => $this->options->isOptionEnabled('show_emoticon_insert_button', true),
						'set' => $this->options->getIntegerOption('emoticons_enabled', 1),
						'size' => $this->options->getIntegerOption('emoticons_size', 32),
						'custom' => [],
						'customPopupWidth' => $this->options->getIntegerOption('custom_emoticons_popup_width', 0),
						'customPopupHeight' => $this->options->getIntegerOption('custom_emoticons_popup_height', 0),
						'customEmoticonMaxWidthInPopup' => $this->options->getIntegerOption('custom_emoticons_emoticon_max_width_in_popup', 0),
						'baseURL' => $this->options->getEmoticonsBaseURL(),
					),
					'images' => array(
						'enabled' => $this->options->isOptionEnabled('enable_images_uploader'),
						'sizeLimit' => $this->options->getIntegerOption('images_size_limit', 3145728),
					),
					'attachments' => array(
						'enabled' => $this->options->isOptionEnabled('enable_attachments_uploader'),
						'extensionsList' => $this->attachmentsService->getAllowedExtensionsList(),
						'validFileFormats' => $this->attachmentsService->getAllowedFormats(),
						'sizeLimit' => $this->attachmentsService->getSizeLimit()
					)
				),
				'customization' => array(
					'userNameLengthLimit' => $this->options->getIntegerOption('user_name_length_limit', 25),
				),
				'browser' => array(
					'enabled' => $this->options->isOptionEnabled('show_users'),
					'searchSubChannels' => $this->options->isOptionEnabled('show_users_list_search_box', true),
					'location' => $this->options->getEncodedOption('browser_location') === 'left' ? 'left' : 'right',
					'status' => $this->options->isOptionEnabled('show_users_online_offline_mark', true),
					'mode' => $this->options->getEncodedOption('browser_mode', 'full-channels')
				),
				'recent' => array(
					'enabled' => $this->options->isOptionEnabled('users_list_offline_enable', true) && $this->options->isOptionEnabled('enable_private_messages', false),
					'excerpts' =>  $this->options->isOptionEnabled('recent_excerpts_enabled', true),
					'status' =>  $this->options->isOptionEnabled('recent_status_enabled', false)
				),
				'incoming' => array(
					'confirm' => $this->options->isOptionEnabled('private_message_confirmation', true),
					'focus' => $this->options->isOptionEnabled('private_message_autofocus', true),
				),
				'counter' => array(
					'onlineUsers' => $this->options->isOptionEnabled('show_users_counter', false)
				),
			),
			'engines' => array(
				'ajax' => array(
					'apiEndpointBase' => $this->getEndpointBase(),
					'apiMessagesEndpointBase' => $this->getMessagesEndpointBase(),
					'apiWPEndpointBase' => $this->getWPEndpointBase(),
					'refresh' => intval($this->options->getEncodedOption('messages_refresh_time', 3000)),
				)
			),
			'rights' => array(
				'receiveMessages' => !$this->options->isOptionEnabled('write_only', false), // TODO: review
			),

			'notifications' => array(
				'newChat' => array(
					'sound' => $this->options->getEncodedOption('chat_sound_notification'),
				),
				'newMessage' => array(
					'title' => $this->options->isOptionEnabled('enable_title_notifications'),
					'sound' => $this->options->getEncodedOption('sound_notification'),
					'mode' => $this->options->getEncodedOption('sound_notification_mode'),
				),
				'userLeft' => array(
					'sound' => $this->options->getEncodedOption('leave_sound_notification'),
					'browserHighlight' => $this->options->isOptionEnabled('enable_leave_notification', true),
				),
				'userJoined' => array(
					'sound' => $this->options->getEncodedOption('join_sound_notification'),
					'browserHighlight' => $this->options->isOptionEnabled('enable_join_notification', true),
				),
				'mentioned' => array(
					'sound' => $this->options->getEncodedOption('mentioning_sound_notification'),
				)
			),

			'i18n' => array(
				'loadingChat' => $this->options->getOption('message_loading_chat', __('Loading the chat ...', 'wise-chat')),
				'loading' => $this->options->getOption('message_loading', __('Loading ...', 'wise-chat')),
				'sending' => $this->options->getOption('message_sending', __('Sending ...', 'wise-chat')),
				'send' => $this->options->getOption('message_submit_button_caption', __('Send', 'wise-chat')),
				'hint' => $this->options->getOption('hint_message', __('Enter message here', 'wise-chat')),
				'customize' => $this->options->getOption('message_customize', __('Customize', 'wise-chat')),
				'secAgo' => $this->options->getOption('message_sec_ago', __('sec. ago', 'wise-chat')),
				'minAgo' => $this->options->getOption('message_min_ago', __('min. ago', 'wise-chat')),
				'yesterday' => $this->options->getOption('message_yesterday', __('yesterday', 'wise-chat')),
				'insertIntoMessage' => $this->options->getOption('message_insert_into_message', __('Insert into message', 'wise-chat')),
				'users' => $this->options->getOption('message_users', __('Users', 'wise-chat')),
				'channels' => $this->options->getOption('message_channels', __('Channels', 'wise-chat')),
				'channel' => $this->options->getOption('message_channel', __('Channel', 'wise-chat')),
				'recent' => $this->options->getOption('message_recent', __('Recent', 'wise-chat')),
				'chats' => $this->options->getOption('message_chats', __('Chats', 'wise-chat')),
				'usersAndChannels' => $this->options->getOption('message_users_and_channels', __('Users and Channels', 'wise-chat')),
				'noChannels' => $this->options->getOption('message_no_channels', __('No channels open.', 'wise-chat')),
				'noChats' => $this->options->getOption('message_no_chats', __('No chats open.', 'wise-chat')),
				'enterUserName' => $this->options->getOption('message_enter_user_name', __('Enter your username', 'wise-chat')),
				'logIn' => $this->options->getOption('message_login', __('Log in', 'wise-chat')),
				'logInUsing' => $this->options->getOption('message_login_using', __('Log in using', 'wise-chat')),
				'logInAnonymously' => $this->options->getOption('message_login_anonymously', __('Log in anonymously', 'wise-chat')),
				'onlineUsers' => $this->options->getOption('message_online_users', __('Online users', 'wise-chat')),
			)
		);

		/**
		 * Filters the configuration of the chat. The configuration is then used in the front-end rendering code.
		 *
		 * @since 3.5.5
		 *
		 * @param array $jsOptions Chat's configuration array
		 */
		$jsOptions = apply_filters('wc_chat_js_configuration', $jsOptions);
		
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile('/templates/main-react.tpl');

		$data = array(
			'chatId' => $chatId,
			'title' => $this->options->getOption('window_title', __('Chat', 'wise-chat')),
			'themeClassName' => 'wc'.ucfirst($this->options->getEncodedOption('theme', 'balloon')).'Theme',
			'loading' => $this->options->getEncodedOption('message_loading_chat', __('Loading the chat ...', 'wise-chat')),
			'classicMode' => $this->options->getIntegerOption('mode', 0) === 0,
			'sidebarMode' => $this->options->getIntegerOption('mode', 0) === 1,
			'sidebarModeLeft' => $this->options->getIntegerOption('mode', 0) === 1 && $this->options->getEncodedOption('fb_location', 'right') === 'left',
			'baseDir' => $this->options->getBaseDir(),
			'jsOptionsEncoded' => htmlspecialchars(json_encode($jsOptions), ENT_QUOTES, 'UTF-8'),
			'cssDefinitions' => $this->cssRenderer->getCssDefinition($chatId),
			'customCssDefinitions' => $this->cssRenderer->getCustomCssDefinition()
		);

		return $templater->render($data);
	}

    /**
     * @return string
     */
    private function getCheckSum() {
		$checkSumData = is_array($this->shortCodeOptions) ? $this->shortCodeOptions : array();

        return base64_encode(WiseChatCrypt::encryptToString(serialize($checkSumData)));
    }

    /**
     * @return string
     */
	private function getEndpointBase() {
		$endpointBase = get_site_url().'/wp-admin/admin-ajax.php';
		if ($this->options->getEncodedOption('ajax_engine', null) === 'gold') {
			$endpointBase = get_site_url().'/?wc-gold-engine';
		} else if (in_array($this->options->getEncodedOption('ajax_engine', null), array('lightweight', 'ultralightweight'))) {
			$endpointBase = plugin_dir_url(__FILE__).'endpoints/';
		}
		
		return $endpointBase;
	}

	/**
	 * @return string
	 */
	private function getMessagesEndpointBase() {
		if ($this->options->getEncodedOption('ajax_engine', null) === 'gold') {
			$endpointBase = get_site_url().'/?wc-gold-engine';
		} else if ($this->options->getEncodedOption('ajax_engine', null) === 'ultralightweight') {
			$endpointBase = plugin_dir_url(__FILE__).'endpoints/ultra/index.php';
		} else {
			$endpointBase = $this->getEndpointBase();
		}
		return $endpointBase;
	}

 	/**
     * @return string
	 * @see <2d023f231h06110453452f>
     */
	private function getWPEndpointBase() {
		return get_site_url().'/wp-admin/admin-ajax.php';
	}
}