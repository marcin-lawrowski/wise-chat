<?php

namespace Kainex\WiseChat\Services;

use Kainex\WiseChat\DAO\NotificationsDAO;
use Kainex\WiseChat\DAO\SentNotificationsDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\Notification;
use Kainex\WiseChat\Model\SentNotification;
use Kainex\WiseChat\Services\User\UserService;

/**
 * WiseChat notifications services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class NotificationsService {

	/**
	 * @var ChannelsService
	 */
	private $channelsService;

	/**
	 * @var NotificationsDAO
	 */
	private $notificationsDAO;

	/**
	 * @var SentNotificationsDAO
	 */
	private $sentNotificationsDAO;

	private UserService $userService;

	/**
	 * @param ChannelsService $channelsService
	 * @param NotificationsDAO $notificationsDAO
	 * @param SentNotificationsDAO $sentNotificationsDAO
	 * @param UserService $userService
	 */
	public function __construct(ChannelsService $channelsService, NotificationsDAO $notificationsDAO, SentNotificationsDAO $sentNotificationsDAO, UserService $userService) {
		$this->channelsService = $channelsService;
		$this->notificationsDAO = $notificationsDAO;
		$this->sentNotificationsDAO = $sentNotificationsDAO;
		$this->userService = $userService;
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