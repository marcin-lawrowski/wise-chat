<?php

namespace Kainex\WiseChat\Services;

use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\DAO\SentNotificationsDAO;
use Kainex\WiseChat\DAO\UserNotificationsDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Message\Message;

/**
 * WiseChat user notifications services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserNotificationsService {

	/**
	 * @var ChannelsService
	 */
	private $channelsService;

	/**
	 * @var UserNotificationsDAO
	 */
	private $userNotificationsDAO;

	/**
	 * @var SentNotificationsDAO
	 */
	private $sentNotificationsDAO;

	/**
	 * @var UsersDAO
	 */
	private $usersDAO;

	/**
	 * @var ChannelUsersDAO
	 */
	private $channelUsersDAO;

	/**
	 * @var HttpRequestService
	 */
	private $httpRequestService;

	/**
	 * @param ChannelsService $channelsService
	 * @param UserNotificationsDAO $userNotificationsDAO
	 * @param SentNotificationsDAO $sentNotificationsDAO
	 * @param UsersDAO $usersDAO
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param HttpRequestService $httpRequestService
	 */
	public function __construct(ChannelsService $channelsService, UserNotificationsDAO $userNotificationsDAO, SentNotificationsDAO $sentNotificationsDAO, UsersDAO $usersDAO, ChannelUsersDAO $channelUsersDAO, HttpRequestService $httpRequestService) {
		$this->channelsService = $channelsService;
		$this->userNotificationsDAO = $userNotificationsDAO;
		$this->sentNotificationsDAO = $sentNotificationsDAO;
		$this->usersDAO = $usersDAO;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->httpRequestService = $httpRequestService;
	}

	/**
	 * Sends all notifications for message.
	 *
	 * @param Message $message
	 * @param Channel $channel
	 */
	public function send($message, $channel) {

	}

}