<?php

WiseChatContainer::load('exceptions/WiseChatUnauthorizedAccessException');

/**
 * Wise Chat base endpoints class
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatEndpoint {

	/**
	 * @var WiseChatClientSide
	 */
	protected $clientSide;

	/**
	 * @var WiseChatMessagesDAO
	 */
	protected $messagesDAO;

	/**
	 * @var WiseChatChannelsDAO
	 */
	protected $channelsDAO;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatUserSettingsDAO
	 */
	protected $userSettingsDAO;

	/**
	 * @var WiseChatChannelUsersDAO
	 */
	protected $channelUsersDAO;

	/**
	 * @var WiseChatBansDAO
	 */
	protected $bansDAO;

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	 * @var WiseChatRenderer
	 */
	protected $renderer;

	/**
	 * @var WiseChatBansService
	 */
	protected $bansService;

	/**
	 * @var WiseChatKicksService
	 */
	protected $kicksService;

	/**
	 * @var WiseChatMessagesService
	 */
	protected $messagesService;

	/**
	 * @var WiseChatUserService
	 */
	protected $userService;

	/**
	 * @var WiseChatService
	 */
	protected $service;

	/**
	 * @var WiseChatChannelsService
	 */
	protected $channelsService;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	 * @var WiseChatUserEvents
	 */
	protected $userEvents;

	/**
	 * @var WiseChatAuthorization
	 */
	protected $authorization;

	/**
	 * @var WiseChatHttpRequestService
	 */
	protected $httpRequestService;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	private $arePostSlashesStripped = false;

	public function __construct() {
		$this->options = WiseChatOptions::getInstance();

		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->userEvents = WiseChatContainer::getLazy('services/user/WiseChatUserEvents');
		$this->authorization = WiseChatContainer::getLazy('services/user/WiseChatAuthorization');
		$this->messagesDAO = WiseChatContainer::get('dao/WiseChatMessagesDAO');
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
		$this->userSettingsDAO = WiseChatContainer::getLazy('dao/user/WiseChatUserSettingsDAO');
		$this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
		$this->actions = WiseChatContainer::getLazy('services/user/WiseChatActions');
		$this->channelsDAO = WiseChatContainer::getLazy('dao/WiseChatChannelsDAO');
		$this->bansDAO = WiseChatContainer::getLazy('dao/WiseChatBansDAO');
		$this->renderer = WiseChatContainer::getLazy('rendering/WiseChatRenderer');
		$this->bansService = WiseChatContainer::getLazy('services/WiseChatBansService');
		$this->kicksService = WiseChatContainer::getLazy('services/WiseChatKicksService');
		$this->messagesService = WiseChatContainer::getLazy('services/WiseChatMessagesService');
		$this->userService = WiseChatContainer::getLazy('services/user/WiseChatUserService');
		$this->service = WiseChatContainer::getLazy('services/WiseChatService');
		$this->channelsService = WiseChatContainer::getLazy('services/WiseChatChannelsService');
		$this->httpRequestService = WiseChatContainer::getLazy('services/WiseChatHttpRequestService');
		$this->clientSide = WiseChatContainer::getLazy('services/client-side/WiseChatClientSide');

		WiseChatContainer::load('WiseChatCrypt');
		WiseChatContainer::load('services/user/WiseChatUserService');
		WiseChatContainer::load('services/WiseChatChannelsService');
	}

	/**
	 * @param WiseChatMessage $message
	 * @param $channelId
	 * @param $channelName
	 * @param array $attributes
	 * @return array
	 */
	protected function toPlainMessage($message, $channelId, $attributes = array()) {
		$textColorAffectedParts = (array)$this->options->getOption("text_color_parts", array('message', 'messageUserName'));
		$classes = '';
		$wpUser = $this->usersDAO->getWpUserByID($message->getWordPressUserId());
		if ($this->options->isOptionEnabled('css_classes_for_user_roles', false)) {
			$classes = $this->userService->getCssClassesForUserRoles($message->getUser(), $wpUser);
		}

		$messagePlain = array(
			'id' => $this->clientSide->encryptMessageId($message->getId()),
			'own' => $message->getUserId() === $this->authentication->getUserIdOrNull(),
			'text' => $message->getText(),
			'channel' => array(
				'id' => $channelId,
				'name' => $message->getChannelName(),
				'type' => 'public',
				'readOnly' => false
			),
			'color' => in_array('message', $textColorAffectedParts) ? $this->userService->getUserTextColor($message->getUser()) : null,
			'cssClasses' => $classes,
			'timeUTC' => gmdate('c', $message->getTime()),
			'sortKey' => $message->getTime().$message->getId(),
			'sender' => $this->getMessageSender($message, $wpUser)
		);

		$messagePlain = array_merge($messagePlain, $attributes);

		return $messagePlain;
	}

	private function getMessageSender($message, $wpUser) {
		$textColorAffectedParts = (array) $this->options->getOption("text_color_parts", array('message', 'messageUserName'));

		return array(
			'id' => $this->clientSide->encryptUserId($message->getUserId()),
			'name' => $message->getUserName(),
			'source' => $wpUser !== null ? 'w' : 'a',
			'current' => $this->authentication->getUser()->getId() == $message->getUserId(),
			'color' => in_array('messageUserName', $textColorAffectedParts) ? $this->userService->getUserTextColor($message->getUser()) : null,
			'profileUrl' => $this->options->getIntegerOption('link_wp_user_name', 0) === 1 ? $this->userService->getUserProfileLink($message->getUser(), $message->getUserName(), $message->getWordPressUserId()) : null,
			'avatarUrl' => $this->options->isOptionEnabled('show_avatars', true) ? $this->userService->getUserAvatarFromMessage($message) : null
		);
	}

	protected function getPostParam($name, $default = null) {
		if (!$this->arePostSlashesStripped) {
			$_POST = stripslashes_deep($_POST);
			$this->arePostSlashesStripped = true;
		}

		return array_key_exists($name, $_POST) ? $_POST[$name] : $default;
	}

	protected function getGetParam($name, $default = null) {
		return array_key_exists($name, $_GET) ? $_GET[$name] : $default;
	}

	protected function getParam($name, $default = null) {
		$getParam = $this->getGetParam($name);
		if ($getParam === null) {
			return $this->getPostParam($name, $default);
		}

		return $getParam;
	}

	/**
	 * @param array $params
	 * @throws Exception
	 */
	protected function checkGetParams($params) {
		foreach ($params as $param) {
			if ($this->getGetParam($param) === null) {
				throw new Exception('Required parameters are missing');
			}
		}
	}

	/**
	 * @param array $params
	 * @throws Exception
	 */
	protected function checkPostParams($params) {
		foreach ($params as $param) {
			if ($this->getPostParam($param) === null) {
				throw new Exception('Required parameters are missing');
			}
		}
	}

	/**
	 * Checks if user is authenticated.
	 *
	 * @throws WiseChatUnauthorizedAccessException
	 */
	protected function checkUserAuthentication() {
		if (!$this->authentication->isAuthenticated()) {
			throw new WiseChatUnauthorizedAccessException('Not authenticated');
		}
	}

	protected function confirmUserAuthenticationOrEndRequest() {
		if (!$this->authentication->isAuthenticated()) {
			$this->sendBadRequestStatus();
			die('{ }');
		}
	}

	/**
	 * @throws WiseChatUnauthorizedAccessException
	 */
	protected function checkUserAuthorization() {
		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			throw new WiseChatUnauthorizedAccessException('Access denied');
		}
		if ($this->service->isChatRestrictedForCurrentUserRole()) {
			throw new WiseChatUnauthorizedAccessException('Access denied');
		}
		if ($this->service->isChatRestrictedToCurrentUser()) {
			throw new WiseChatUnauthorizedAccessException('Access denied');
		}
	}

	/**
	 * @throws WiseChatUnauthorizedAccessException
	 */
	protected function checkIpNotKicked() {
		if (isset($_SERVER['REMOTE_ADDR']) && $this->kicksService->isIpAddressKicked($_SERVER['REMOTE_ADDR'])) {
			throw new WiseChatUnauthorizedAccessException($this->options->getOption('message_error_12', __('You are blocked from using the chat', 'wise-chat')));
		}
	}

	/**
	 * @throws WiseChatUnauthorizedAccessException
	 */
	protected function checkUserWriteAuthorization() {
		if (!$this->userService->isSendingMessagesAllowed()) {
			throw new WiseChatUnauthorizedAccessException('No write permission');
		}
	}

	/**
	 * @throws Exception
	 */
	protected function checkChatOpen() {
		if (!$this->service->isChatOpen()) {
			throw new Exception($this->options->getEncodedOption('message_error_5', 'The chat is closed now'));
		}
	}

	/**
	 * @param WiseChatChannel $channel
	 * @throws Exception
	 */
	protected function checkChannel($channel) {
		if ($channel === null) {
			throw new Exception('Channel does not exist');
		}
	}

	/**
	 * @param WiseChatChannel $channel
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	protected function checkChannelAuthorization($channel) {
		if (!$this->authorization->isUserAuthorizedForChannel($channel)) {
			throw new WiseChatUnauthorizedAccessException('Not authorized in this channel');
		}
	}

	protected function generateCheckSum() {
		$checksum = $this->getParam('checksum');
		if ($checksum !== null) {
			$decoded = unserialize(WiseChatCrypt::decryptFromString(base64_decode($checksum)));
			if (is_array($decoded)) {
				$decoded['ts'] = time();

				return base64_encode(WiseChatCrypt::encryptToString(serialize($decoded)));
			}
		}
		return null;
	}

	protected function verifyCheckSum() {
		$checksum = $this->getParam('checksum');

		if ($checksum !== null) {
			$decoded = unserialize(WiseChatCrypt::decryptFromString(base64_decode($checksum)));
			if (is_array($decoded)) {
				$timestamp = array_key_exists('ts', $decoded) ? $decoded['ts'] : time();
				$validityTime = $this->options->getIntegerOption('ajax_validity_time', 1440) * 60;
				if ($timestamp + $validityTime < time()) {
					$this->sendNotFoundStatus();
					die();
				}

				$this->options->replaceOptions($decoded);
			}
		}
	}

	protected function verifyXhrRequest() {
		if (!$this->options->isOptionEnabled('enabled_xhr_check', true)) {
			return true;
		}
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			return true;
		} else {
			$this->sendNotFoundStatus();
			die();
		}
	}

	protected function checkUserRight($rightName) {
		if (!$this->usersDAO->hasCurrentWpUserRight($rightName) && !$this->usersDAO->hasCurrentBpUserRight($rightName)) {
			throw new WiseChatUnauthorizedAccessException('Not enough privileges to execute this request');
		}
	}

	/**
	 * @param string $encryptedChannelId
	 * @return WiseChatChannel|null
	 * @throws Exception
	 */
	protected function getChannelFromEncryptedId($encryptedChannelId) {
		$channelTypeAndId = WiseChatCrypt::decryptFromString($encryptedChannelId);
		if ($channelTypeAndId === null) {
			throw new Exception('Invalid channel');
		}

		if (strpos($channelTypeAndId, 'c|') !== false) {
			$channel = $this->channelsDAO->get(intval(str_replace('c|', '', $channelTypeAndId)));
			if ($channel && $channel->getName() === WiseChatChannelsService::PRIVATE_MESSAGES_CHANNEL) {
				throw new Exception('Unknown channel ID');
			}
		} else {
			throw new Exception('Unknown channel');
		}

		return $channel;
	}

	protected function sendBadRequestStatus() {
		header('HTTP/1.0 400 Bad Request', true, 400);
	}

	protected function sendUnauthorizedStatus() {
		header('HTTP/1.0 401 Unauthorized', true, 401);
	}

	protected function sendNotFoundStatus() {
		header('HTTP/1.0 404 Not Found', true, 404);
	}

	protected function jsonContentType() {
		header('Content-Type: application/json; charset='.get_option('blog_charset'));
	}
}