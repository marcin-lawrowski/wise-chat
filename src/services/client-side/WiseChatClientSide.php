<?php

/**
 * WiseChat client side utilities.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatClientSide {

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/**
	 * @var WiseChatMessagesService
	 */
	private $messagesService;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	 * @var WiseChatUserService
	 */
	private $userService;

	/**
	 * @var WiseChatUITemplates
	 */
	protected $uiTemplates;

	/**
	 * @var array
	 */
	private $plainDirectChannelsCache = array();

	public function __construct() {
		WiseChatContainer::load('WiseChatCrypt');

		$this->messagesService = WiseChatContainer::getLazy('services/WiseChatMessagesService');
		$this->options = WiseChatOptions::getInstance();
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->userService = WiseChatContainer::getLazy('services/user/WiseChatUserService');
		$this->uiTemplates = WiseChatContainer::getLazy('rendering/WiseChatUITemplates');
	}

	/**
	 * @param $user
	 * @return string
	 */
	public function getUserCacheId($user) {
		return $this->getInstanceId().'_'.WiseChatCrypt::encryptToString($user->getId());
	}

	/**
	 * Get chat's instance ID.
	 *
	 * @return string
	 */
	public function getInstanceId() {
		return sha1(serialize($this->options->getOption('channel')));
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptUserId($id) {
		return WiseChatCrypt::encryptToString($id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptDirectChannelId($id) {
		return WiseChatCrypt::encryptToString('d|'.$id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptPublicChannelId($id) {
		return WiseChatCrypt::encryptToString('c|'.$id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptMessageId($id) {
		return WiseChatCrypt::encryptToString($id);
	}

	/**
	 * @param integer[] $ids
	 * @return string[]
	 */
	public function encryptMessageIds($ids) {
		return array_map(function($id) {
			return WiseChatCrypt::encryptToString($id);
		}, $ids);
	}

	/**
	 * @param string $encryptedId
	 * @return integer
	 */
	public function decryptMessageId($encryptedId) {
		return intval(WiseChatCrypt::decryptFromString($encryptedId));
	}

	/**
	 * Decrypts the message ID and loads the message.
	 *
	 * @param string $encryptedMessageId
	 * @return WiseChatMessage
	 * @throws Exception If the message does not exist
	 */
	public function getMessageOrThrowException($encryptedMessageId) {
		$message = $this->messagesService->getById($this->decryptMessageId($encryptedMessageId));
		if ($message === null) {
			throw new \Exception('The message does not exist');
		}

		return $message;
	}

	/**
	 * Returns plain array representation of channel user object.
	 *
	 * @param WiseChatChannelUser $channelUser
	 * @return array
	 */
	public function getChannelUserAsPlainDirectChannel($channelUser) {
		return $this->getUserAsPlainDirectChannel($channelUser->getUser(), array(
			'online' => $channelUser->isActive(),
		));
	}

	/**
	 * Returns plain array representation of user object.
	 *
	 * @param WiseChatUser $user
	 * @param array $additionalDetails
	 * @return array
	 */
	public function getUserAsPlainDirectChannel($user, $additionalDetails = array()) {
		if (array_key_exists($user->getId(), $this->plainDirectChannelsCache)) {
			return $this->plainDirectChannelsCache[$user->getId()];
		}

		$currentUserId = $this->authentication->getUserIdOrNull();

		// text color defined by role:
		$textColor = $this->userService->getTextColorDefinedByUserRole($user);

		// custom text color:
		if ($this->options->isOptionEnabled('allow_change_text_color')) {
			$textColorProposal = $user->getDataProperty('textColor');
			if ($textColorProposal) {
				$textColor = $textColorProposal;
			}
		}

		// avatar:
		$avatarSrc = $this->options->isOptionEnabled('show_users_list_avatars', false) ? $this->userService->getUserAvatar($user) : null;

		$isCurrentUser = $currentUserId === $user->getId();

		// add roles as css classes:
		$roleClasses = $this->options->isOptionEnabled('css_classes_for_user_roles', false) ? $this->userService->getCssClassesForUserRoles($user) : null;

		$countryFlagSrc = null;
		$countryCode = null;
		$country = null;
		$city = null;

		if ($this->options->isOptionEnabled('collect_user_stats', true) && $this->options->isOptionEnabled('show_users_flags', false)) {
			$countryCode = $user->getDataProperty('countryCode');
			$country = $user->getDataProperty('country');
			if ($countryCode) {
				$countryFlagSrc = $this->options->getFlagURL(strtolower($countryCode));
			}
		}
		if ($this->options->isOptionEnabled('collect_user_stats', true) && $this->options->isOptionEnabled('show_users_city_and_country', false)) {
			$city = $user->getDataProperty('city');
			$countryCode = $user->getDataProperty('countryCode');
		}

		$isAllowed = false;
		$url = null;
		if ($this->options->isOptionEnabled('enable_private_messages', false) || !$this->options->isOptionEnabled('users_list_linking', false)) {
			$isAllowed = true;
		} else if ($this->options->isOptionEnabled('users_list_linking', false)) {
			$url = $this->userService->getUserProfileLink($user, $user->getName(), $user->getWordPressId());
		}

		return $this->plainDirectChannelsCache[$user->getId()] = array_merge([
			'id' => $this->encryptDirectChannelId($user->getId()),
			'name' => $user->getName(),
			'type' => 'direct',
			'readOnly' => !$isAllowed,
			'url' => $url,
			'textColor' => $textColor,
			'avatar' => $avatarSrc,
			'locked' => $isCurrentUser,
			'classes' => $roleClasses,
			'countryCode' => $countryCode,
			'country' => $country,
			'city' => $city,
			'countryFlagSrc' => $countryFlagSrc,
			'infoWindow' => $this->getInfoWindow($user),
			'intro' => $this->uiTemplates->getDirectChannelIntro($user),
			'online' => false
		], $additionalDetails);
	}

	private function getInfoWindow($user) {
		if (!$this->options->isOptionEnabled('show_users_list_info_windows', true)) {
			return null;
		}

		$avatarSrc = $this->options->isOptionEnabled('show_users_list_avatars', false) ? $this->userService->getUserAvatar($user) : null;

		return array(
			'avatar' => $avatarSrc,
			'name' => $user->getName(),
			'url' => $this->userService->getUserProfileLink($user, $user->getName(), $user->getWordPressId()),
			'content' => $this->uiTemplates->getInfoWindow($user)
		);
	}

}