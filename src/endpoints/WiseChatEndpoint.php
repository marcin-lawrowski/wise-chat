<?php

namespace Kainex\WiseChat\Endpoints;

use Exception;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\User\UserSettingsDAO;
use Kainex\WiseChat\DAO\UserMutesDAO;
use Kainex\WiseChat\DAO\ChannelsDAO;
use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\DAO\MessagesDAO;
use Kainex\WiseChat\Exceptions\UnauthorizedAccessException;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Rendering\Renderer;
use Kainex\WiseChat\Services\Channels\Listing\ChannelsSourcesService;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Services\Message\MessageReactionsService;
use Kainex\WiseChat\Services\User\UserFeedService;
use Kainex\WiseChat\Services\User\ActionsService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\User\AuthorizationService;
use Kainex\WiseChat\Services\User\UserEventsService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Services\UserMutesService;
use Kainex\WiseChat\Services\ChannelsService;
use Kainex\WiseChat\Services\HttpRequestService;
use Kainex\WiseChat\Services\UserBansService;
use Kainex\WiseChat\Services\MessagesService;
use Kainex\WiseChat\Services\PendingChatsService;
use Kainex\WiseChat\Services\PrivateMessagesRulesService;
use Kainex\WiseChat\Services\ChatService;
use Kainex\WiseChat\Traits\HttpUtils;
use Kainex\WiseChat\Crypt;
use Kainex\WiseChat\Options;

/**
 * Wise Chat base endpoints class
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatEndpoint {
	use HttpUtils;

	/** @var UserFeedService */
	protected $userFeedService;

	/**
	 * @var ClientSide
	 */
	protected $clientSide;

	/**
	 * @var MessagesDAO
	 */
	protected $messagesDAO;

	/**
	 * @var ChannelsDAO
	 */
	protected $channelsDAO;

	/**
	 * @var UsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var UserSettingsDAO
	 */
	protected $userSettingsDAO;

	/**
	 * @var ChannelUsersDAO
	 */
	protected $channelUsersDAO;

	/**
	 * @var UserMutesDAO
	 */
	protected $userMutesDAO;

	/**
	 * @var ActionsService
	 */
	protected $actions;

	/**
	 * @var Renderer
	 */
	protected $renderer;

	/**
	 * @var UserMutesService
	 */
	protected $userMutesService;

	/**
	 * @var UserBansService
	 */
	protected $bansService;

	/**
	 * @var MessagesService
	 */
	protected $messagesService;

	/**
	 * @var UserService
	 */
	protected $userService;

	/**
	 * @var ChatService
	 */
	protected $service;

	/**
	 * @var ChannelsService
	 */
	protected $channelsService;

	/** @var ChannelsSourcesService */
	protected $channelsSourcesService;

	/**
	 * @var AuthenticationService
	 */
	protected $authentication;

	/**
	 * @var UserEventsService
	 */
	protected $userEvents;

	/**
	 * @var AuthorizationService
	 */
	protected $authorization;

	/**
	 * @var PendingChatsService
	 */
	protected $pendingChatsService;

	/**
	 * @var PrivateMessagesRulesService
	 */
	protected $privateMessagesRulesService;

	/**
	 * @var HttpRequestService
	 */
	protected $httpRequestService;

	/**
	 * @var MessageReactionsService
	 */
	protected $messageReactionsService;

	/**
	 * @var Options
	 */
	protected $options;

	/**
	 * @param UserFeedService $userFeedService
	 * @param ClientSide $clientSide
	 * @param MessagesDAO $messagesDAO
	 * @param ChannelsDAO $channelsDAO
	 * @param UsersDAO $usersDAO
	 * @param UserSettingsDAO $userSettingsDAO
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param UserMutesDAO $userMutesDAO
	 * @param ActionsService $actions
	 * @param Renderer $renderer
	 * @param UserMutesService $userMutesService
	 * @param UserBansService $bansService
	 * @param MessagesService $messagesService
	 * @param UserService $userService
	 * @param ChatService $service
	 * @param ChannelsService $channelsService
	 * @param ChannelsSourcesService $channelsSourcesService
	 * @param AuthenticationService $authentication
	 * @param UserEventsService $userEvents
	 * @param AuthorizationService $authorization
	 * @param PendingChatsService $pendingChatsService
	 * @param PrivateMessagesRulesService $privateMessagesRulesService
	 * @param HttpRequestService $httpRequestService
	 * @param MessageReactionsService $messageReactionsService
	 * @param Options $options
	 */
	public function __construct(UserFeedService $userFeedService, ClientSide $clientSide, MessagesDAO $messagesDAO, ChannelsDAO $channelsDAO, UsersDAO $usersDAO, UserSettingsDAO $userSettingsDAO, ChannelUsersDAO $channelUsersDAO, UserMutesDAO $userMutesDAO, ActionsService $actions, Renderer $renderer, UserMutesService $userMutesService, UserBansService $bansService, MessagesService $messagesService, UserService $userService, ChatService $service, ChannelsService $channelsService, ChannelsSourcesService $channelsSourcesService, AuthenticationService $authentication, UserEventsService $userEvents, AuthorizationService $authorization, PendingChatsService $pendingChatsService, PrivateMessagesRulesService $privateMessagesRulesService, HttpRequestService $httpRequestService, MessageReactionsService $messageReactionsService, Options $options) {
		$this->userFeedService = $userFeedService;
		$this->clientSide = $clientSide;
		$this->messagesDAO = $messagesDAO;
		$this->channelsDAO = $channelsDAO;
		$this->usersDAO = $usersDAO;
		$this->userSettingsDAO = $userSettingsDAO;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->userMutesDAO = $userMutesDAO;
		$this->actions = $actions;
		$this->renderer = $renderer;
		$this->userMutesService = $userMutesService;
		$this->bansService = $bansService;
		$this->messagesService = $messagesService;
		$this->userService = $userService;
		$this->service = $service;
		$this->channelsService = $channelsService;
		$this->channelsSourcesService = $channelsSourcesService;
		$this->authentication = $authentication;
		$this->userEvents = $userEvents;
		$this->authorization = $authorization;
		$this->pendingChatsService = $pendingChatsService;
		$this->privateMessagesRulesService = $privateMessagesRulesService;
		$this->httpRequestService = $httpRequestService;
		$this->messageReactionsService = $messageReactionsService;
		$this->options = $options;
	}

	/**
	 * Checks if user is authenticated.
	 *
	 * @throws UnauthorizedAccessException
	 */
	protected function checkUserAuthentication() {
		if (!$this->authentication->isAuthenticated()) {
			throw new UnauthorizedAccessException('Not authenticated');
		}
	}

	protected function confirmUserAuthenticationOrEndRequest() {
		if (!$this->authentication->isAuthenticated()) {
			$this->sendBadRequestStatus();
			die('{ }');
		}
	}

	/**
	 * @throws UnauthorizedAccessException
	 */
	protected function checkUserAuthorization() {
		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			throw new UnauthorizedAccessException('Access denied');
		}
		if ($this->service->isChatRestrictedForCurrentUserRole()) {
			throw new UnauthorizedAccessException('Access denied');
		}
		if ($this->service->isChatRestrictedToCurrentUser()) {
			throw new UnauthorizedAccessException('Access denied');
		}
	}

	/**
	 * @throws UnauthorizedAccessException
	 */
	protected function checkBanned() {
		if ($this->bansService->isBanned()) {
			throw new UnauthorizedAccessException(__('You are blocked from using the chat', 'wise-chat'));
		}
	}

	/**
	 * @throws UnauthorizedAccessException
	 */
	protected function checkUserWriteAuthorization() {
		if (!$this->userService->isSendingMessagesAllowed() && !$this->authentication->isAuthenticatedExternally()) {
			throw new UnauthorizedAccessException('No write permission');
		}
	}

	/**
	 * @throws Exception
	 */
	protected function checkChatOpen() {
		if (!$this->service->isChatOpen()) {
			throw new Exception(__('The chat is closed now', 'wise-chat'));
		}
	}

	/**
	 * @param Channel $channel
	 * @throws Exception
	 */
	protected function checkChannel($channel) {
		if ($channel === null) {
			throw new Exception('Channel does not exist');
		}
	}

	/**
	 * @param Channel $channel
	 * @throws UnauthorizedAccessException
	 * @throws Exception
	 */
	protected function checkChannelAuthorization($channel) {
		if (!$this->channelsService->isUserAuthorizedForChannel($channel)) {
			throw new UnauthorizedAccessException('Not authorized in this channel');
		}
	}

	protected function generateCheckSum() {
		$checksum = $this->getParam('checksum');
		if ($checksum !== null) {
			$decoded = unserialize(Crypt::decryptFromString(base64_decode($checksum)));
			if (is_array($decoded)) {
				$decoded['ts'] = time();

				return base64_encode(Crypt::encryptToString(serialize($decoded)));
			}
		}
		return null;
	}

	protected function verifyCheckSum() {
		$checksum = $this->getParam('checksum');

		if ($checksum !== null) {
			$decoded = unserialize(Crypt::decryptFromString(base64_decode($checksum)));
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

	protected function checkUserRight($rightName) {
		if (!$this->usersDAO->hasCurrentWpUserRight($rightName) && !$this->usersDAO->hasCurrentBpUserRight($rightName)) {
			throw new UnauthorizedAccessException('Not enough privileges to execute this request');
		}
	}

	/**
	 * @param string $encryptedChannelId
	 * @return User
	 * @throws Exception
	 */
	protected function getUserFromEncryptedId($encryptedChannelId) {
		$channelTypeAndId = Crypt::decryptFromString($encryptedChannelId);
		if ($channelTypeAndId === null) {
			throw new Exception('Invalid channel');
		}

		if (strpos($channelTypeAndId, 'd|') !== false) {
			return $this->usersDAO->get(intval(str_replace('d|', '', $channelTypeAndId)));
		} else {
			throw new Exception('Unknown channel');
		}
	}

	protected function hasPublicChannelsAccess() {
		return ($this->options->getIntegerOption('mode', 0) === 0 && !($this->options->isOptionEnabled('classic_disable_channel', false)))
			|| ($this->options->getIntegerOption('mode', 0) === 1 && !($this->options->isOptionEnabled('fb_disable_channel', false)));
	}

}