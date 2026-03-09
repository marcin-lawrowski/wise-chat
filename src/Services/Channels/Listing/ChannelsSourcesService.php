<?php

namespace Kainex\WiseChat\Services\Channels\Listing;

use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Services\ChannelsService;
use Kainex\WiseChat\Services\ChatService;
use Kainex\WiseChat\Options;

/**
 * WiseChat channels sources.
 *
 * @author Kainex <contact@kainex.pl>
 */
class ChannelsSourcesService {

	/** @var AuthenticationService */
	private $authentication;

	/** @var UserService */
	private $userService;

	/** @var ChatService */
	private $service;

	/** @var ChannelsService */
	private $channelsService;

	/** @var ChannelUsersDAO */
	protected $channelUsersDAO;

	/** @var UsersDAO */
	private $usersDAO;

	/** @var Options */
	private $options;

	public const MONITORED_OPEN_ONLY = 1;

	/**
	 * @param AuthenticationService $authentication
	 * @param UserService $userService
	 * @param ChatService $service
	 * @param ChannelsService $channelsService
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param UsersDAO $usersDAO
	 * @param Options $options
	 */
	public function __construct(AuthenticationService $authentication, UserService $userService, ChatService $service, ChannelsService $channelsService, ChannelUsersDAO $channelUsersDAO, UsersDAO $usersDAO, Options $options) {
		$this->authentication = $authentication;
		$this->userService = $userService;
		$this->service = $service;
		$this->channelsService = $channelsService;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->usersDAO = $usersDAO;
		$this->options = $options;
	}

	/**
	 * Returns all constant and bookmarked channels.
	 * Direct channels are excluded.
	 *
	 * @return Channel[]
	 * @throws \Exception
	 */
	public function getConstantAndBookmarkedChannels(): array {
		if (!$this->channelsService->arePublicChannelsEnabled()) {
			return [];
		}

		$allPersonalChannels = array_merge($this->channelsService->getConstantChannels(), $this->channelsService->getUserChannels($this->authentication->getUserIdOrNull()));

		// remove all direct channels (if present):
		$channels = [];
		foreach ($allPersonalChannels as $personalChannel) {
			if ($personalChannel->getType() !== Channel::TYPE_DIRECT) {
				$channels[$personalChannel->getId()] = $personalChannel;
			}
		}

		return array_values($channels);
	}

	/**
	 * Returns all opened channels of the current user.
	 *
	 * @return Channel[]
	 * @throws \Exception
	 */
	public function getOpenChannels(): array {
		$openChannels = $this->channelsService->getOpenChannels($this->authentication->getUserIdOrNull());

		$channels = [];
		foreach ($openChannels as $openChannel) {
			if ($openChannel->getType() === Channel::TYPE_DIRECT) {
				if ($this->options->isOptionEnabled('enable_private_messages')) {
					$channels[] = $openChannel;
				}
			} else {
				if ($this->channelsService->arePublicChannelsEnabled()) {
					$channels[] = $openChannel;
				}
			}
		}

		return $channels;
	}

	/**
	 * Returns all channels permitted to check for new messages in there.
	 *
	 * @return Channel[]
	 * @throws \Exception
	 */
	public function getMonitoredChannels(): array {
		$allPersonalChannels = array_merge($this->channelsService->getUserChannels($this->authentication->getUserIdOrNull()), $this->channelsService->getOpenChannels($this->authentication->getUserIdOrNull()));

		$channels = [];
		if ($this->channelsService->arePublicChannelsEnabled()) {
			$channels = array_merge($this->channelsService->getConstantChannels(), $allPersonalChannels);
		} else {
			// remove all non-direct channels:
			foreach ($allPersonalChannels as $personalChannel) {
				if (!in_array($personalChannel->getType(), [Channel::TYPE_PUBLIC, Channel::TYPE_PRIVATE])) {
					$channels[] = $personalChannel;
				}
			}
		}

		$authorized = [];
		foreach ($channels as $channel) {
			if ($this->channelsService->isUserAuthorizedInChannel($channel)) {
				$authorized[] = $channel;
			}
		}

		$filtered = [];
		if ($this->options->isOptionEnabled('enable_private_messages')) {
			$filtered = $authorized;
		} else {
			foreach ($authorized as $authorizedChannel) {
				if ($authorizedChannel->getType() !== Channel::TYPE_DIRECT) {
					$filtered[] = $authorizedChannel;
				}
			}
		}

		$noDuplicates = [];
		foreach ($filtered as $channel) {
			$noDuplicates[$channel->getId()] = $channel;
		}

		return array_values($noDuplicates);
	}

	/**
	 * Returns users (alias "direct channels") applicable for displaying in the browser.
	 *
	 * @return User[]
	 */
	public function getDirectChannels(): array {
		$onlineUsers = $this->userService->getOnlineUsers([
			'limitToWordPressUserIDs' => $this->options->getOption('access_users', array()) ?? []
		]);

		$users = array_merge($onlineUsers, $this->getWordPressBasedUsers($onlineUsers));

		// filter to return only those available to the current user
		return array_filter($users, function(User $user) { return $this->isUserVisible($user); });
	}

	private function isUserVisible(User $user): bool {
		// do not output non-friends if BP integration is on:
		if (!$this->userService->isUsersConnectionAvailable($this->authentication->getUser(), $user)) {
			return false;
		}

		// do not output anonymous users:
		if ($this->service->isChatAllowedForWPUsersOnly() && $this->userService->isAnonymousUser($user)) {
			return false;
		}

		// hide users
		$excludeUsers = [];
		if (in_array($user->getWordPressId(), $excludeUsers)) {
			return false;
		}

		// hide chosen roles:
		$hideRoles = $this->options->getOption('users_list_hide_roles', array());
		if (is_array($hideRoles) && count($hideRoles) > 0 && $user->getWordPressId() > 0) {
			$wpUser = $this->usersDAO->getWpUserByID($user->getWordPressId());
			if ($wpUser !== null && is_array($wpUser->roles) && count(array_intersect($hideRoles, $wpUser->roles)) > 0) {
				return false;
			}
		}

		// do not render anonymous users:
		if ($this->options->isOptionEnabled('users_list_hide_anonymous', false) && $this->userService->isAnonymousUser($user)) {
			return false;
		}

		return true;
	}

	/**
	 * Gets users based on WordPress users. Online users are not included.
	 *
	 * @param User[] $onlineUsers
	 * @return User[]
	 */
	private function getWordPressBasedUsers(array $onlineUsers): array {
		if (!$this->options->isOptionEnabled('users_list_offline_enable', true)) {
			return [];
		}

		// collect map of channel users:
		$channelWPUsersMap = array();
		foreach ($onlineUsers as $onlineUser) {
			$channelWPUsersMap[$onlineUser->getWordPressId()] = true;
		}

		// append offline users:
		$directChannels = [];
		$accessUsers = $this->options->getOption('access_users', array());
		$excludeUsers = [];

		$searchParameters = [
			'include' => is_array($accessUsers) ? $accessUsers : array(),
			'exclude' => $excludeUsers
		];

		// if offline users are disabled then read AI bots only:
		if (!$this->options->isOptionEnabled('users_list_offline_enable', true)) {
			$searchParameters['meta_key'] = 'wc_ai_bot';
			$searchParameters['meta_value'] = '1';
		}

		$wpUsers = $this->usersDAO->getWPUsers($searchParameters);
		$chatUsersMap = $this->usersDAO->getLatestChatUsersByWordPressIds($wpUsers);
		foreach ($wpUsers as $wpUser) {
			if (array_key_exists($wpUser->ID, $channelWPUsersMap)) {
				continue;
			}

			$chatUser = array_key_exists($wpUser->ID, $chatUsersMap) ? $chatUsersMap[$wpUser->ID] : null;
			if ($chatUser === null) {
				// create an in-memory user:
				$chatUser = new User();
				$chatUser->setId('v' . $wpUser->ID);
				$chatUser->setName($this->usersDAO->getChatUserNameFromWpUser($wpUser));
				$chatUser->setWordPressId($wpUser->ID);
			}

			$directChannels[] = $chatUser;
		}

		return $directChannels;
	}

	/**
	 * Gets live chat operators.
	 *
	 * @return User[]
	 */
	public function getOperators(): array {
		$operators = [];
		$userIDs = $this->options->getOption('auto_open', array()) ?? [];
		if (empty($userIDs)) {
			return [];
		}
		$searchParameters = [
			'include' => $userIDs
		];

		$wpUsers = $this->usersDAO->getWPUsers($searchParameters);
		$chatUsersMap = $this->usersDAO->getLatestChatUsersByWordPressIds($wpUsers);
		foreach ($wpUsers as $wpUser) {
			$chatUser = array_key_exists($wpUser->ID, $chatUsersMap) ? $chatUsersMap[$wpUser->ID] : null;
			if ($chatUser === null) {
				// create an in-memory user:
				$chatUser = new User();
				$chatUser->setId('v' . $wpUser->ID);
				$chatUser->setName($this->usersDAO->getChatUserNameFromWpUser($wpUser));
				$chatUser->setWordPressId($wpUser->ID);
			} else if ($chatUser->getId() === $this->authentication->getUser()->getID()) {
				continue;
			}

			$operators[] = $chatUser;
		}

		return $operators;
	}

}