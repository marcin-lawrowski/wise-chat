<?php

namespace Kainex\WiseChat\Services\User;

use Exception;
use Kainex\WiseChat\DAO\User\UserFeedDAO;
use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\Model\UserFeed;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Services\ChannelsService;
use Kainex\WiseChat\Services\HttpRequestService;
use Kainex\WiseChat\Crypt;
use Kainex\WiseChat\Options;
use Kainex\WiseChat\Services\Message\MessageReactionsService;
use Kainex\WiseChat\Services\MessagesService;

/**
 * User feed service.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserFeedService extends UserFeedDAO {

	const FEED_LIMIT = 5;

	/**
	 * @var ChannelsService
	 */
	protected $channelsService;

	/** @var UserService */
	private $userService;

	/** @var ClientSide */
	private $clientSide;

	/**
	 * @var ChannelUsersDAO
	 */
	private $channelUsersDAO;

	/** @var Options */
	private $options;

	/**
	 * @var HttpRequestService
	 */
	private $httpRequestService;

	private MessagesService $messagesService;
	private MessageReactionsService $messageReactionsService;

	/**
	 * @param ChannelsService $channelsService
	 * @param UserService $userService
	 * @param ClientSide $clientSide
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param Options $options
	 * @param HttpRequestService $httpRequestService
	 * @param MessagesService $messagesService
	 * @param MessageReactionsService $messageReactionsService
	 */
	public function __construct(ChannelsService $channelsService, UserService $userService, ClientSide $clientSide, ChannelUsersDAO $channelUsersDAO, Options $options, HttpRequestService $httpRequestService, \Kainex\WiseChat\Services\MessagesService $messagesService, \Kainex\WiseChat\Services\Message\MessageReactionsService $messageReactionsService) {
		$this->channelsService = $channelsService;
		$this->userService = $userService;
		$this->clientSide = $clientSide;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->options = $options;
		$this->httpRequestService = $httpRequestService;
		$this->messagesService = $messagesService;
		$this->messageReactionsService = $messageReactionsService;
	}

	/**
	 * @param integer $userId
	 * @param string $type
	 * @param int|null $targetId
	 * @param array $data
	 * @return UserFeed
	 * @throws Exception
	 */
	public function create(int $userId, string $type, ?int $targetId = null, array $data = []): UserFeed {
    	return new UserFeed();
	}

	private function sendNewEntryNotifications(UserFeed $userFeed) {

	}

	/**
	 * Returns latest user feed as plain array.
	 *
	 * @param User $user
	 * @param int|null $afterId
	 * @return array
	 */
	public function getLatest(User $user, ?int $afterId = null): array {
		return [];
	}

	public function markAsSeen(int $userFeedEntryId, int $userId) {

	}

}