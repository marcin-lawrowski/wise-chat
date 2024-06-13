<?php

/**
 * Class for loading channels.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMaintenanceChannels {

	/** @var WiseChatChannelsSourcesService */
	private $channelsSourcesService;

	/**
	 * @var WiseChatClientSide
	 */
	protected $clientSide;

	/**
	 * @var WiseChatChannelUsersDAO
	 */
	protected $channelUsersDAO;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	 * @var WiseChatAuthorization
	 */
	protected $authorization;

	/**
	 * @var WiseChatUserService
	 */
	protected $userService;

	/**
	 * @var WiseChatService
	 */
	protected $service;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatMessagesService
	 */
	protected $messagesService;

	/**
	 * @var WiseChatChannelsService
	 */
	protected $channelsService;

	/**
	 * @var WiseChatRenderer
	 */
	protected $renderer;

	/**
	 * @var WiseChatUITemplates
	 */
	protected $uiTemplates;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/** @var array All required channels (both public and direct) */
	private $channelsStorage;

	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->authorization = WiseChatContainer::getLazy('services/user/WiseChatAuthorization');
		$this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
		$this->userService = WiseChatContainer::getLazy('services/user/WiseChatUserService');
		$this->service = WiseChatContainer::getLazy('services/WiseChatService');
		$this->usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');
		$this->renderer = WiseChatContainer::getLazy('rendering/WiseChatRenderer');
		$this->clientSide = WiseChatContainer::getLazy('services/client-side/WiseChatClientSide');
		$this->channelsSourcesService = WiseChatContainer::getLazy('services/channels/listing/WiseChatChannelsSourcesService');
		$this->messagesService = WiseChatContainer::getLazy('services/WiseChatMessagesService');
		$this->channelsService = WiseChatContainer::getLazy('services/WiseChatChannelsService');
		$this->uiTemplates = WiseChatContainer::getLazy('rendering/WiseChatUITemplates');
		$this->channelsStorage = array();
	}

	/**
	 * @return WiseChatChannel[]
	 * @throws Exception
	 */
	public function getPublicChannels() {
		$result = array();
		$channels = $this->channelsSourcesService->getPublicChannels();

		foreach ($channels as $key => $channel) {
			$result[] = $this->publicChannelToPlain($channel);
			if ($key === 2) {
				break;
			}
		}

		return $result;
	}

	/**
	 * @param WiseChatChannel $channel
	 * @return array
	 * @throws Exception
	 */
	private function publicChannelToPlain($channel) {
		static $isChatFull = null;

		// TODO: "full" channel convert to "full" chat (including direct channels)
		if ($isChatFull === null) {
			$isChatFull = $this->service->isChatFull();
		}

		return array(
			'id' => $this->clientSide->encryptPublicChannelId($channel->getId()),
			'readOnly' => !$this->userService->isSendingMessagesAllowed() && !$this->authentication->isAuthenticatedExternally(),
			'type' => 'public',
			'name' => $channel->getName(),
			'avatar' => $this->options->getIconsURL() . 'public-channel.png',
			'full' => $isChatFull,
			'protected' => $this->isProtectedChannel($channel),
			'authorized' => $this->authorization->isUserAuthorizedForChannel($channel)
		);
	}

	/**
	 * @return array
	 */
	public function getDirectChannels() {
		$plainUsers = [];
		$channelUsers = $this->channelsSourcesService->getDirectChannels();

		foreach ($channelUsers as $channelUser) {
			$plainUser = $this->clientSide->getChannelUserAsPlainDirectChannel($channelUser);

			$plainUsers[$channelUser->getUser()->getId()] = $plainUser;
		}

		return array_values($plainUsers);
	}

	/**
	 * TODO: optimize
	 *
	 * @return integer
	 */
	public function getDirectChannelsNumber() {
		$plainUsers = [];
		$channelUsers = $this->channelsSourcesService->getDirectChannels();
		foreach ($channelUsers as $channelUser) {
			if ($channelUser->isActive()) {
				$plainUsers[$channelUser->getUser()->getId()] = true;
			}
		}

		return count($plainUsers);
	}

	/**
	 * @return string|null Channel client ID
	 */
	public function getAutoOpenChannel() {
		if ($this->options->isOptionNotEmpty('auto_open')) {
			$currentUser = $this->authentication->getUser();
			$channelUser = null;

			// try to read from cache:
			if ($currentUser->getDataProperty('auto_open_direct_channel')) {
				$autoOpenDirectChannelCandidate = $currentUser->getDataProperty('auto_open_direct_channel');
				$user = $this->usersDAO->get($autoOpenDirectChannelCandidate);
				if ($user) {
					$channelUser = $this->channelUsersDAO->getByUserId($user->getId());

					if ($channelUser) {
						$channelUser->setUser($user);
					} else {
						$channelUser = new WiseChatChannelUser();
						$channelUser->setUser($user);
						$channelUser->setActive(false);
						$channelUser->setUserId($user->getId());
					}
				}
			}

			if (!$channelUser) {
				$autoOpenChannelCandidates = (array) $this->options->getOption('auto_open', array());

				// TODO: settings - auto open strategy
				$offlineUsers = array();
				$onlineUsers = array();
				foreach ($autoOpenChannelCandidates as $autoOpenChannelCandidate) {
					if (intval($autoOpenChannelCandidate) === $currentUser->getId()) {
						continue;
					}

					$user = $this->usersDAO->createOrGetBasedOnWordPressUserId($autoOpenChannelCandidate);
					$channelUser = $this->channelUsersDAO->getByUserId($user->getId());

					if ($channelUser) {
						$channelUser->setUser($user);
						if ($channelUser->isActive()) {
							$onlineUsers[] = $channelUser;
						} else {
							$offlineUsers[] = $channelUser;
						}
					} else {
						$channelUser = new WiseChatChannelUser();
						$channelUser->setUser($user);
						$channelUser->setActive(false);
						$channelUser->setUserId($user->getId());
						$offlineUsers[] = $channelUser;
					}
				}

				$channelUser = null;
				if (count($onlineUsers) > 0) {
					$channelUser = $onlineUsers[array_rand($onlineUsers)];
				} else if (count($offlineUsers) > 0) {
					$channelUser = $offlineUsers[array_rand($offlineUsers)];
				}
			}

			if ($channelUser) {
				// add a welcome message:
				if (!$currentUser->hasDataProperty('auto_open_direct_channel')) {
					$welcomeMessage = $this->uiTemplates->getWelcomeMessage($channelUser->getUser(), $this->authentication->getUser());

					if ($welcomeMessage) {
						$this->messagesService->addMessage(
							$channelUser->getUser(), $this->channelsService->getDirectChannel(), $welcomeMessage, array(), false, $this->authentication->getUser(), null,
							array('disableFilters' => true, 'disableCrop' => true)
						);
					}
				}

				// store the selection:
				$currentUser->setDataProperty('auto_open_direct_channel', $channelUser->getUserId());
				$this->usersDAO->save($currentUser);

				$this->channelsStorage[] = $this->clientSide->getChannelUserAsPlainDirectChannel($channelUser);

				return $this->clientSide->encryptDirectChannelId($channelUser->getUserId());
			}
		} else {
			// open the 1st public channel:
			if ($this->options->isOptionEnabled('auto_open_first_public_channel', true) && $this->arePublicChannelsEnabled()) {
				$channels = (array) $this->options->getOption('channel');
				if (count($channels) > 0) {
					$channel = $this->channelsSourcesService->getPublicChannelByName($channels[0]);
					if ($channel) {
						$this->channelsStorage[] = $this->publicChannelToPlain($channel);

						return $this->clientSide->encryptPublicChannelId($channel->getId());
					}
				}
			}
		}

		return null;
	}

	public function getChannels() {
		return $this->channelsStorage;
	}

	public function arePublicChannelsEnabled() {
		return $this->options->getIntegerOption('mode', 0) === 0 && !($this->options->isOptionEnabled('classic_disable_channel', false))
					|| $this->options->getIntegerOption('mode', 0) === 1 && !($this->options->isOptionEnabled('fb_disable_channel', false));
	}

	/**
	 * @param WiseChatChannel $channel
	 * @return bool
	 */
	private function isProtectedChannel($channel) {
		return $channel !== null && $channel->getPassword();
	}

}