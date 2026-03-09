<?php

namespace Kainex\WiseChat\Rendering;

use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Services\ClientSide\ClientChannel;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Options;
use WP_User;

/**
 * Building templates for various parts of chat's UI.
 *
 * @author Kainex <contact@kainex.pl>
 */
class UITemplates {

	/** @var ChannelUsersDAO */
	protected $channelUsersDAO;

	/** @var UserService */
	protected $userService;

	/** @var Renderer */
	protected $renderer;

	/** @var UsersDAO */
	protected $usersDAO;

	/** @var ClientSide */
	protected $clientSide;

	/**
	 * @var Options
	 */
	protected $options;

	/**
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param UserService $userService
	 * @param Renderer $renderer
	 * @param UsersDAO $usersDAO
	 * @param ClientSide $clientSide
	 * @param Options $options
	 */
	public function __construct(ChannelUsersDAO $channelUsersDAO, UserService $userService, Renderer $renderer, UsersDAO $usersDAO, ClientSide $clientSide, Options $options) {
		$this->channelUsersDAO = $channelUsersDAO;
		$this->userService = $userService;
		$this->renderer = $renderer;
		$this->usersDAO = $usersDAO;
		$this->clientSide = $clientSide;
		$this->options = $options;
	}

	/**
	 * @param User $user
	 * @param User $recipientUser
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

	public function hasWelcomeMessage($user, $recipientUser): bool {
		if ($recipientUser->getWordPressId() > 0) {
			$metaName = 'wc_live_chat_welcome_message_wordpress_user';
		} else {
			$metaName = 'wc_live_chat_welcome_message';
		}
		$welcomeMessageTemplate = $this->usersDAO->getWpUserMeta($user->getWordPressId(), $metaName);

		return strlen($welcomeMessageTemplate) > 0;
	}

	private function cleanTemplate($template) {
		$template = preg_replace('/\R+/mu', "\n", $template);
		$template = trim($template);

		return $template;
	}

	/**
	 * @param User $user
	 * @return array
	 */
	private function getAnonymousUserVariables($user) {
		$variables = array(
			'username' => $user->getName(),
			'name' => $user->getName()
		);
		$variables['role'] = $variables['roles'] = __('Anonymous user', 'wise-chat');

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


	private function getUserVariables(User $user, ?ClientChannel $clientChannel = null): array {
		$wpUser = $user->getWordPressId() > 0 ? $this->userService->getWpUserByID($user->getWordPressId()) : null;
		if ($wpUser !== null) {
			$variables = $this->getWPUserVariables($user, $wpUser);
		} else if ($user->getExternalType()) {
			$variables = $this->getExternalUserVariables($user);
		} else {
			$variables = $this->getAnonymousUserVariables($user);
		}

		// common variables:
		if ($clientChannel !== null) {
			$variables['avatar'] = sprintf('[img src="%s" className="wcAvatar"]', $clientChannel->getAvatar());
			$variables['avatar-src'] = $clientChannel->getAvatar();
			$variables['name'] = $clientChannel->getName();
			$variables['status'] = sprintf('[span className="%s" content=""]', $clientChannel->isOnline() ? 'wcStatus wcOnline' : 'wcStatus wcOffline');
			$variables['video-call'] = sprintf('[video-call channelId="%s"]', $clientChannel->getId());
		} else {
			$avatarSrc = $this->userService->getUserAvatar($user);
			$variables['avatar'] = sprintf('[img src="%s" className="wcAvatar"]', $avatarSrc);
			$variables['avatar-src'] = $avatarSrc;
			$variables['name'] = $user->getName();
			$variables['status'] = sprintf('[span className="%s" content=""]', $this->channelUsersDAO->isOnline($user->getId(), $wpUser) ? 'wcStatus wcOnline' : 'wcStatus wcOffline');
			$variables['video-call'] = sprintf('[video-call channelId="%s"]', $this->clientSide->encryptDirectChannelId($user->getId()));
		}

		return $variables;
	}

	/**
	 * @param User $user
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
			$metaFields = get_user_meta($wpUser->ID, '', true); // HINT: cached by get_users() call

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
	 * @param User $user
	 * @return string[]
	 */
	private function getExternalUserVariables($user) {
		$variables = array(
			'role' => '',
			'roles' => '',
			'name-linked' => sprintf('[link src="%s" name="%s"]', $this->userService->getUserProfileLink($user, $user->getName(), $user->getWordPressId()), $user->getName())
		);

		switch ($user->getExternalType()) {
			case 'fb':
				$variables['role'] = $variables['roles'] = __('Facebook user', 'wise-chat');
				break;
			case 'tw':
				$variables['role'] = $variables['roles'] = __('Twitter user', 'wise-chat');
				break;
			case 'go':
				$variables['role'] = $variables['roles'] = __('Google user', 'wise-chat');
				break;
			default:
				$variables['role'] = $variables['roles'] = __('Anonymous user', 'wise-chat');
		}

		return $variables;
	}

}