<?php

namespace Kainex\WiseChat\Services;

use Exception;
use Kainex\WiseChat\DAO\Channels\MembersDAO;
use Kainex\WiseChat\DAO\Channels\OpenUserChannelsDAO;
use Kainex\WiseChat\DAO\Channels\UserChannelsDAO;
use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\ChannelsDAO;
use Kainex\WiseChat\DAO\MessagesDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Channel\ChannelMember;
use Kainex\WiseChat\Model\Channel\OpenUserChannel;
use Kainex\WiseChat\Model\UserChannel;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Services\User\UserFeedService;
use Kainex\WiseChat\Services\User\ActionsService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\User\AuthorizationService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Crypt;
use Kainex\WiseChat\Options;
use Kainex\WiseChat\Settings;

/**
 * @author Kainex <contact@kaine.pl>
 */
class ChannelsService extends ChannelsDAO {

	const PRIVATE_MESSAGES_CHANNEL = '__private';

	/** @var UserFeedService */
	private $userFeedService;

	/**
     * @var UserService
     */
    private $userService;

	/** @var MembersDAO */
	private $membersDAO;

	/** @var UserChannelsDAO */
	private $userChannelsDAO;

	private ChannelUsersDAO $channelUsersDAO;

	/**
	 * @var UsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var AuthorizationService
	 */
	protected $authorization;

	/**
	 * @var AuthenticationService
	 */
	protected $authentication;

	/**
	* @var MessagesDAO
	*/
	protected $messagesDAO;

	/**
	 * @var MessagesService
	 */
	protected $messagesService;

	/**
	 * @var ActionsService
	 */
	protected $actions;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var ClientSide
	 */
	protected $clientSide;

	private OpenUserChannelsDAO $openUserChannelsDAO;
	private PrivateMessagesRulesService $privateMessagesRulesService;

	/**
	 * @param UserFeedService $userFeedService
	 * @param UserService $userService
	 * @param MembersDAO $membersDAO
	 * @param UserChannelsDAO $userChannelsDAO
	 * @param UsersDAO $usersDAO
	 * @param AuthorizationService $authorization
	 * @param AuthenticationService $authentication
	 * @param MessagesDAO $messagesDAO
	 * @param MessagesService $messagesService
	 * @param ActionsService $actions
	 * @param Options $options
	 * @param ClientSide $clientSide
	 * @param OpenUserChannelsDAO $openUserChannelsDAO
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param PrivateMessagesRulesService $privateMessagesRulesService
	 */
	public function __construct(UserFeedService $userFeedService, UserService $userService, MembersDAO $membersDAO, UserChannelsDAO $userChannelsDAO, UsersDAO $usersDAO, AuthorizationService $authorization, AuthenticationService $authentication, MessagesDAO $messagesDAO, MessagesService $messagesService, ActionsService $actions, Options $options, ClientSide $clientSide, \Kainex\WiseChat\DAO\Channels\OpenUserChannelsDAO $openUserChannelsDAO, \Kainex\WiseChat\DAO\ChannelUsersDAO $channelUsersDAO, PrivateMessagesRulesService $privateMessagesRulesService) {
		$this->userFeedService = $userFeedService;
		$this->userService = $userService;
		$this->membersDAO = $membersDAO;
		$this->userChannelsDAO = $userChannelsDAO;
		$this->usersDAO = $usersDAO;
		$this->authorization = $authorization;
		$this->authentication = $authentication;
		$this->messagesDAO = $messagesDAO;
		$this->messagesService = $messagesService;
		$this->actions = $actions;
		$this->options = $options;
		$this->clientSide = $clientSide;
		$this->openUserChannelsDAO = $openUserChannelsDAO;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->privateMessagesRulesService = $privateMessagesRulesService;
	}

	/**
	 * @return Channel[]
	 */
	public function getConstantChannels() {
		return $this->getByNames((array) $this->options->getOption('channel'));
	}

	/**
	 * @param Channel $channelToCheck
	 * @return bool
	 */
	public function isConstantChannel($channelToCheck) {
		$channels = $this->getConstantChannels();
		foreach ($channels as $channel) {
			if ($channel->getId() === $channelToCheck->getId()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Public channels: public and private groups
	 *
	 * @return bool
	 */
	public function arePublicChannelsEnabled(): bool {
		if ($this->options->getIntegerOption('mode') === 0 && $this->options->isOptionEnabled('classic_disable_channel')) {
			return false;
		}

		if ($this->options->getIntegerOption('mode') === 1 && $this->options->isOptionEnabled('fb_disable_channel')) {
			return false;
		}

		return true;
	}

	/**
	 * Determines whether the current user is authorized to access the channel.
	 *
	 * @param Channel $channel
	 *
	 * @return boolean
	 * @throws Exception
	 */
    public function isUserAuthorizedForChannel($channel) {
    	if (!$this->isUserAuthorizedInChannel($channel)) {
            return false;
	    }

        return $this->isChannelMember($channel);
    }

	/**
	 * Check if a password-protected channel is available to the current user.
	 *
	 * @param Channel $channel
	 * @return bool
	 * @throws Exception
	 */
	public function isUserAuthorizedInChannel(Channel $channel): bool {
		if ($channel->getPassword()) {
    		$grants = $this->userService->getProperty(AuthorizationService::PROPERTY_NAME);
    		$passwordAuthorized = is_array($grants) && array_key_exists($channel->getId(), $grants) && $grants[$channel->getId()] === $channel->getPassword();

    		if (!$passwordAuthorized) {
    			return false;
		    }
	    }

		return true;
	}

	public function canCreateChannels() {
		return false;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function isProtectedChannel($channel) {
		return $channel !== null && $channel->getPassword();
	}

	/**
	 * @param int $userId1
	 * @param int $userId2
	 * @param bool $createChannel Creates direct channel if it does not exist
	 * @return Channel|null
	 * @throws Exception
	 */
	public function getDirectChannel(int $userId1, int $userId2, bool $createChannel = true): ?Channel {
		if ($userId1 === $userId2) {
			throw new \Exception('Cannot create direct channel for the same user');
		}

		if ($userId1 < $userId2) {
			$channelName = $userId1.'_'.$userId2;
		} else {
			$channelName = $userId2.'_'.$userId1;
		}

		$channel = $this->getByNameAndType($channelName, Channel::TYPE_DIRECT);
		if (!$channel) {
			if ($createChannel) {
				// create channel:
				$channel = new Channel();
				$channel->setName($channelName);
				$channel->setType(Channel::TYPE_DIRECT);
				$this->save($channel);

				$member = new ChannelMember();
				$member->setType(ChannelMember::TYPE_OWNER);
				$member->setChannelId($channel->getId());
				$member->setUserId($userId1);
				$member->setConfirmed(true);
				$this->membersDAO->save($member);

				$member = new ChannelMember();
				$member->setType(ChannelMember::TYPE_OWNER);
				$member->setChannelId($channel->getId());
				$member->setUserId($userId2);
				$member->setConfirmed(true);
				$this->membersDAO->save($member);
			} else {
				return null;
			}
		}

		return $channel;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function canEdit(Channel $channel): bool {
		return $this->canCreateChannels() && $this->isChannelMemberType($channel, ChannelMember::TYPE_OWNER);
	}

	/**
	 * Returns IDs of channels allowed to edit by the current user.
	 *
	 * @param Channel[] $channels
	 * @return int[]
	 */
	public function canEditChannels(array $channels): array {
		if (!$this->canCreateChannels() || empty($channels)) {
			return [];
		}
		$channelIDs = array_map(function (Channel $channel) { return $channel->getId(); }, $channels);

		$result = [];
		$members = $this->membersDAO->getByChannelIdsAndUserId($channelIDs, $this->authentication->getUserIdOrNull(), true);
		foreach ($members as $member) {
			if ($member->getType() === ChannelMember::TYPE_OWNER) {
				$result[] = $member->getChannelId();
			}
		}

		return $result;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function canRemove($channel) {
		$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelId($this->authentication->getUser()->getId(), $channel->getId());

		return count($userChannels) > 0;
	}

	/**
	 * Returns IDs of channels allowed to remove from a personal list by the current user.
	 *
	 * @param Channel[] $channels
	 * @return int[]
	 */
	public function canRemoveChannels(array $channels): array {
		if (empty($channels)) {
			return [];
		}
		$channelIDs = array_map(function (Channel $channel) { return $channel->getId(); }, $channels);

		$result = [];
		$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelIds($this->authentication->getUserIdOrNull(), $channelIDs);
		foreach ($userChannels as $userChannel) {
			$result[] = $userChannel->getChannelId();
		}

		return $result;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function isDirect(Channel $channel) {
		return $channel->getType() === Channel::TYPE_DIRECT;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function isChannelMember(Channel $channel): bool {
		if ($channel->getType() === null) {
			return true;
		}

		if ($channel->getType() === Channel::TYPE_PUBLIC) {
			return true;
		}

		if ($channel->getType() === Channel::TYPE_PRIVATE || $channel->getType() === Channel::TYPE_DIRECT) {
			return $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $this->authentication->getUserIdOrNull(), true) !== null;
		}

		return false;
	}

	/**
	 * @param Channel $channel
	 * @param integer $type
	 * @return bool
	 */
	public function isChannelMemberType(Channel $channel, int $type): bool {
		if ($channel->getType() === null) {
			return false;
		}

		$member = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $this->authentication->getUserIdOrNull(), true);

		return $member !== null && $member->getType() === $type;
	}

	/**
	 * @param $parameters
	 * @return Channel
	 * @throws Exception
	 */
	public function createChannel($parameters) {
		if (!$this->canCreateChannels()) {
			throw new \Exception(__('No permission to create channels', 'wise-chat'));
		}

		$name = $parameters['name'];
		$type = $parameters['type'];

		if (!$name) {
			throw new \Exception('No name provided');
		}
		if (!in_array($type, ['public', 'private'])) {
			throw new \Exception('Invalid type');
		}

		if ($this->getByName($name)) {
			throw new \Exception(__('This name is already occupied', 'wise-chat'));
		}

		// create channel:
		$channel = new Channel();
		$channel->setName($name);
		$channel->setType($type === 'private' ? Channel::TYPE_PRIVATE : Channel::TYPE_PUBLIC);
		$this->save($channel);

		// set current user as an owner:
		$member = new ChannelMember();
		$member->setType(ChannelMember::TYPE_OWNER);
		$member->setChannelId($channel->getId());
		$member->setUserId($this->authentication->getUserIdOrNull());
		$member->setConfirmed(true);
		$this->membersDAO->save($member);

		// add channel to own list:
		$userChannel= new UserChannel();
		$userChannel->setSort($this->userChannelsDAO->getMaxSortUserId($this->authentication->getUserIdOrNull()) + 10);
		$userChannel->setUserId($this->authentication->getUserIdOrNull());
		$userChannel->setChannelId($channel->getId());
		$this->userChannelsDAO->save($userChannel);

		return $channel;
	}

	/**
	 * @param Channel $channel
	 * @param array $parameters
	 * @return Channel
	 * @throws Exception
	 */
	public function saveChannel(Channel $channel, array $parameters): Channel {
		$name = $parameters['name'];
		$type = $parameters['type'];
		$configuration = json_decode($parameters['configuration'], true);

		if (!$name) {
			throw new \Exception('No name provided');
		}
		if (!in_array($type, ['public', 'private'])) {
			throw new \Exception('Invalid type');
		}

		if ($name !== $channel->getName()) {
			if ($this->getByName($name)) {
				throw new \Exception(__('This name is already occupied', 'wise-chat'));
			}
		}

		$channel->setName($name);
		$channel->setType($type === 'private' ? Channel::TYPE_PRIVATE : Channel::TYPE_PUBLIC);
		$channel->setConfiguration($configuration);
		$this->save($channel);

		return $channel;
	}

	/**
	 * @param Channel $channel
	 * @param bool|null $confirmed
	 * @return ChannelMember[]
	 */
	public function getChannelMembers(Channel $channel, ?bool $confirmed = null): array {
		$members = $this->membersDAO->getAllByChannelIds([$channel->getId()]);

		if ($confirmed !== null) {
			return array_filter($members, function (ChannelMember $member) use ($confirmed) { return $confirmed ? $member->isConfirmed() : !$member->isConfirmed(); });
		}

		return $members;
	}

	/**
	 * Run by the channel owner or the user itself.
	 *
	 * @param Channel $channel
	 * @param integer $userId
	 * @param bool $postFeedEntry
	 * @throws Exception
	 */
	public function deleteChannelMember(Channel $channel, int $userId, bool $postFeedEntry = true) {
		$userMember = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $userId);
		if (!$userMember) {
			throw new \Exception('The user is not a member of the channel');
		}

		$this->membersDAO->deleteById($userMember->getId());

		$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelId($userId, $channel->getId());
		foreach ($userChannels as $userChannel) {
			$this->userChannelsDAO->deleteById($userChannel->getId());
		}

		// do not notify unconfirmed users:
		if (!$userMember->isConfirmed()) {
			$postFeedEntry = false;
		}

		if ($postFeedEntry) {
			$this->userFeedService->create($userId, 'channels.member.deleted', $channel->getId());
		}

		$this->actions->publishAction('deleteChannel', array('channelId' => $this->clientSide->encryptPublicChannelId($channel->getId())), $userId);
	}

	/**
	 * Deletes the channel, members and messages.
	 *
	 * @param Channel $channel
	 * @throws Exception
	 */
	public function deleteChannel(Channel $channel) {
		if (!current_user_can(Settings::CAPABILITY) && !$this->canEdit($channel)) {
			throw new \Exception('Permission denied');
		}

		$this->membersDAO->deleteByChannelId($channel->getId());
		$this->userChannelsDAO->deleteByChannelId($channel->getId());
		$this->openUserChannelsDAO->deleteByChannelId($channel->getId());
		$this->messagesService->deleteByChannel($channel->getId());
		$this->actions->publishAction('deleteChannel', array('channelId' => $this->clientSide->encryptPublicChannelId($channel->getId())));
		$this->deleteById($channel->getId());
	}

	/**
	 * @param Channel $channel
	 * @param integer $wordPressUserId
	 * @param boolean $confirmed
	 * @throws Exception
	 */
	public function addChannelMemberOfWPUser($channel, $wordPressUserId, $confirmed = true) {
		if (!$this->canEdit($channel)) {
			throw new \Exception('No permission to save the channel');
		}

		$user = $this->userService->createOrGetBasedOnWordPressUserId($wordPressUserId);
		if (!$user) {
			throw new \Exception('WordPress user does not exist');
		}

		$this->addChannelMember($channel, $user->getId(), $confirmed);
	}

	/**
	 * @param Channel $channel
	 * @param integer $userId
	 * @param boolean $confirmed Whether the member should be confirmed
	 * @throws Exception
	 */
	public function addChannelMember($channel, $userId, $confirmed = true) {
		if (!$this->canEdit($channel)) {
			throw new \Exception('No permission to save the channel');
		}

		if ($channel->getType() !== Channel::TYPE_PRIVATE) {
			throw new \Exception(__('You can only add member to private channels', 'wise-chat'));
		}

		$user = $this->usersDAO->get($userId);
		if (!$user) {
			throw new \Exception('WordPress user does not exist');
		}

		$currentUserMember = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $user->getId());
		if ($currentUserMember !== null) {
			throw new \Exception(__('The user is already a member of this channel', 'wise-chat'));
		}

		// set current user as an owner:
		$member = new ChannelMember();
		$member->setType(ChannelMember::TYPE_MEMBER);
		$member->setChannelId($channel->getId());
		$member->setUserId($user->getId());
		$member->setConfirmed($confirmed);
		$this->membersDAO->save($member);

		if ($confirmed) {
			// add channel to own list:
			$userChannel = new UserChannel();
			$userChannel->setSort($this->userChannelsDAO->getMaxSortUserId($user->getId()) + 10);
			$userChannel->setUserId($user->getId());
			$userChannel->setChannelId($channel->getId());
			$this->userChannelsDAO->save($userChannel);

			$this->userFeedService->create($user->getId(), 'channels.member.added', $channel->getId());
		} else {
			$this->userFeedService->create($user->getId(), 'channels.member.invitation', $channel->getId());
		}
	}

	/**
	 * @return ChannelMember[]
	 */
	public function getChannelsUserBelongsTo(): array {
		return $this->membersDAO->getAllByUserId($this->authentication->getUser()->getId());
	}

	public function requestChannelMembership(Channel $channel) {
		if (!$this->arePublicChannelsEnabled()) {
			throw new \Exception('Access denied');
		}
		if ($channel->getType() !== Channel::TYPE_PRIVATE) {
			throw new \Exception('Join is only possible in private channels');
		}

		$ownerMember = $this->membersDAO->getByChannelIdAndTypeId($channel->getId(), ChannelMember::TYPE_OWNER);
		if (!$ownerMember) {
			throw new \Exception(__('No channel owner available', 'wise-chat'));
		}

		$this->userFeedService->create($ownerMember->getUserId(), 'channels.member.join.request', $channel->getId(), ['userId' => $this->authentication->getUser()->getId()]);
	}

	/**
	 * Removes own user-channel connection.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function removeUserChannel(Channel $channel) {
		$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelId($this->authentication->getUser()->getId(), $channel->getId());
		if (!$userChannels) {
			throw new \Exception('The channel is not on your list');
		}
		foreach ($userChannels as $userChannel) {
			$this->userChannelsDAO->deleteById($userChannel->getId());
		}
	}

	/**
	 * Removes the current user from the channel.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function quitChannelMembership(Channel $channel) {
		if ($this->canEdit($channel)) {
			throw new \Exception(__('Channel administrator cannot remove itself from the channel', 'wise-chat'));
		}
		$this->deleteChannelMember($channel, $this->authentication->getUser()->getId(), false);
	}

	/**
	 * Adds a channel to the current user's list.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function addUserChannel(Channel $channel) {
		$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelId($this->authentication->getUser()->getId(), $channel->getId());
		if (!$userChannels) {
			if (!$this->isChannelMember($channel)) {
				throw new \Exception('No permission to add user channel');
			}

			if ($this->isConstantChannel($channel)) {
				throw new \Exception(__('Could not add a constant channel to the list', 'wise-chat'));
			}

			$userChannel= new UserChannel();
			$userChannel->setSort($this->userChannelsDAO->getMaxSortUserId($this->authentication->getUser()->getId()) + 10);
			$userChannel->setUserId($this->authentication->getUser()->getId());
			$userChannel->setChannelId($channel->getId());
			$this->userChannelsDAO->save($userChannel);
		}
	}

	/**
	 * Confirms user membership and adds the channel to the user's list.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function confirmChannelMembership(Channel $channel) {
		$member = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $this->authentication->getUser()->getId(), false);
		if ($member) {
			$member->setConfirmed(true);
			$this->membersDAO->save($member);

			$this->addUserChannel($channel);
		} else {
			throw new \Exception(__('No pending invitations', 'wise-chat'));
		}
	}

	public function sendChannelNotifications(Channel $channel, array $wpUserIDs) {
		if (!$this->canEdit($channel)) {
			throw new \Exception('Permission denied');
		}

		if ($channel->getType() !== Channel::TYPE_PUBLIC) {
			throw new \Exception('Public channels only');
		}

		foreach ($wpUserIDs as $wpUserIDEncrypted) {
			$wpUserID = Crypt::decryptFromString($wpUserIDEncrypted);
			$user = $this->usersDAO->getLatestByWordPressId($wpUserID);
			if ($user) {
				$this->userFeedService->create($user->getId(), 'channels.notification', $channel->getId());
			}
		}
	}

	/**
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function ensureReadAccess(Channel $channel) {
		if (in_array($channel->getType(), [null, Channel::TYPE_PUBLIC, Channel::TYPE_PRIVATE])) {
			if (!$this->arePublicChannelsEnabled() || !$this->isUserAuthorizedInChannel($channel) || !$this->isChannelMember($channel)) {
				throw new \Exception('No permissions to read public channel');
			}
		} else if ($channel->getType() === Channel::TYPE_DIRECT) {
			if (!$this->isChannelMember($channel) || !$this->options->isOptionEnabled('enable_private_messages')) {
				throw new \Exception('No permissions to read direct channel');
			}
		} else {
			throw new \Exception('Unsupported channel type');
		}
	}

	/**
	 * Check if the channel may be opened.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function ensureOpeningAccess(Channel $channel) {
		if (in_array($channel->getType(), [null, Channel::TYPE_PUBLIC, Channel::TYPE_PRIVATE])) {
			if (!$this->arePublicChannelsEnabled() || !$this->isChannelMember($channel)) {
				throw new \Exception('No permissions to read public channel');
			}
		} else if ($channel->getType() === Channel::TYPE_DIRECT) {
			if (!$this->isChannelMember($channel) || !$this->options->isOptionEnabled('enable_private_messages')) {
				throw new \Exception('No permissions to read direct channel');
			}
		} else {
			throw new \Exception('Unsupported channel type');
		}
	}

	/**
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function ensureWriteAccess(Channel $channel) {
		if (in_array($channel->getType(), [null, Channel::TYPE_PUBLIC, Channel::TYPE_PRIVATE])) {
			if (!$this->arePublicChannelsEnabled() || !$this->isUserAuthorizedInChannel($channel) || !$this->isChannelMember($channel)) {
				throw new \Exception('No permissions to write to a public channel');
			}
		} else if ($channel->getType() === Channel::TYPE_DIRECT) {
			if (!$this->options->isOptionEnabled('enable_private_messages')) {
				throw new \Exception('Permission denied: direct channels are not enabled');
			}

			$currentUserMember = null;
			$otherUserMember = null;
			$members = $this->membersDAO->getAllByChannelIds([$channel->getId()]);
			foreach ($members as $member) {
				if (!$member->isConfirmed()) {
					continue;
				}
				if ($this->authentication->getUser()->getId() == $member->getUserId()) {
					$currentUserMember = $member;
				} else {
					$otherUserMember = $member;
				}
			}
			if (!$currentUserMember) {
				throw new \Exception('Permission denied: current user is not a member');
			}
			if (!$this->privateMessagesRulesService->isMessageDeliveryAllowed($this->authentication->getUser(), $otherUserMember->getUser())) {
				throw new \Exception('Permission denied: permission rules');
			}
		} else {
			throw new \Exception('Unsupported channel type');
		}
	}

	/**
	 * If the channel is accessible to authentication.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function ensureAuthAccess(Channel $channel) {
		if (in_array($channel->getType(), [null, Channel::TYPE_PUBLIC, Channel::TYPE_PRIVATE])) {
			if (!$this->arePublicChannelsEnabled() || !$this->isChannelMember($channel)) {
				throw new \Exception('No permissions to read public channel');
			}
		} else if ($channel->getType() === Channel::TYPE_DIRECT) {
			if (!$this->isChannelMember($channel) || !$this->options->isOptionEnabled('enable_private_messages')) {
				throw new \Exception('No permissions to read direct channel');
			}
		} else {
			throw new \Exception('Unsupported channel type');
		}
	}

	/**
	 * If the channel is available to edit (change name, members, etc.) or delete.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function ensureEditAccess(Channel $channel) {
		if (in_array($channel->getType(), [Channel::TYPE_PUBLIC, Channel::TYPE_PRIVATE])) {
			if (!$this->arePublicChannelsEnabled() || !$this->canEdit($channel)) {
				throw new \Exception(__('No permission to save channels', 'wise-chat'));
			}
		} else {
			throw new \Exception('Unsupported channel type');
		}
	}

	public function openChannel(Channel $channel): bool {
		if (!$this->openUserChannelsDAO->getAllByUserIdAndChannelId($this->authentication->getUser()->getId(), $channel->getId())) {
			if (!$this->isChannelMember($channel)) {
				throw new \Exception('No permission to open the channel');
			}

			$openUserChannel= new OpenUserChannel();
			$openUserChannel->setSort($this->openUserChannelsDAO->getMaxSortUserId($this->authentication->getUser()->getId()) + 10);
			$openUserChannel->setUserId($this->authentication->getUser()->getId());
			$openUserChannel->setChannelId($channel->getId());
			$this->openUserChannelsDAO->save($openUserChannel);

			return true;
		} else {
			return false;
		}
	}

	public function closeChannel(Channel $channel) {
		$this->openUserChannelsDAO->deleteByChannelIdAndUserId($channel->getId(), $this->authentication->getUser()->getId());
	}

	public function isChannelOpen(int $userId, int $channelId): bool {
		return !empty($this->openUserChannelsDAO->getAllByUserIdAndChannelId($userId, $channelId));
	}

	public function deleteUserRelatedData(): void {
		$this->membersDAO->deleteAll();
		$this->channelUsersDAO->deleteAll();
		$this->openUserChannelsDAO->deleteAll();
	}

	/**
	 * @param Channel[] $channels
	 * @return void
	 */
	public function getChannelsStats(array $channels): array {
		$channelIDs = array_map(function (Channel $channel) { return $channel->getId(); }, $channels);

		$quantity = $this->membersDAO->getCountByChannelsIDs($channelIDs);

		$result = [];
		foreach ($quantity as $membersQuantity) {
			$channelId = $membersQuantity['channelId'];
			$result[(int) $channelId] = [
				'membersCount' => (int) $membersQuantity['membersCount']
			];
		}

		$messagesQuantityByChannels = $this->messagesService->getCountByChannelsIDs($channelIDs);
		foreach ($messagesQuantityByChannels as $messagesQuantity) {
			$channelId = (int) $messagesQuantity['channelId'];
			if (!isset($result[$channelId])) {
				$result[$channelId] = [];
			}
			$result[$channelId]['messagesCount'] = (int) $messagesQuantity['messagesCount'];
		}

		return $result;
	}

}