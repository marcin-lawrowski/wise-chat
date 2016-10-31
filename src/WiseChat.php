<?php

/**
 * WiseChat core class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChat {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;
	
	/**
	* @var WiseChatUserSettingsDAO
	*/
	private $userSettingsDAO;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var WiseChatActionsDAO
	*/
	private $actionsDAO;
	
	/**
	* @var WiseChatRenderer
	*/
	private $renderer;
	
	/**
	* @var WiseChatCssRenderer
	*/
	private $cssRenderer;
	
	/**
	* @var WiseChatBansService
	*/
	private $bansService;
	
	/**
	* @var WiseChatUserService
	*/
	private $userService;
	
	/**
	* @var WiseChatMessagesService
	*/
	private $messagesService;
	
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
	 * @var WiseChatAds
	 */
	private $ads;
	
	/**
	* @var array
	*/
	private $shortCodeOptions;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->userSettingsDAO = WiseChatContainer::get('dao/user/WiseChatUserSettingsDAO');
		$this->channelUsersDAO = WiseChatContainer::get('dao/WiseChatChannelUsersDAO');
		$this->actionsDAO = WiseChatContainer::get('dao/WiseChatActionsDAO');
		$this->renderer = WiseChatContainer::get('rendering/WiseChatRenderer');
		$this->cssRenderer = WiseChatContainer::get('rendering/WiseChatCssRenderer');
		$this->bansService = WiseChatContainer::get('services/WiseChatBansService');
		$this->userService = WiseChatContainer::get('services/user/WiseChatUserService');
		$this->messagesService = WiseChatContainer::get('services/WiseChatMessagesService');
		$this->service = WiseChatContainer::get('services/WiseChatService');
		$this->attachmentsService = WiseChatContainer::get('services/WiseChatAttachmentsService');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->ads = WiseChatContainer::getLazy('services/WiseChatAds');
		WiseChatContainer::load('WiseChatCrypt');
		WiseChatContainer::load('WiseChatThemes');
		WiseChatContainer::load('rendering/WiseChatTemplater');
		
		$this->userService->initMaintenance();
		$this->shortCodeOptions = array();
	}
	
	/*
	* Enqueues all necessary resources (scripts or styles).
	*/
	public function registerResources() {
		$pluginBaseURL = $this->options->getBaseDir();
		
		wp_enqueue_script('wise_chat_messages_history', $pluginBaseURL.'js/utils/messages_history.js', array());
		wp_enqueue_script('wise_chat_messages', $pluginBaseURL.'js/ui/messages.js', array());
		wp_enqueue_script('wise_chat_settings', $pluginBaseURL.'js/ui/settings.js', array());
		wp_enqueue_script('wise_chat_maintenance_executor', $pluginBaseURL.'js/maintenance/executor.js', array());
		wp_enqueue_script('wise_chat_core', $pluginBaseURL.'js/wise_chat.js', array());
		
		if ($this->options->isOptionEnabled('allow_change_text_color')) {
			wp_enqueue_script('wise_chat_3rdparty_jscolorPicker', $pluginBaseURL.'js/3rdparty/jquery.colorPicker.min.js', array());
			wp_enqueue_style('wise_chat_3rdparty_jscolorPicker', $pluginBaseURL.'css/3rdparty/colorPicker.css');
		}
	}

	/**
	 * Shortcode backend function: [wise-chat]
	 *
	 * @param array $attributes
	 * @return string
	 */
	public function getRenderedShortcode($attributes) {
		if (!is_array($attributes)) {
			$attributes = array();
		}
		$attributes['channel'] = $this->service->getValidChatChannelName(
			array_key_exists('channel', $attributes) ? $attributes['channel'] : ''
		);
		
		$this->options->replaceOptions($attributes);
		$this->shortCodeOptions = $attributes;
   
		return $this->getRenderedChat($attributes['channel']);
	}

	/**
	 * Returns chat HTML for given channel.
	 *
	 * @param string|null $channelName
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getRenderedChat($channelName = null) {
		$channel = $this->service->createAndGetChannel($this->service->getValidChatChannelName($channelName));

		// saves users list in session for this channel (it will be updated in maintenance task):
		if ($this->options->isOptionEnabled('enable_leave_notification', true) || strlen($this->options->getOption('leave_sound_notification')) > 0) {
			$this->userService->clearUsersListInSession($channel, WiseChatUserService::USERS_LIST_CATEGORY_ABSENT);
		}
		if ($this->options->isOptionEnabled('enable_join_notification', true) || strlen($this->options->getOption('join_sound_notification')) > 0) {
			$this->userService->persistUsersListInSession($channel, WiseChatUserService::USERS_LIST_CATEGORY_NEW);
		}

		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_4', 'Only logged in users are allowed to enter the chat'), 'wcAccessDenied'
			);
		}

		if ($this->service->isChatRestrictedForCurrentUserRole()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_11', 'You are not allowed to enter the chat.'), 'wcAccessDenied'
			);
		}
		
		if (!$this->service->isChatOpen()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_5', 'The chat is closed now'), 'wcChatClosed'
			);
		}
		
		if ($this->service->isChatChannelFull($channel)) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_6', 'The chat is full now. Try again later.'), 'wcChatFull'
			);
		}
		
		if ($this->service->isChatChannelsLimitReached($channel)) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_10', 'You cannot enter the chat due to the limit of channels you can participate simultaneously.'), 'wcChatChannelLimitFull'
			);
		}

		if ($this->service->hasUserToBeForcedToEnterName()) {
			if ($this->getPostParam('wcUserNameSelection') !== null) {
				try {
					$this->authentication->authenticate($this->getPostParam('wcUserName'));
				} catch (Exception $e) {
					return $this->renderer->getRenderedUserNameForm($e->getMessage());
				}
			} else {
				return $this->renderer->getRenderedUserNameForm();
			}
		}
		
		if ($this->service->hasUserToBeAuthorizedInChannel($channel)) {
			if ($this->getPostParam('wcChannelAuthorization') !== null) {
				if (!$this->service->authorize($channel, $this->getPostParam('wcChannelPassword'))) {
					return $this->renderer->getRenderedPasswordAuthorization($this->options->getOption('message_error_9', 'Invalid password.'));
				}
			} else {
				return $this->renderer->getRenderedPasswordAuthorization();
			}
		}

		$chatId = $this->service->getChatID();
		
		$this->userService->startUpMaintenance($channel);
		$this->bansService->startUpMaintenance();
		$this->messagesService->startUpMaintenance($channel);

		$messages = $this->messagesService->getAllByChannelNameAndOffset($channel->getName());
		$renderedMessages = '';
		$lastId = 0;
		foreach ($messages as $message) {
			// omit non-admin messages:
			if ($message->isAdmin() && !$this->usersDAO->isWpUserAdminLogged()) {
				continue;
			}
				
			$renderedMessages .= $this->renderer->getRenderedMessage($message);
			
			if ($lastId < $message->getId()) {
				$lastId = $message->getId();
			}
		}
		
		$lastAction = $this->actionsDAO->getLast();
		$jsOptions = array(
			'chatId' => $chatId,
			'channelId' => $channel->getId(),
			'nowTime' => gmdate('c', time()),
			'lastId' => $lastId,
			'checksum' => $this->getCheckSum(),
			'lastActionId' => $lastAction !== null ? $lastAction->getId() : 0,
			'baseDir' => $this->options->getBaseDir(),
            'emoticonsBaseURL' => $this->options->getEmoticonsBaseURL(),
			'apiEndpointBase' => $this->getEndpointBase(),
			'messagesRefreshTime' => intval($this->options->getEncodedOption('messages_refresh_time', 3000)),
			'messagesOrder' => $this->options->getEncodedOption('messages_order', '') == 'descending' ? 'descending' : 'ascending',
			'enableTitleNotifications' => $this->options->isOptionEnabled('enable_title_notifications'),
			'soundNotification' => $this->options->getEncodedOption('sound_notification'),
			'messagesTimeMode' => $this->options->getEncodedOption('messages_time_mode'),
			'channelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0),
			'messages' => array(
				'message_sending' => $this->options->getEncodedOption('message_sending', 'Sending ...'),
				'hint_message' => $this->options->getEncodedOption('hint_message'),
				'messageSecAgo' => $this->options->getEncodedOption('message_sec_ago', 'sec. ago'),
				'messageMinAgo' => $this->options->getEncodedOption('message_min_ago', 'min. ago'),
				'messageYesterday' => $this->options->getEncodedOption('message_yesterday', 'yesterday'),
				'messageUnsupportedTypeOfFile' => $this->options->getEncodedOption('message_error_7', 'Unsupported type of file.'),
				'messageSizeLimitError' => $this->options->getEncodedOption('message_error_8', 'The size of the file exceeds allowed limit.'),
				'messageInputTitle' => $this->options->getEncodedOption('message_input_title', 'Use Shift+ENTER in order to move to the next line.'),
				'messageHasLeftTheChannel' => $this->options->getEncodedOption('message_has_left_the_channel', 'has left the channel'),
				'messageHasJoinedTheChannel' => $this->options->getEncodedOption('message_has_joined_the_channel', 'has joined the channel'),
			),
			'userSettings' => $this->userSettingsDAO->getAll(),
			'attachmentsValidFileFormats' => $this->attachmentsService->getAllowedFormats(),
			'attachmentsSizeLimit' => $this->attachmentsService->getSizeLimit(),
			'imagesSizeLimit' => $this->options->getIntegerOption('images_size_limit', 3145728),
			'autoHideUsersList' => $this->options->isOptionEnabled('autohide_users_list', false),
			'autoHideUsersListWidth' => $this->options->getIntegerOption('autohide_users_list_width', 300),
			'showUsersList' => $this->options->isOptionEnabled('show_users'),
			'multilineSupport' => $this->options->isOptionEnabled('multiline_support'),
			'messageMaxLength' => $this->options->getIntegerOption('message_max_length', 100),
			'debugMode' => $this->options->isOptionEnabled('enabled_debug', false),
			'emoticonsSet' => $this->options->getIntegerOption('emoticons_enabled', 1),
			'enableLeaveNotification' => $this->options->isOptionEnabled('enable_leave_notification', true),
			'enableJoinNotification' => $this->options->isOptionEnabled('enable_join_notification', true),
			'leaveSoundNotification' => $this->options->getEncodedOption('leave_sound_notification'),
			'joinSoundNotification' => $this->options->getEncodedOption('join_sound_notification'),
			'mentioningSoundNotification' => $this->options->getEncodedOption('mentioning_sound_notification'),
		);

		foreach ($jsOptions['messages'] as $key => $jsOption) {
			$jsOptions['messages'][$key] = html_entity_decode( (string) $jsOption, ENT_QUOTES, 'UTF-8');
		}
		
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getMainTemplate());
		$data = array(
			'chatId' => $chatId,
			'baseDir' => $this->options->getBaseDir(),
			'messages' => $renderedMessages,
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'showMessageSubmitButton' => $this->options->isOptionEnabled('show_message_submit_button'),
            'showEmoticonInsertButton' => $this->options->isOptionEnabled('show_emoticon_insert_button', true),
			'messageSubmitButtonCaption' => $this->options->getEncodedOption('message_submit_button_caption', 'Send'),
			'showUsersList' => $this->options->isOptionEnabled('show_users'),
			'usersList' => $this->options->isOptionEnabled('show_users') ? $this->renderer->getRenderedUsersList($channel) : '',
			'showUsersCounter' => $this->options->isOptionEnabled('show_users_counter'),
			'channelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0),
			'totalUsers' => $this->channelUsersDAO->getAmountOfUsersInChannel($channel->getId()),
			'showUserName' => $this->options->isOptionEnabled('show_user_name'),
			'currentUserName' => htmlentities($this->authentication->getUserNameOrEmptyString(), ENT_QUOTES, 'UTF-8'),
			'isCurrentUserNameNotEmpty' => $this->authentication->isAuthenticated(),
			
			'inputControlsTopLocation' => $this->options->getEncodedOption('input_controls_location') == 'top',
			'inputControlsBottomLocation' => $this->options->getEncodedOption('input_controls_location') == '',
			
			'showCustomizationsPanel' => 
				$this->options->isOptionEnabled('allow_change_user_name') && !$this->usersDAO->isWpUserLogged() ||
				$this->options->isOptionEnabled('allow_mute_sound') && strlen($this->options->getEncodedOption('sound_notification')) > 0 || 
				$this->options->isOptionEnabled('allow_change_text_color'),
				
			'allowChangeUserName' => $this->options->isOptionEnabled('allow_change_user_name') && !$this->usersDAO->isWpUserLogged(),
			'allowMuteSound' => $this->options->isOptionEnabled('allow_mute_sound') && strlen($this->options->getEncodedOption('sound_notification')) > 0,
			'allowChangeTextColor' => $this->options->isOptionEnabled('allow_change_text_color'),

            'allowToSendMessages' => $this->userService->isSendingMessagesAllowed(),
				
			'messageCustomize' => $this->options->getEncodedOption('message_customize', 'Customize'),
			'messageName' => $this->options->getEncodedOption('message_name', 'Name'),
			'messageSave' => $this->options->getEncodedOption('message_save', 'Save'),
			'messageReset' => $this->options->getEncodedOption('message_reset', 'Reset'),
			'messageMuteSounds' => $this->options->getEncodedOption('message_mute_sounds', 'Mute sounds'),
			'messageTextColor' => $this->options->getEncodedOption('message_text_color', 'Text color'),
			'messageTotalUsers' => $this->options->getEncodedOption('message_total_users', 'Total users'),
			'messagePictureUploadHint' => $this->options->getEncodedOption('message_picture_upload_hint', 'Upload a picture'),
			'messageAttachFileHint' => $this->options->getEncodedOption('message_attach_file_hint', 'Attach a file'),
            'messageInsertEmoticon' => $this->options->getEncodedOption('message_insert_emoticon', 'Insert an emoticon'),
			'messageInputTitle' => $this->options->getEncodedOption('message_input_title', 'Use Shift+ENTER in order to move to the next line.'),
            'windowTitle' => $this->options->getEncodedOption('window_title', ''),

            'enableAttachmentsPanel' => $this->options->isOptionEnabled('enable_images_uploader') || $this->options->isOptionEnabled('enable_attachments_uploader'),
            'enableImagesUploader' => $this->options->isOptionEnabled('enable_images_uploader'),
            'enableAttachmentsUploader' => $this->options->isOptionEnabled('enable_attachments_uploader'),
            'attachmentsExtensionsList' => $this->attachmentsService->getAllowedExtensionsList(),

            'multilineSupport' => $this->options->isOptionEnabled('multiline_support'),
            'hintMessage' => $this->options->getEncodedOption('hint_message'),
            'messageMaxLength' => $this->options->getIntegerOption('message_max_length', 100),

			'jsOptions' => json_encode($jsOptions),
            'messagesOrder' => $this->options->getEncodedOption('messages_order', '') == 'descending' ? 'descending' : 'ascending',
			'cssDefinitions' => $this->cssRenderer->getCssDefinition($chatId),
			'customCssDefinitions' => $this->cssRenderer->getCustomCssDefinition(),
			'poweredBy' => $this->ads->getFooterAd(),
		);
		
		$data = array_merge($data, $this->userSettingsDAO->getAll());
		if ($this->authentication->isAuthenticated()) {
			$data = array_merge($data, $this->authentication->getUser()->getData());
		}

		return $templater->render($data);
	}

    /**
     * @return string
     */
    private function getCheckSum() {
        return base64_encode(WiseChatCrypt::encrypt(serialize($this->shortCodeOptions)));
    }

    /**
     * @return string
     */
	private function getEndpointBase() {
		$endpointBase = get_site_url().'/wp-admin/admin-ajax.php';
		if ($this->options->getEncodedOption('ajax_engine', null) === 'lightweight') {
			$endpointBase = get_site_url().'/wp-content/plugins/wise-chat/src/endpoints/';
		}
		
		return $endpointBase;
	}

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
	private function getPostParam($name, $default = null) {
		return array_key_exists($name, $_POST) ? $_POST[$name] : $default;
	}
}