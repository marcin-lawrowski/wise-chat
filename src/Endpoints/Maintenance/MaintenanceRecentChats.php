<?php

namespace Kainex\WiseChat\Endpoints\Maintenance;

use Kainex\WiseChat\DAO\ChannelsDAO;
use Kainex\WiseChat\DAO\MessagesDAO;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Services\Message\MessageReactionsService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Services\MessagesService;
use Kainex\WiseChat\Options;

/**
 * Class for loading recent chats.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MaintenanceRecentChats {

	private MessagesDAO $messagesDAO;
	private ChannelsDAO $channelsDAO;
	private MessagesService $messagesService;
	private UserService $userService;
	private AuthenticationService $authentication;
	private Options $options;
	private ClientSide $clientSide;
	private MessageReactionsService $messageReactionsService;

	/**
	 * @param MessagesDAO $messagesDAO
	 * @param ChannelsDAO $channelsDAO
	 * @param MessagesService $messagesService
	 * @param UserService $userService
	 * @param AuthenticationService $authentication
	 * @param Options $options
	 * @param ClientSide $clientSide
	 * @param MessageReactionsService $messageReactionsService
	 */
	public function __construct(MessagesDAO $messagesDAO, ChannelsDAO $channelsDAO, MessagesService $messagesService, UserService $userService, AuthenticationService $authentication, Options $options, ClientSide $clientSide, MessageReactionsService $messageReactionsService) {
		$this->messagesDAO = $messagesDAO;
		$this->channelsDAO = $channelsDAO;
		$this->messagesService = $messagesService;
		$this->userService = $userService;
		$this->authentication = $authentication;
		$this->options = $options;
		$this->clientSide = $clientSide;
		$this->messageReactionsService = $messageReactionsService;
	}

	/**
	 * Returns recent direct chats.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getRecentChats(): array {
		$unreadMessages = $this->messagesService->getUnreadMessages($this->authentication->getUser());
		$unreadChannelsIDs = [];
		foreach ($unreadMessages as $message) {
			$unreadChannelsIDs[] = $message->getChannelId();
		}

		$newestDirectMessages = $this->messagesDAO->getAllNewestDirectMessages($this->authentication->getUser(), $this->options->getIntegerOption('recent_chats_limit', 20));
		$channelsIDs = [];
		foreach ($newestDirectMessages as $message) {
			$channelsIDs[] = $message->getChannelId();
		}

		$channels = $this->channelsDAO->getAllById($channelsIDs);

		$clientChannels = $this->clientSide->convertToClientChannels($channels);
		$this->messageReactionsService->cacheReactions($newestDirectMessages);
		$this->userService->cacheUsersOfMessages($newestDirectMessages);

		$recentChats = [];
		foreach ($newestDirectMessages as $message) {
			$attributes = array(
				'read' => !in_array($message->getChannelId(), $unreadChannelsIDs)
			);
			if (!isset($clientChannels[$message->getChannelId()])) {
				continue;
			}
			$clientChannel = $clientChannels[$message->getChannelId()];

			$recentChats[] = $this->clientSide->toPlainMessage($message, $clientChannel, $attributes);
		}
		return $recentChats;
	}

}