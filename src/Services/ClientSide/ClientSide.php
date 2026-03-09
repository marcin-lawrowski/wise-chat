<?php

namespace Kainex\WiseChat\Services\ClientSide;

use Exception;
use Kainex\WiseChat\DAO\Channels\MembersDAO;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\ChannelsDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Channel\ChannelMember;
use Kainex\WiseChat\Model\Channel\ChannelUser;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Rendering\UITemplates;
use Kainex\WiseChat\Services\Channels\Listing\Model\DirectChannel;
use Kainex\WiseChat\Services\Message\MessageReactionsService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Services\UserMutesService;
use Kainex\WiseChat\Services\ChannelsService;
use Kainex\WiseChat\Services\MessagesService;
use Kainex\WiseChat\Services\PrivateMessagesRulesService;
use Kainex\WiseChat\Services\ChatService;
use Kainex\WiseChat\Crypt;
use Kainex\WiseChat\Options;
use WP_User;

/**
 * WiseChat client side utilities.
 *
 * @author Kainex <contact@kaine.pl>
 */
class ClientSide {

	/**
	 * @var Options
	 */
	protected $options;

	/**
	 * @var MessagesService
	 */
	private $messagesService;

	/**
	 * @var AuthenticationService
	 */
	private $authentication;

	/**
	 * @var UserService
	 */
	private $userService;

	/**
	 * @var PrivateMessagesRulesService
	 */
	private $privateMessagesRulesService;

	/**
	 * @var UITemplates
	 */
	protected $uiTemplates;

	/**
	 * @var ChannelsDAO
	 */
	protected $channelsDAO;

	/**
	 * @var ChannelsService
	 */
	protected $channelsService;

	/**
	 * @var ChatService
	 */
	protected $service;

	/**
	 * @var UsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var MessageReactionsService
	 */
	protected $messageReactionsService;

	private MembersDAO $membersDAO;
	private UserMutesService $userMutesService;

	/**
	 * @var array
	 */
	private $plainDirectChannelsCache = array();

	/**
	 * @param Options $options
	 * @param MessagesService $messagesService
	 * @param AuthenticationService $authentication
	 * @param UserService $userService
	 * @param PrivateMessagesRulesService $privateMessagesRulesService
	 * @param UITemplates $uiTemplates
	 * @param ChannelsDAO $channelsDAO
	 * @param ChannelsService $channelsService
	 * @param ChatService $service
	 * @param UsersDAO $usersDAO
	 * @param MessageReactionsService $messageReactionsService
	 * @param MembersDAO $membersDAO
	 * @param UserMutesService $userMutesService
	 */
	public function __construct(Options $options, MessagesService $messagesService, AuthenticationService $authentication, UserService $userService, PrivateMessagesRulesService $privateMessagesRulesService, UITemplates $uiTemplates, ChannelsDAO $channelsDAO, ChannelsService $channelsService, ChatService $service, UsersDAO $usersDAO, MessageReactionsService $messageReactionsService, \Kainex\WiseChat\DAO\Channels\MembersDAO $membersDAO, \Kainex\WiseChat\Services\UserMutesService $userMutesService) {
		$this->options = $options;
		$this->messagesService = $messagesService;
		$this->authentication = $authentication;
		$this->userService = $userService;
		$this->privateMessagesRulesService = $privateMessagesRulesService;
		$this->uiTemplates = $uiTemplates;
		$this->channelsDAO = $channelsDAO;
		$this->channelsService = $channelsService;
		$this->service = $service;
		$this->usersDAO = $usersDAO;
		$this->messageReactionsService = $messageReactionsService;
		$this->membersDAO = $membersDAO;
		$this->userMutesService = $userMutesService;
	}

	/**
	 * @param Message $message
	 * @param ClientChannel $clientChannel
	 * @param array $attributes
	 * @return array
	 */
	public function toPlainMessage(Message $message, ClientChannel $clientChannel, array $attributes = []): array {
		// TODO: null $clientChannel -> support in JSX

		// check if the message cannot be exposed to the user:
		if (!$this->userService->isUserAllowedToSeeTheContentOfMessage($message)) {
			return array(
				'id' => $this->encryptMessageId($message->getId()),
				'locked' => true,
				'channel' => array(
					'id' => $clientChannel->getId()
				)
			);
		}

		$replyToMessage = $this->options->isOptionEnabled('enable_reply_to_messages', true) && $message->getReplyToMessageId() > 0
			? $this->messagesService->getById($message->getReplyToMessageId(), true)
			: null;

		// if it is a reply to a pending message:
		if ($replyToMessage && !$this->userService->isUserAllowedToSeeTheContentOfMessage($replyToMessage)) {
			return array(
				'id' => $this->encryptMessageId($replyToMessage->getId()),
				'locked' => true,
				'channel' => array(
					'id' => $clientChannel
				)
			);
		}

		$textColorAffectedParts = (array)$this->options->getOption("text_color_parts", array('message', 'messageUserName'));
		$classes = '';
		$wpUser = $this->usersDAO->getWpUserByID($message->getWordPressUserId());
		if ($this->options->isOptionEnabled('css_classes_for_user_roles', false)) {
			$classes = $this->userService->getCssClassesForUserRoles($message->getUser(), $wpUser);
		}

		$messagePlain = array(
			'id' => $this->encryptMessageId($message->getId()),
			'own' => $message->getUserId() === $this->authentication->getUserIdOrNull(),
			'text' => $message->getText(),
			'channel' => $this->clientChannelToPlain($clientChannel),
			'color' => in_array('message', $textColorAffectedParts) ? $this->userService->getUserTextColor($message->getUser()) : null,
			'cssClasses' => $classes,
			'timeUTC' => gmdate('c', $message->getTime()),
			'sortKey' => $message->getTime().$message->getId(),
			'awaitingApproval' => $this->options->isOptionEnabled('new_messages_hidden', false) && $message->isHidden(),
			'locked' => false,
			'sender' => $message->isAdmin()
				? ['name' => 'Admin', 'avatarUrl' => $this->userService->getUserDefaultAvatar()] // TODO: what about admin?
				: (
					$message->getUser()
					? $this->getMessageSender($message, $message->getUser(), $wpUser)
					: ['name' => 'Unknown', 'avatarUrl' => $this->userService->getUserDefaultAvatar()] // TODO
				),

			'quoted' => $replyToMessage !== null
				? $this->toPlainMessage($replyToMessage, $clientChannel)
				: null
		);

		// append message reactions:
		if ($this->messageReactionsService->isEnabled()) {
			$messagePlain['reactions'] = $this->messageReactionsService->getReactionsAsPlainArray($message);
		}

		return array_merge($messagePlain, $attributes);
	}

	private function getMessageSender(Message $message, User $user, ?WP_User $wpUser) {
		$textColorAffectedParts = (array) $this->options->getOption("text_color_parts", array('message', 'messageUserName'));
		$isCurrent = $this->authentication->getUser()->getId() === $message->getUserId();

		$details = array(
			'id' => $this->encryptUserId($message->getUserId()),
			'name' => $user->getName(),
			'source' => $wpUser !== null ? 'w' : 'a',
			'current' => $isCurrent,
			'color' => in_array('messageUserName', $textColorAffectedParts) ? $this->userService->getUserTextColor($message->getUser()) : null,
			'profileUrl' => $this->options->getIntegerOption('link_wp_user_name', 0) === 1 ? $this->userService->getUserProfileLink($message->getUser(), $message->getUser() ? $message->getUser()->getName() : 'Unknown', $message->getWordPressUserId()) : null,
			'avatarUrl' => $this->options->isOptionEnabled('show_avatars', false) ? $this->userService->getUserAvatar($message->getUser()) : null
		);

		if (!$isCurrent && $message->getUser()) {
			$details['channel'] = [
				'id' => $this->encryptDirectChannelId($message->getUserId()),
				'own' => false,
				'locked' => false,
				'type' => 'direct',
				'name' => $message->getUser()->getName(),
				'configuration' => []
			];
		}

		return $details;
	}

	/**
	 * @param $user
	 * @return string
	 */
	public function getUserCacheId($user) {
		return $this->getInstanceId().'_'.Crypt::encryptToString($user->getId());
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
		return Crypt::encryptToString($id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptDirectChannelId($id) {
		return Crypt::encryptToString('d|'.$id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptPublicChannelId($id) {
		return Crypt::encryptToString('c|'.$id);
	}

	/**
	 * @param Channel $channel
	 * @return string
	 * @throws Exception
	 */
	public function encryptChannelId(Channel $channel): string {
		if (in_array($channel->getType(), [null, Channel::TYPE_PUBLIC, Channel::TYPE_PRIVATE])) {
			return Crypt::encryptToString('c|'.$channel->getId());
		} else if ($channel->getType() === Channel::TYPE_DIRECT) {
			// get the opposite member from channel name (for performance):
			// TODO: switch to stronger method

			$split = explode('_', $channel->getName());
			$memberUserId = intval($split[0]) === $this->authentication->getUserIdOrNull() ? $split[1] : $split[0];

			return $this->encryptDirectChannelId($memberUserId);
		} else {
			throw new \Exception('Unknown channel type');
		}
	}

	/**
	 * @param Channel[] $channels
	 * @return ClientChannel[] Array with channel IDs as keys
	 * @throws Exception
	 */
	public function convertToClientChannels(array $channels): array {
		if (empty($channels)) {
			return [];
		}

		$output = [];
		$readOnly = !$this->userService->isSendingMessagesAllowed() && !$this->authentication->isAuthenticatedExternally();
		$allowedToEdit = $this->channelsService->canEditChannels($channels);
		$allowedToRemove = $this->channelsService->canRemoveChannels($channels);
		$directChannelsIDs = [];

		foreach ($channels as $channel) {
			// omit direct channels because they have more details and needs to be loaded differently:
			if ($channel->getType() === Channel::TYPE_DIRECT) {
				$directChannelsIDs[] = $channel->getId();
				continue;
			}

			$clientChannel = new ClientChannel();
			$clientChannel->setId($this->encryptChannelId($channel));
			$clientChannel->setName($channel->getName());
			$clientChannel->setType($channel->getType());
			$clientChannel->setConfiguration($channel->getConfiguration());
			$clientChannel->setReadOnly($readOnly);
			$clientChannel->setAvatar($this->options->getIconsURL() . 'public-channel.png');
			$clientChannel->setProtected($this->channelsService->isProtectedChannel($channel));
			$clientChannel->setAuthorized($this->channelsService->isUserAuthorizedInChannel($channel));
			$clientChannel->setCanEdit(in_array($channel->getId(), $allowedToEdit));
			$clientChannel->setCanRemove(in_array($channel->getId(), $allowedToRemove));
			$clientChannel->setOnline(true);
			$output[$channel->getId()] = $clientChannel;
		}

		if (!empty($directChannelsIDs)) {
			$members = $this->membersDAO->getAllByChannelIds($directChannelsIDs);
			if (!empty($members)) {
				$users = [];
				$userIdToChannelIdMap = [];
				foreach ($members as $member) {
					if ($member->getUserId() !== $this->authentication->getUserIdOrNull()) {
						$users[] = $member->getUser();
						$userIdToChannelIdMap[$member->getUserId()] = $member->getChannelId();
					}
				}

				if (!empty($users)) {
					$directChannels = $this->convertUsersToClientChannels($users, $userIdToChannelIdMap);
					foreach ($directChannels as $directChannelId => $directChannel) {
						$output[$directChannelId] = $directChannel;
					}
				}
			}
		}

		return $output;
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptMessageId($id) {
		return Crypt::encryptToString($id);
	}

	/**
	 * @param integer[] $ids
	 * @return string[]
	 */
	public function encryptMessageIds($ids) {
		return array_map(function($id) {
			return Crypt::encryptToString($id);
		}, $ids);
	}

	/**
	 * @param string $encryptedId
	 * @return integer
	 */
	public function decryptMessageId($encryptedId) {
		return intval(Crypt::decryptFromString($encryptedId));
	}

	/**
	 * Decrypts the message ID and loads the message.
	 *
	 * @param string $encryptedMessageId
	 * @return Message
	 * @throws Exception If the message does not exist
	 */
	public function getMessageOrThrowException($encryptedMessageId) {
		$message = $this->messagesService->getById($this->decryptMessageId($encryptedMessageId), true);
		if ($message === null) {
			throw new \Exception('The message does not exist');
		}

		return $message;
	}

	/**
	 * Translates a channel public ID into a channel object. No read/write access rights are checked.
	 *
	 * @param string $encryptedChannelId
	 * @param bool $createDirectChannels Creates direct channel if it does not exist
	 * @return Channel
	 * @throws Exception
	 */
	public function getChannelFromEncryptedId(string $encryptedChannelId, bool $createDirectChannels = true): Channel {
		$channelTypeAndId = Crypt::decryptFromString($encryptedChannelId);
		if ($channelTypeAndId === null) {
			throw new Exception('Invalid channel');
		}

		if (strpos($channelTypeAndId, 'c|') !== false || is_numeric($channelTypeAndId)) {
			$channel = $this->channelsDAO->get(intval(str_replace('c|', '', $channelTypeAndId)));
			if (!$channel) {
				throw new Exception('Unknown channel ID');
			}
		} else if (strpos($channelTypeAndId, 'd|') !== false) {
			$userId = str_replace('d|', '', $channelTypeAndId);
			if (strpos($userId, 'v') === 0) {
				$recipient = $this->userService->createOrGetBasedOnWordPressUserId(str_replace('v', '', $userId));
				$userId = $recipient->getId();
			} else {
				$userId = intval($userId);
			}

			$channel = $this->channelsService->getDirectChannel($this->authentication->getUserIdOrNull(), $userId, $createDirectChannels);
			if (!$channel) {
				throw new Exception('Unknown direct channel');
			}
		} else {
			throw new Exception('Unknown channel');
		}

		return $channel;
	}

	/**
	 * Converts public channel ID into Channel object.
	 *
	 * @param string $encryptedChannelId
	 * @return Channel
	 * @throws Exception
	 */
	public function getChannelOfEncryptedID(string $encryptedChannelId): Channel {
		$channelTypeAndId = Crypt::decryptFromString($encryptedChannelId);
		if ($channelTypeAndId === null) {
			throw new Exception('Invalid channel');
		}

		if (strpos($channelTypeAndId, 'c|') !== false || is_numeric($channelTypeAndId)) {
			$channel = $this->channelsDAO->get(intval(str_replace('c|', '', $channelTypeAndId)));
			if (!$channel) {
				throw new Exception('Unknown channel ID');
			}
		} else if (strpos($channelTypeAndId, 'd|') !== false) {
			$userId = str_replace('d|', '', $channelTypeAndId);
			$userId = intval($userId);

			$channel = $this->channelsService->getDirectChannel($this->authentication->getUserIdOrNull(), $userId, false);
			if (!$channel) {
				throw new Exception('Unknown direct channel');
			}
		} else {
			throw new Exception('Unknown channel');
		}

		return $channel;
	}

	/**
	 * @param ChannelMember[] $members
	 * @return array[]
	 */
	public function channelMembersToPlain($members) {
		$plain = [];

		foreach ($members as $member) {
			$plain[] = [
				'id' => Crypt::encryptToString($member->getUserId()),
				'name' => $member->getUser()->getName(),
				'avatarUrl' => $this->userService->getUserAvatar($member->getUser()),
				'type' => $member->getType() === ChannelMember::TYPE_OWNER ? 'Administrator' : 'Member',
				'confirmed' => $member->isConfirmed()
			];
		}

		return $plain;
	}

	/**
	 * Outputs public channel as plain array.
	 *
	 * @param Channel $channel
	 * @return array
	 * @throws Exception
	 */
	public function channelToPlain(Channel $channel): array {
		$clientChannels = $this->convertToClientChannels([$channel]);

		return $this->clientChannelToPlain(reset($clientChannels));
	}

	/**
	 * @param WP_User[] $users
	 * @return array
	 */
	public function wpUsersToPlain($users) {
		$plain = [];

		foreach ($users as $user) {
			$avatarUrl = get_avatar_url($user, array("size" => 96));
			if ($avatarUrl === false) {
				$avatarUrl = $this->options->getIconsURL().'user.png';
			}
			$plain[] = [
				'id' => Crypt::encryptToString($user->ID),
				'name' => $user->display_name,
				'avatarUrl' => $avatarUrl
			];
		}

		return $plain;
	}

	/**
	 * @param int|null $channelType
	 * @return string|null
	 */
	private function getChannelTypeAsText(?int $channelType): ?string {
		if (!$channelType || $channelType === Channel::TYPE_PUBLIC) {
			return 'public';
		}
		if ($channelType === Channel::TYPE_PRIVATE) {
			return 'private';
		}
		if ($channelType === Channel::TYPE_DIRECT) {
			return 'direct';
		}

		return null;
	}

	/**
	 * @param User[] $users
	 * @return ClientChannel[]
	 */
	public function convertUsersToClientChannels(array $users, ?array $userIdToChannelIdMap = null): array {
		if (empty($users)) {
			return [];
		}
		$output = [];

		$wpIDs = [];
		$usersIds = [];
		foreach ($users as $user) {
			$usersIds[] = $user->getId();
			if ($user->getWordPressId()) {
				$wpIDs[] = $user->getWordPressId();
			}
		}
		if (!empty($wpIDs)) {
			$this->userService->cacheWPUsers(['include' => $wpIDs]);
		}
		$mutedUsersIDs = $this->userMutesService->getMutedUsersIDs($users);
		$onlineUsers = $this->userService->getOnlineUsers(['limitToUserIDs' => $usersIds]);
		$onlineUsersIds = [];
		foreach ($onlineUsers as $onlineUser) {
			$onlineUsersIds[] = $onlineUser->getId();
		}
		$allowedUsersIDs = $this->privateMessagesRulesService->isMessageDeliveryAllowedToUsers($this->authentication->getUser(), $users);
		$urlEnabled = $this->options->isOptionEnabled('users_list_linking');
		$roleClassesEnabled = $this->options->isOptionEnabled('css_classes_for_user_roles');

		foreach ($users as $user) {
			$wpUser = $user->getWordPressId() > 0 ? $this->userService->getWpUserByID($user->getWordPressId()) : null;

			$clientChannel = new ClientChannel();
			$clientChannel->setId($this->encryptDirectChannelId($user->getId()));
			$clientChannel->setName($user->getName());
			$clientChannel->setType(Channel::TYPE_DIRECT);
			$clientChannel->setProtected(false);
			$clientChannel->setAuthorized(true);
			$clientChannel->setOwn($user->getId() === $this->authentication->getUserIdOrNull());

			// text color defined by role:
			$textColor = $this->userService->getTextColorDefinedByUserRole($user);

			// custom text color:
			if ($this->options->isOptionEnabled('allow_change_text_color')) {
				$textColorProposal = $user->getDataProperty('textColor');
				if ($textColorProposal) {
					$textColor = $textColorProposal;
				}
			}

			$roleClasses = $roleClassesEnabled ? $this->userService->getCssClassesForUserRoles($user) : null;

			$countryFlagSrc = null;
			$countryCode = null;
			$country = null;
			$city = null;

			if ($this->options->isOptionEnabled('show_users_flags')) {
				$countryCode = $user->getDataProperty('countryCode');
				$country = $user->getDataProperty('country');
				if ($countryCode) {
					$countryFlagSrc = $this->options->getFlagURL(strtolower($countryCode));
				}
			}
			if ($this->options->isOptionEnabled('show_users_city_and_country')) {
				$city = $user->getDataProperty('city');
				$countryCode = $user->getDataProperty('countryCode');
			}

			$clientChannel->setAvatar($this->userService->getUserAvatar($user));
			$clientChannel->setReadOnly(!in_array($user->getId(), $allowedUsersIDs));
			$clientChannel->setCanEdit(false);
			$clientChannel->setCanRemove(false);
			$clientChannel->setUrl(
				$urlEnabled ? $this->userService->getUserProfileLink($user, $user->getName(), $user->getWordPressId()) : null
			);
			$clientChannel->setTextColor($textColor);
			$clientChannel->setClasses($roleClasses);

			$clientChannel->setCountryCode($countryCode);
			$clientChannel->setCountry($country);
			$clientChannel->setCity($city);
			$clientChannel->setCountryFlagSrc($countryFlagSrc);
			$clientChannel->setOnline(in_array($user->getId(), $onlineUsersIds));

			if ($wpUser && $wpUser->get('wc_ai_bot') === '1') {
				$clientChannel->setOnline(true);
			}

			$clientChannel->setMuted(in_array($user->getId(), $mutedUsersIDs));

			$clientChannel->setIntro(null);
			$clientChannel->setInfoWindow(null);

			if ($userIdToChannelIdMap) {
				if (isset($userIdToChannelIdMap[$user->getId()])) {
					$output[$userIdToChannelIdMap[$user->getId()]] = $clientChannel;
				}
			} else {
				$output[] = $clientChannel;
			}
		}

		return $output;
	}


	public function clientChannelToPlain(ClientChannel $clientChannel): array {
		static $isChatFull = null;

		// TODO: "full" channel convert to "full" chat (including direct channels)
		if ($isChatFull === null) {
			$isChatFull = $this->service->isChatFull();
		}

		return array(
			'id' => $clientChannel->getId(),
			'name' => $clientChannel->getName(),
			'readOnly' => $clientChannel->isReadOnly(),
			'own' => $clientChannel->isOwn(),
			'configuration' => array_merge(ChannelsDAO::DEFAULT_CONFIGURATION, $clientChannel->getConfiguration() ?: []),
			'type' => $this->getChannelTypeAsText($clientChannel->getType()),
			'avatar' => $clientChannel->getAvatar(),
			'protected' => $clientChannel->isProtected(),
			'authorized' => $clientChannel->isAuthorized(),
			'canEdit' => $clientChannel->isCanEdit(),
			'canRemove' => $clientChannel->isCanRemove(),
			'full' => $isChatFull,
			'url' => $clientChannel->getUrl(),
			'textColor' => $clientChannel->getTextColor(),
			'locked' => false, // TODO
			'classes' => $clientChannel->getClasses(),
			'countryCode' => $clientChannel->getCountryCode(),
			'country' => $clientChannel->getCountry(),
			'city' => $clientChannel->getCity(),
			'countryFlagSrc' => $clientChannel->getCountryFlagSrc(),
			'infoWindow' => $clientChannel->getInfoWindow(),
			'intro' => $clientChannel->getIntro(),
			'online' => $clientChannel->isOnline(),
			'muted' => $clientChannel->isMuted()
		);
	}

}