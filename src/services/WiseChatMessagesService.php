<?php

/**
 * WiseChat messages services.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMessagesService {

	/**
	 * @var WiseChatClientSide
	 */
	private $clientSide;

	/**
	 * @var WiseChatUsersDAO
	 */
	private $usersDAO;

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;

	/**
	 * @var WiseChatAttachmentsService
	 */
	private $attachmentsService;

	/**
	 * @var WiseChatImagesService
	 */
	private $imagesService;

	/**
	 * @var WiseChatAbuses
	 */
	private $abuses;

	/**
	 * @var WiseChatBansService
	 */
	private $bansService;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	* @var WiseChatOptions
	*/
	private $options;

	/** @var WiseChatChannelsDAO */
	private $channelsDAO;
	
	public function __construct() {
		WiseChatContainer::load('dao/criteria/WiseChatMessagesCriteria');
		WiseChatContainer::load('services/message/WiseChatTextProcessing');
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->messagesDAO = WiseChatContainer::get('dao/WiseChatMessagesDAO');
		$this->actions = WiseChatContainer::getLazy('services/user/WiseChatActions');
		$this->attachmentsService = WiseChatContainer::get('services/WiseChatAttachmentsService');
		$this->imagesService = WiseChatContainer::get('services/WiseChatImagesService');
		$this->abuses = WiseChatContainer::getLazy('services/user/WiseChatAbuses');
		$this->bansService = WiseChatContainer::get('services/WiseChatBansService');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
		$this->clientSide = WiseChatContainer::getLazy('services/client-side/WiseChatClientSide');
		$this->channelsDAO = WiseChatContainer::getLazy('dao/WiseChatChannelsDAO');
	}
	
	/**
	* Maintenance actions performed at start-up.
	*/
	public function startUpMaintenance() {
		$this->deleteOldMessages();
	}

	/**
	 * Maintenance actions performed periodically.
	 *
	 * @throws Exception
	 */
	public function periodicMaintenance() {
		$this->deleteOldMessages();
	}

	/**
	 * Publishes a message in the given channel of the chat and returns it.
	 *
	 * @param WiseChatUser $user Author of the message
	 * @param WiseChatChannel $channel A channel to publish in
	 * @param string $text Content of the message
	 * @param array $attachments Array of attachments (only single image is supported)
	 * @param boolean $isAdmin Indicates whether to mark the message as admin-owned
	 *
	 * @return WiseChatMessage|null
	 * @throws Exception On validation error
	 */
	public function addMessage($user, $channel, $text, $attachments, $isAdmin = false) {
		$text = trim($text);
		$filteredMessage = $text;

		// basic validation:
		if ($user === null) {
			throw new Exception('User cannot be null');
		}
		if ($channel === null) {
			throw new Exception('Channel cannot be null');
		}

		// check if the user has been muted
		if ($user->getId() > 0 && $this->authentication->getSystemUser()->getId() != $user->getId() && $this->bansService->isIpAddressBanned($user->getIp())) {
			throw new Exception($this->options->getOption('message_error_15', __('You are not allowed to send messages. You have been muted.', 'wise-chat')));
		}

        // use bad words filtering:
        if ($this->options->isOptionEnabled('filter_bad_words')) {
            WiseChatContainer::load('rendering/filters/pre/WiseChatFilter');
            $badWordsFilterReplacement = $this->options->getOption('bad_words_replacement_text');
            $filteredMessage = WiseChatFilter::filter(
                $filteredMessage,
                strlen($badWordsFilterReplacement) > 0 ? $badWordsFilterReplacement : null
            );
        }

		// auto-ban feature:
		if ($this->options->isOptionEnabled('enable_autoban') && $filteredMessage != $text) {
			$counter = $this->abuses->incrementAndGetAbusesCounter();
			$threshold = $this->options->getIntegerOption('autoban_threshold', 3);
			if ($counter >= $threshold && $threshold > 0) {
				$duration = $this->options->getIntegerOption('autoban_duration', 1440);
				$this->bansService->banIpAddress(
					$user->getIp(), $this->bansService->getDurationFromString($duration.'m')
				);
				$this->abuses->clearAbusesCounter();
			}
		}

		// flood prevention feature:
		if ($this->options->isOptionEnabled('enable_flood_control')) {
			$floodControlThreshold = $this->options->getIntegerOption('flood_control_threshold', 200);
			$floodControlTimeFrame = $this->options->getIntegerOption('flood_control_time_frame', 1);
			if ($floodControlThreshold > 0 && $floodControlTimeFrame > 0) {
				$messagesAmount = $this->messagesDAO->getNumberByCriteria(
					WiseChatMessagesCriteria::build()
						->setIp($user->getIp())
						->setMinimumTime(time() - $floodControlTimeFrame * 60)
				);
				if ($messagesAmount > $floodControlThreshold) {
					$duration = $this->options->getIntegerOption('flood_control_ban_duration', 1440);
					$this->bansService->banIpAddress(
						$user->getIp(), $this->bansService->getDurationFromString($duration.'m')
					);
				}
			}
		}

		// go through the custom filters:
		/** @var WiseChatFilterChain $filterChain */
		$filterChain = WiseChatContainer::get('services/WiseChatFilterChain');
		$filteredMessage = $filterChain->filter($filteredMessage);

		// cut the message:
		$filteredMessage = WiseChatTextProcessing::cutMessageText($filteredMessage, $this->options->getIntegerOption('message_max_length', 100));

		// convert images and links into proper shortcodes and download images (if enabled):
		/** @var WiseChatLinksPreFilter $linksPreFilter */
		$linksPreFilter = WiseChatContainer::get('rendering/filters/pre/WiseChatLinksPreFilter');
		$filteredMessage = $linksPreFilter->filter(
			$filteredMessage,
			$this->options->isOptionEnabled('allow_post_images'),
            $this->options->isOptionEnabled('enable_youtube')
		);

		$message = new WiseChatMessage();
		$message->setTime(time());
		$message->setAdmin($isAdmin);
		$message->setUserName($user->getName());
		$message->setUserId($user->getId());
		$message->setAvatarUrl($this->getUserAvatar($user));
		$message->setText($filteredMessage);
		$message->setChannelName($channel->getName());
		$message->setIp($user->getIp());
		if ($user->getWordPressId() !== null) {
			$message->setWordPressUserId($user->getWordPressId());
		}

		// save the attachment and include it into the message:
		$attachmentIds = array();
		if (count($attachments) > 0) {
			list($attachmentShortcode, $attachmentIds) = $this->saveAttachments($channel, $attachments);
			$message->setText($message->getText() . $attachmentShortcode);
		}

		$message = $this->messagesDAO->save($message);

		// mark attachments created by the links pre-filter:
		$createdAttachments = $linksPreFilter->getCreatedAttachments();
		if (count($createdAttachments) > 0) {
			$this->attachmentsService->markAttachmentsWithDetails($createdAttachments, $channel->getName(), $message->getId());
		}

		// mark attachments uploaded together with the message:
		if (count($attachmentIds) > 0) {
			$this->attachmentsService->markAttachmentsWithDetails($attachmentIds, $channel->getName(), $message->getId());
		}

		return $message;
	}

	/**
	 * Saves attachments in the Media Library and attaches them to the end of the message.
	 *
	 * @param WiseChatChannel $channel
	 * @param array $attachments Array of attachments
	 *
	 * @return array Array consisting of the two elements: a shortcode representing the attachments and array of IDs of created attachments
	 */
	private function saveAttachments($channel, $attachments) {
		if (!is_array($attachments) || count($attachments) === 0) {
			return array(null, array());
		}
        WiseChatContainer::load('rendering/filters/WiseChatShortcodeConstructor');

		$firstAttachment = $attachments[0];
		$data = $firstAttachment['data'];
		$data = substr($data, strpos($data, ",") + 1);
		$decodedData = base64_decode($data);

		$attachmentShortcode = null;
		$attachmentIds = array();
		if ($this->options->isOptionEnabled('enable_images_uploader') && $firstAttachment['type'] === 'image') {
			$image = $this->imagesService->saveImage($decodedData);
			if (is_array($image)) {
				$attachmentShortcode = ' '.WiseChatShortcodeConstructor::getImageShortcode($image['id'], $image['image'], $image['image-th'], '_');
				$attachmentIds = array($image['id']);
			}
		}

		if ($this->options->isOptionEnabled('enable_attachments_uploader') && $firstAttachment['type'] === 'file') {
			$fileName = $firstAttachment['name'];
			$file = $this->attachmentsService->saveAttachment($fileName, $decodedData, $channel->getName());
			if (is_array($file)) {
				$attachmentShortcode = ' '.WiseChatShortcodeConstructor::getAttachmentShortcode($file['id'], $file['file'], $fileName);
				$attachmentIds = array($file['id']);
			}
		}

		return array($attachmentShortcode, $attachmentIds);
	}

	/**
	 * Returns all messages from the given channel and (optionally) beginning from the given offset.
	 * Limit and admin messages inclusion are taken from the plugin's options.
	 *
	 * @param array $channelNames Channels
	 * @param integer $fromId Begin from specific message ID
	 *
	 * @return WiseChatMessage[]
	 * @throws Exception
	 */
	public function getAllByChannelNamesAndOffset($channelNames, $fromId = null) {
		$criteria = new WiseChatMessagesCriteria();
		$criteria->setChannelNames($channelNames);
		$criteria->setOffsetId($fromId);
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setLimit($this->options->getIntegerOption('messages_limit', 100));
		$criteria->setOrderMode(WiseChatMessagesCriteria::ORDER_ASCENDING);

		return $this->messagesDAO->getAllByCriteria($criteria);
	}

	/**
	 * Returns all messages from the given channel.
	 * Limit and admin messages inclusion are taken from the plugin's options.
	 *
	 * @param string $channelName Name of the channel
	 *
	 * @return WiseChatMessage[]
	 * @throws Exception
	 */
	public function getAllPublicByChannelNameAndUser($channelName) {
		$criteria = new WiseChatMessagesCriteria();
		$criteria->setChannelNames(array($channelName));
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setIncludeOnlyPrivateMessages(false);
		$criteria->setLimit($this->options->getIntegerOption('messages_limit', 100));
		$criteria->setOrderMode(WiseChatMessagesCriteria::ORDER_ASCENDING);

		return $this->messagesDAO->getAllByCriteria($criteria);
	}

	/**
	 * Returns all messages from the given channel without limit and with the default order.
	 * Admin messages are not returned.
	 *
	 * @param string $channelName Name of the channel
	 *
	 * @return WiseChatMessage[]
	 */
	public function getAllByChannelName($channelName) {
		return $this->messagesDAO->getAllByCriteria(WiseChatMessagesCriteria::build()->setChannelNames(array($channelName)));
	}

	/**
	 * Returns all private messages from the given channel.
	 *
	 * @param string $channelName Name of the channel
	 *
	 * @return WiseChatMessage[]
	 */
	public function getAllPrivateByChannelName($channelName) {
		$criteria = new WiseChatMessagesCriteria();
		$criteria->setChannelNames(array($channelName));
		$criteria->setIncludeAdminMessages(false);
		$criteria->setIncludeOnlyPrivateMessages(true);

		return $this->messagesDAO->getAllByCriteria($criteria);
	}

	/**
	 * Returns message by ID.
	 *
	 * @param integer $id
	 * @param bool $populateUser
	 * @return WiseChatMessage|null
	 */
	public function getById($id, $populateUser = false) {
		$message = $this->messagesDAO->get($id);
		if (!$message) {
			return null;
		}

		if ($populateUser) {
			$message->setUser($this->usersDAO->get($message->getUserId()));
		}

		return $message;
	}

	/**
	 * Returns number of messages in the channel.
	 *
	 * @param string $channelName Name of the channel
	 *
	 * @return integer
	 */
	public function getNumberByChannelName($channelName) {
		return $this->messagesDAO->getNumberByCriteria(WiseChatMessagesCriteria::build()->setChannelNames(array($channelName)));
	}

	/**
	 * Deletes message by ID.
	 * Images connected to the message (WordPress Media Library attachments) are also deleted.
	 *
	 * @param integer $id
	 */
	public function deleteById($id) {
		$message = $this->messagesDAO->get($id);
		if ($message !== null) {
			$this->messagesDAO->deleteById($id);
			$this->attachmentsService->deleteAttachmentsByMessageIds(array($id));
		}
	}

	/**
	 * Deletes all messages (in all channels).
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 */
	public function deleteAll() {
		$this->messagesDAO->deleteAllByCriteria(WiseChatMessagesCriteria::build()->setIncludeAdminMessages(true));
		$this->messagesDAO->deleteAllByCriteria(WiseChatMessagesCriteria::build()->setIncludeAdminMessages(true)->setIncludeOnlyPrivateMessages(true));
		$this->attachmentsService->deleteAllAttachments();
	}

	/**
	 * Deletes all messages from specified channel.
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @param string $channelName Name of the channel
	 * @throws Exception
	 */
	public function deleteByChannel($channelName) {
		$this->messagesDAO->deleteAllByCriteria(
            WiseChatMessagesCriteria::build()
                ->setChannelNames(array($channelName))
                ->setIncludeAdminMessages(true)
        );
		$this->messagesDAO->deleteAllByCriteria(
			WiseChatMessagesCriteria::build()
				->setChannelNames(array($channelName))
				->setIncludeAdminMessages(true)
				->setIncludeOnlyPrivateMessages(true)
		);
		$this->attachmentsService->deleteAttachmentsByChannel($channelName);
	}

	/**
	 * Sends a notification e-mail reporting spam message.
	 *
	 * @param integer $channelId
	 * @param integer $messageId
	 * @param string $url
	 */
	public function reportSpam($channelId, $messageId, $url) {
		$recipient = $this->options->getOption('spam_report_recipient', get_option('admin_email'));
		$subject = $this->options->getOption('spam_report_subject', '[Wise Chat] Spam Report');
		$contentDefaultTemplate = "Wise Chat Spam Report\n\n".
			'Channel: {channel}'."\n".
			'Message: {message}'."\n".
			'Posted by: {message-user}'."\n".
			'Posted from IP: {message-user-ip}'."\n\n".
			"--\n".
			'This e-mail was sent by {report-user} from {url}'."\n".
			'{report-user-ip}';
		$content = $this->options->getOption('spam_report_content', $contentDefaultTemplate);
		if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
			return;
		}
		$currentUser = $this->authentication->getUser();
		$message = $this->messagesDAO->get($messageId);
		if ($message === null || $currentUser === null) {
			return;
		}
		$variables = array(
			'url' => $url,
			'channel' => $message->getChannelName(),
			'message' => $message->getText(),
			'message-user' => $message->getUserName(),
			'message-user-ip' => $message->getIp(),
			'report-user' => $currentUser->getName(),
			'report-user-ip' => $currentUser->getIp()
		);
		foreach ($variables as $key => $variable) {
			$content = str_replace(array('${'.$key.'}', '{'.$key.'}'), $variable, $content);
		}
		wp_mail($recipient, $subject, $content);
	}

	/**
	 * Deletes old messages if auto-remove option is on.
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @throws Exception
	 */
	private function deleteOldMessages() {
		$minutesThreshold = $this->options->getIntegerOption('auto_clean_after', 0);
		
		if ($minutesThreshold > 0) {
			$channels = $this->channelsDAO->getByNames((array) $this->options->getOption('channel'));

			$criteria = new WiseChatMessagesCriteria();
			$criteria->setChannelNames(array_map(function($channel) { return $channel->getName(); }, $channels));
			$criteria->setIncludeAdminMessages(true);
			$criteria->setMaximumTime(time() - $minutesThreshold * 60);
			$criteria->setIncludeOnlyPrivateMessages(false);
			$messages = $this->messagesDAO->getAllByCriteria($criteria);

			$messagesIds = array();
			foreach ($messages as $message) {
				$messagesIds[] = $message->getId();
			}

			if (count($messagesIds) > 0) {
				$this->attachmentsService->deleteAttachmentsByMessageIds($messagesIds);
				$this->actions->publishAction('deleteMessages', array('ids' => $this->clientSide->encryptMessageIds($messagesIds)));
				$this->messagesDAO->deleteAllByCriteria($criteria);
			}
		}
	}

	/**
	 * @param WiseChatUser $user
	 *
	 * @return string|null
	 */
	private function getUserAvatar($user) {
		$imageSrc = null;

		if ($user !== null && $user->getWordPressId() !== null) {
			$imageTag = get_avatar($user->getWordPressId());
			if ($imageTag === false) {
				$imageSrc = $this->options->getIconsURL().'user.png';
			} else {
				$doc = new DOMDocument();
				$doc->loadHTML($imageTag);
				$imageTags = $doc->getElementsByTagName('img');
				foreach ($imageTags as $tag) {
					$imageSrc = $tag->getAttribute('src');
				}
			}
		} else {
			$imageSrc = $this->options->getIconsURL().'user.png';
		}

		return $imageSrc;
	}
}