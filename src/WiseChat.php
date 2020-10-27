<?php

/**
 * WiseChat core class.
 *
 * @author Kainex <contact@kaine.pl>
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
	 * @var WiseChatHttpRequestService
	 */
	private $httpRequestService;
	
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
		$this->httpRequestService = WiseChatContainer::getLazy('services/WiseChatHttpRequestService');
		WiseChatContainer::load('WiseChatCrypt');
		WiseChatContainer::load('WiseChatThemes');
		WiseChatContainer::load('rendering/WiseChatTemplater');

		$this->shortCodeOptions = array();
	}
	
	/*
	* Enqueues all necessary resources (scripts or styles).
	*/
	public function registerResources() {
		$pluginBaseURL = $this->options->getBaseDir();
		
		wp_enqueue_script('wise_chat_messages_history', $pluginBaseURL.'js/utils/messages_history.js', array('jquery'));
		wp_enqueue_script('wise_chat_messages', $pluginBaseURL.'js/ui/messages.js', array('jquery'));
		wp_enqueue_script('wise_chat_settings', $pluginBaseURL.'js/ui/settings.js', array('jquery'));
		wp_enqueue_script('wise_chat_maintenance_executor', $pluginBaseURL.'js/maintenance/executor.js', array('jquery'));
		wp_enqueue_script('wise_chat_core', $pluginBaseURL.'js/wise_chat.js', array('jquery'));
		
		if ($this->options->isOptionEnabled('allow_change_text_color', true)) {
			wp_enqueue_script('wise_chat_3rdparty_jscolorPicker', $pluginBaseURL.'js/3rdparty/jquery.colorPicker.min.js', array('jquery'));
			wp_enqueue_style('wise_chat_3rdparty_jscolorPicker', $pluginBaseURL.'css/3rdparty/colorPicker.css');
		}

		wp_enqueue_script('wise_chat_3rdparty_momentjs', $pluginBaseURL.'js/3rdparty/moment.patched.min.js', array('jquery'));
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
	 * Shortcode backend function: [wise-chat]
	 *
	 * @param array $attributes
	 * @return string
	 */
	public function getRenderedChannelUsersShortcode($attributes) {
		if (!is_array($attributes)) {
			$attributes = array();
		}
		$attributes['channel'] = $this->service->getValidChatChannelName(
			array_key_exists('channel', $attributes) ? $attributes['channel'] : ''
		);

		$attributes['chat_height'] = '';

		$this->options->replaceOptions($attributes);
		$this->shortCodeOptions = $attributes;

		$chatId = $this->service->getChatID();
		$channel = $this->service->createAndGetChannel($this->service->getValidChatChannelName($attributes['channel']));
		$this->userService->refreshChannelUsersData();

		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getChannelUsersWidgetTemplate());

		$data = array(
			'chatId' => $chatId,
			'baseDir' => $this->options->getBaseDir(),
			'title' => $attributes['title'],
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'usersList' => $this->renderer->getRenderedUsersList($channel, false),
			'cssDefinitions' => $this->cssRenderer->getCssDefinition($chatId),
			'customCssDefinitions' => $this->cssRenderer->getCustomCssDefinition(),
			'messageUsersListEmpty' => $this->options->getEncodedOption('message_users_list_empty', __('No users in the channel', 'wise-chat')),
		);
		$data = array_merge($data, $this->userSettingsDAO->getAll());
		if ($this->authentication->isAuthenticated()) {
			$data = array_merge($data, $this->authentication->getUser()->getData());
		}

		return $templater->render($data);
	}

	/**
	 * Returns rendered chat window.
	 *
	 * @param string|null $channelName
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getRenderedChat($channelName = null) {
		$channel = $this->service->createAndGetChannel($this->service->getValidChatChannelName($channelName));

		// saves the current list of users (it will be updated in maintenance task):
		if ($this->authentication->isAuthenticated()) {
			if ($this->options->isOptionEnabled('enable_leave_notification', true) || strlen($this->options->getOption('leave_sound_notification')) > 0) {
				$this->userService->clearUsersList($channel, WiseChatUserService::USERS_LIST_CATEGORY_ABSENT);
			}
			if ($this->options->isOptionEnabled('enable_join_notification', true) || strlen($this->options->getOption('join_sound_notification')) > 0) {
				$this->userService->persistUsersList($channel, WiseChatUserService::USERS_LIST_CATEGORY_NEW);
			}
		}

		if ($this->service->isIpKicked()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_12', __('You are blocked from using the chat', 'wise-chat')), 'wcAccessDenied'
			);
		}

		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_4', __('Only logged in users are allowed to enter the chat', 'wise-chat')), 'wcAccessDenied'
			);
		}

		if ($this->service->isChatRestrictedForCurrentUserRole()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_11', __('You are not allowed to enter the chat.', 'wise-chat')), 'wcAccessDenied'
			);
		}
		
		if (!$this->service->isChatOpen()) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_5', __('The chat is closed now', 'wise-chat')), 'wcChatClosed'
			);
		}
		
		if ($this->service->isChatChannelFull($channel)) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_6', __('The chat is full now. Try again later.', 'wise-chat')), 'wcChatFull'
			);
		}
		
		if ($this->service->isChatChannelsLimitReached($channel)) {
			return $this->renderer->getRenderedAccessDenied(
				$this->options->getOption('message_error_10', __('You cannot enter the chat due to the limit of channels you can participate simultaneously.', 'wise-chat')), 'wcChatChannelLimitFull'
			);
		}

		if ($this->service->hasUserToBeForcedToEnterName()) {
			return $this->renderer->getRenderedUserNameForm($channel);
		} else if ($this->service->hasUserToBeAuthorizedInChannel($channel)) {
			return $this->renderer->getRenderedPasswordAuthorization($channel);
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
			'apiWPEndpointBase' => $this->getWPEndpointBase(),
			'apiEndpointBase' => $this->getEndpointBase(),
			'apiMessagesEndpointBase' => $this->getMessagesEndpointBase(),
			'messagesRefreshTime' => intval($this->options->getEncodedOption('messages_refresh_time', 3000)),
			'messagesOrder' => $this->options->getEncodedOption('messages_order', '') == 'descending' ? 'descending' : 'ascending',
			'enableTitleNotifications' => $this->options->isOptionEnabled('enable_title_notifications'),
			'soundNotification' => $this->options->getEncodedOption('sound_notification'),
			'messagesTimeMode' => $this->options->getEncodedOption('messages_time_mode', 'elapsed'),
			'messagesDateFormat' => trim($this->options->getEncodedOption('messages_date_format')),
			'messagesTimeFormat' => trim($this->options->getEncodedOption('messages_time_format')),
			'channelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0),
			'messages' => array(
				'message_sending' => $this->options->getEncodedOption('message_sending', __('Sending ...', 'wise-chat')),
				'hint_message' => $this->options->getEncodedOption('hint_message'),
				'messageSecAgo' => $this->options->getEncodedOption('message_sec_ago', __('sec. ago', 'wise-chat')),
				'messageMinAgo' => $this->options->getEncodedOption('message_min_ago', __('min. ago', 'wise-chat')),
				'messageYesterday' => $this->options->getEncodedOption('message_yesterday', __('yesterday', 'wise-chat')),
				'messageUnsupportedTypeOfFile' => $this->options->getEncodedOption('message_error_7', __('Unsupported type of file.', 'wise-chat')),
				'messageSizeLimitError' => $this->options->getEncodedOption('message_error_8', __('The size of the file exceeds allowed limit.', 'wise-chat')),
				'messageInputTitle' => $this->options->getEncodedOption('message_input_title', __('Use Shift+ENTER in order to move to the next line.', 'wise-chat')),
				'messageHasLeftTheChannel' => $this->options->getEncodedOption('message_has_left_the_channel', __('has left the channel', 'wise-chat')),
				'messageHasJoinedTheChannel' => $this->options->getEncodedOption('message_has_joined_the_channel', __('has joined the channel', 'wise-chat')),
				'messageSpamReportQuestion' => $this->options->getEncodedOption('message_text_1', __('Are you sure you want to report the message as spam?', 'wise-chat')),
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
			'errorMode' => $this->options->isOptionEnabled('enabled_errors', false),
			'emoticonsSet' => $this->options->getIntegerOption('emoticons_enabled', 1),
			'enableLeaveNotification' => $this->options->isOptionEnabled('enable_leave_notification', true),
			'enableJoinNotification' => $this->options->isOptionEnabled('enable_join_notification', true),
			'leaveSoundNotification' => $this->options->getEncodedOption('leave_sound_notification'),
			'joinSoundNotification' => $this->options->getEncodedOption('join_sound_notification'),
			'mentioningSoundNotification' => $this->options->getEncodedOption('mentioning_sound_notification'),
			'textColorAffectedParts' => (array) $this->options->getOption("text_color_parts", array('message', 'messageUserName')),
		);

		foreach ($jsOptions['messages'] as $key => $jsOption) {
			$jsOptions['messages'][$key] = html_entity_decode( (string) $jsOption, ENT_QUOTES, 'UTF-8');
		}
		
		$templater = new WiseChatTemplater($this->options->getPluginBaseDir());
		$templater->setTemplateFile(WiseChatThemes::getInstance()->getMainTemplate());

		$totalUsers = 0;
		if ($this->options->isOptionEnabled('counter_without_anonymous', true)) {
			$totalUsers = $this->channelUsersDAO->getAmountOfLoggedInUsersInChannel($channel->getId());
		} else {
			$totalUsers = $this->channelUsersDAO->getAmountOfUsersInChannel($channel->getId());
		}

		$data = array(
			'chatId' => $chatId,
			'baseDir' => $this->options->getBaseDir(),
			'messages' => $renderedMessages,
			'themeStyles' => $this->options->getBaseDir().WiseChatThemes::getInstance()->getCss(),
			'showMessageSubmitButton' => $this->options->isOptionEnabled('show_message_submit_button', true),
            'showEmoticonInsertButton' => $this->options->isOptionEnabled('show_emoticon_insert_button', true),
			'messagesInline' => $this->options->isOptionEnabled('messages_inline', false),
			'messageSubmitButtonCaption' => $this->options->getEncodedOption('message_submit_button_caption', __('Send', 'wise-chat')),
			'showUsersList' => $this->options->isOptionEnabled('show_users'),
			'usersList' => $this->options->isOptionEnabled('show_users') ? $this->renderer->getRenderedUsersList($channel) : '',
			'showUsersCounter' => $this->options->isOptionEnabled('show_users_counter'),
			'channelUsersLimit' => $this->options->getIntegerOption('channel_users_limit', 0),
			'totalUsers' => $totalUsers,
			'showUserName' => $this->options->isOptionEnabled('show_user_name', true),
			'currentUserName' => htmlentities($this->authentication->getUserNameOrEmptyString(), ENT_QUOTES, 'UTF-8', false),
			'isCurrentUserNameNotEmpty' => $this->authentication->isAuthenticated(),
			
			'inputControlsTopLocation' => $this->options->getEncodedOption('input_controls_location') == 'top',
			'inputControlsBottomLocation' => $this->options->getEncodedOption('input_controls_location') == '',
			
			'showCustomizationsPanel' => 
				$this->options->isOptionEnabled('allow_change_user_name', true) && !$this->usersDAO->isWpUserLogged() ||
				$this->options->isOptionEnabled('allow_mute_sound') && strlen($this->options->getEncodedOption('sound_notification')) > 0 || 
				$this->options->isOptionEnabled('allow_change_text_color', true),
				
			'allowChangeUserName' => $this->options->isOptionEnabled('allow_change_user_name', true) && !$this->usersDAO->isWpUserLogged(),
			'userNameLengthLimit' => $this->options->getIntegerOption('user_name_length_limit', 25),
			'allowMuteSound' => $this->options->isOptionEnabled('allow_mute_sound') && strlen($this->options->getEncodedOption('sound_notification')) > 0,
			'allowChangeTextColor' => $this->options->isOptionEnabled('allow_change_text_color', true),

            'allowToSendMessages' => $this->userService->isSendingMessagesAllowed(),
				
			'messageCustomize' => $this->options->getEncodedOption('message_customize', __('Customize', 'wise-chat')),
			'messageName' => $this->options->getEncodedOption('message_name', __('Name', 'wise-chat')),
			'messageSave' => $this->options->getEncodedOption('message_save', __('Save', 'wise-chat')),
			'messageReset' => $this->options->getEncodedOption('message_reset', __('Reset', 'wise-chat')),
			'messageMuteSounds' => $this->options->getEncodedOption('message_mute_sounds', __('Mute sounds', 'wise-chat')),
			'messageTextColor' => $this->options->getEncodedOption('message_text_color', __('Text color', 'wise-chat')),
			'messageTotalUsers' => $this->options->getEncodedOption('message_total_users', __('Total users', 'wise-chat')),
			'messagePictureUploadHint' => $this->options->getEncodedOption('message_picture_upload_hint', __('Upload a picture', 'wise-chat')),
			'messageAttachFileHint' => $this->options->getEncodedOption('message_attach_file_hint', __('Attach a file', 'wise-chat')),
            'messageInsertEmoticon' => $this->options->getEncodedOption('message_insert_emoticon', __('Insert an emoticon', 'wise-chat')),
			'messageInputTitle' => $this->options->getEncodedOption('message_input_title', __('Use Shift+ENTER in order to move to the next line.', 'wise-chat')),
            'windowTitle' => $this->options->getEncodedOption('window_title', 'Wise Chat'),

            'enableAttachmentsPanel' => $this->options->isOptionEnabled('enable_images_uploader', true) || $this->options->isOptionEnabled('enable_attachments_uploader', true),
            'enableImagesUploader' => $this->options->isOptionEnabled('enable_images_uploader', true),
            'enableAttachmentsUploader' => $this->options->isOptionEnabled('enable_attachments_uploader', true),
            'attachmentsExtensionsList' => $this->attachmentsService->getAllowedExtensionsList(),

            'multilineSupport' => $this->options->isOptionEnabled('multiline_support'),
            'hintMessage' => $this->options->getEncodedOption('hint_message'),
            'messageMaxLength' => $this->options->getIntegerOption('message_max_length', 100),

			'jsOptions' => json_encode($jsOptions),
			'jsOptionsEncoded' => htmlspecialchars(json_encode($jsOptions), ENT_QUOTES, 'UTF-8'),
            'messagesOrder' => $this->options->getEncodedOption('messages_order', '') == 'descending' ? 'descending' : 'ascending',
			'cssDefinitions' => $this->cssRenderer->getCssDefinition($chatId),
			'customCssDefinitions' => $this->cssRenderer->getCustomCssDefinition()
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
		$this->shortCodeOptions['ts'] = time();
        return base64_encode(WiseChatCrypt::encrypt(serialize($this->shortCodeOptions)));
    }

	/**
	 * @return string
	 */
	private function getWPEndpointBase() {
		return get_site_url().'/wp-admin/admin-ajax.php';
	}

    /**
     * @return string
     */
	private function getEndpointBase() {
		$endpointBase = get_site_url().'/wp-admin/admin-ajax.php';
		if (in_array($this->options->getEncodedOption('ajax_engine', null), array('lightweight', 'ultralightweight'))) {
			$endpointBase = plugin_dir_url(__FILE__).'endpoints/';
		}
		
		return $endpointBase;
	}

	/**
     * @return string
     */
	private function getMessagesEndpointBase() {
		if ($this->options->getEncodedOption('ajax_engine', null) === 'ultralightweight') {
			$endpointBase = plugin_dir_url(__FILE__).'endpoints/ultra/index.php';
		} else {
			$endpointBase = $this->getEndpointBase();
		}

		return $endpointBase;
	}
}