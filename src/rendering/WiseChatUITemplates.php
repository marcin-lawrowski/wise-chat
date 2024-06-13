<?php

/**
 * Building templates for various parts of chat's UI.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatUITemplates {

	/** @var WiseChatChannelUsersDAO */
	protected $channelUsersDAO;

	/** @var WiseChatUserService */
	protected $userService;

	/** @var WiseChatRenderer */
	protected $renderer;

	/** @var WiseChatUsersDAO */
	protected $usersDAO;

	/** @var WiseChatClientSide */
	protected $clientSide;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->renderer = WiseChatContainer::getLazy('rendering/WiseChatRenderer');
		$this->userService = WiseChatContainer::getLazy('services/user/WiseChatUserService');
		$this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
		$this->clientSide = WiseChatContainer::getLazy('services/client-side/WiseChatClientSide');
	}

	/**
	 * @param WiseChatUser $user
	 * @param WiseChatUser $recipientUser
	 *
	 * @return string|null
	 */
	public function getWelcomeMessage($user, $recipientUser) {
		if ($recipientUser->getWordPressId() > 0) {
			$metaName = 'wc_live_chat_welcome_message_wordpress_user';
		} else {
			$metaName = 'wc_live_chat_welcome_message';
		}
		$welcomeMessageTemplate = $this->usersDAO->getWpUserMeta($user->getWordPressId(), $metaName);

		if ($welcomeMessageTemplate) {
			$template = $this->renderer->getTemplatedString($this->getUserVariables($recipientUser), $welcomeMessageTemplate, false);
			return $this->cleanTemplate($template);
		}

		return null;
	}

	/**
	 * @param WiseChatUser $user
	 *
	 * @return string
	 */
	public function getInfoWindow($user) {
		if ($user->getWordPressId() > 0) {
			$templateDefault = "{role}";
			$template = $this->options->getOption('users_list_info_window_template', $templateDefault);
		} else {
			$templateDefault = "{username}";
			$template = $this->options->getOption('users_list_info_window_template_name_auth', $templateDefault);
		}

		$template = $this->renderer->getTemplatedString($this->getUserVariables($user), $template, false);
		$template = $this->cleanTemplate($template);

		/**
		 * Filters template of the user info window popup displayed when hovering username on the users list.
		 *
		 * @since 3.0.0
		 *
		 * @param string $template A template with rendered dynamic variables
		 * @param WiseChatUser $user The user
		 */
		return apply_filters('wc_user_info_window_template', $template, $user);
	}

	/**
	 * @param WiseChatUser $user
	 * @return string
	 */
	public function getDirectChannelIntro($user) {
		if (!$this->options->isOptionEnabled('intro_direct_channel_enabled', true)) {
			return null;
		}

		$variables = $this->getUserVariables($user);
		if ($user->getWordPressId() > 0) {
			$templateDefault = '[span className="wcName" content="{name}"]
			{role} {status} {video-call}
			{description}';
			$template = $this->options->getOption('intro_direct_channel_template_wordpress_auth', $templateDefault);
		} else {
			$templateDefault = '{username} {status} {video-call}';
			$template = $this->options->getOption('intro_direct_channel_template', $templateDefault);
		}

		$template = $this->renderer->getTemplatedString($variables, $template, false);
		$template = $this->cleanTemplate($template);

		/**
		 * Filters template of the private chat channel intro.
		 *
		 * @since 3.4.0
		 *
		 * @param string $template A template with rendered dynamic variables
		 * @param WiseChatUser $user The user
		 */
		return apply_filters('wc_direct_intro_template', $template, $user);
	}

	private function cleanTemplate($template) {
		$template = preg_replace('/\R+/mu', "\n", $template);
		$template = trim($template);

		return $template;
	}

	/**
	 * @param WiseChatUser $user
	 * @return array
	 */
	private function getAnonymousUserVariables($user) {
		$variables = array(
			'username' => $user->getName(),
			'name' => $user->getName()
		);
		$variables['role'] = $variables['roles'] = $this->options->getOption('message_anonymous_user', __('Anonymous user', 'wise-chat'));

		for ($i = 1; $i <= 7; $i++) {
			$variables['field'.$i] = '';
		}

		if ($user->hasDataProperty('fields')) {
			$fields = $user->getDataProperty('fields');
			foreach ($fields as $fieldKey => $fieldValue) {
				$variables['field'.$fieldKey] = $fieldValue;
			}
		}

		return $variables;
	}

	/**
	 * @param WiseChatUser $user
	 * @return array
	 */
	private function getUserVariables($user) {
		$wpUser = $user->getWordPressId() > 0 ? get_userdata($user->getWordPressId()) : false;
		if ($wpUser !== false) {
			$variables = $this->getWPUserVariables($user, $wpUser);
		} else {
			$variables = $this->getAnonymousUserVariables($user);
		}

		// common variables:
		$avatarSrc = $this->userService->getUserAvatar($user);
		$variables['avatar'] = sprintf('[img src="%s" className="wcAvatar"]', $avatarSrc);
		$variables['avatar-src'] = $avatarSrc;
		$variables['name'] = $user->getName();
		$variables['status'] = sprintf('[span className="%s" content=""]', $this->channelUsersDAO->isOnline($user->getId()) ? 'wcStatus wcOnline' : 'wcStatus wcOffline');
		$variables['video-call'] = sprintf('[video-call channelId="%s"]', $this->clientSide->encryptDirectChannelId($user->getId()));

		return $variables;
	}

	/**
	 * @param WiseChatUser $user
	 * @param WP_User $wpUser
	 * @return string[]
	 */
	private function getWPUserVariables($user, $wpUser) {
		global $wp_roles;

		$variables = array(
			'name-linked' => sprintf('[link src="%s" name="%s"]', $this->userService->getUserProfileLink($user, $user->getName(), $user->getWordPressId()), $user->getName()),
			'role' => '',
			'roles' => '',
			'id' => $wpUser->ID,
			'username' => $wpUser->user_login,
			'displayname' => $wpUser->display_name,
			'email' => $wpUser->user_email,
			'firstname' => $wpUser->user_firstname,
			'lastname' => $wpUser->user_lastname,
			'nickname' => $wpUser->nickname,
			'description' => $wpUser->user_description,
			'website' => $wpUser->user_url,
			'website-linked' => $wpUser->user_url ? sprintf('[link src="%s"]', $wpUser->user_url) : ''
		);

		if ($this->options->isOptionEnabled('ui_templates_include_all_meta', true)) {
			$metaFields = get_user_meta($wpUser->ID, '', true);
			foreach ($metaFields as $metaKey => $metaField) {
				if (!in_array($metaKey, array('session_tokens', 'wp_capabilities')) && is_array($metaField) && count($metaField) > 0) {
					$variables[$metaKey] = $metaField[0];
				}
			}
		}

		$wpUserRoles = $wpUser->roles;
		if ($wpUserRoles !== null && is_array($wpUserRoles) && is_array($wp_roles->roles)) {
			foreach ($wpUserRoles as $key => $role) {
				$wpUserRoles[$key] = array_key_exists($role, $wp_roles->roles) ? translate_user_role($wp_roles->roles[$role]['name']) : $role;
			}

			$variables['role'] = reset($wpUserRoles);
			$variables['roles'] = implode(', ', $wpUserRoles);
		}

		return $variables;
	}

	/**
	 * @param WiseChatUser $user
	 * @return string[]
	 */
	private function getExternalUserVariables($user) {
		$variables = array(
			'role' => '',
			'roles' => '',
			'name-linked' => sprintf('[link src="%s" name="%s"]', $this->userService->getUserProfileLink($user, $user->getName(), $user->getWordPressId()), $user->getName())
		);

		$variables['role'] = $variables['roles'] = $this->options->getOption('message_anonymous_user', __('Anonymous user', 'wise-chat'));

		return $variables;
	}

}