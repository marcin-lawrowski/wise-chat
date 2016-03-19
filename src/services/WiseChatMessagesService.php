<?php

/**
 * WiseChat messages services.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatMessagesService {

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
	
	public function __construct() {
		WiseChatContainer::load('dao/criteria/WiseChatMessagesCriteria');
		$this->options = WiseChatOptions::getInstance();
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->messagesDAO = WiseChatContainer::get('dao/WiseChatMessagesDAO');
		$this->actions = WiseChatContainer::getLazy('services/user/WiseChatActions');
		$this->attachmentsService = WiseChatContainer::get('services/WiseChatAttachmentsService');
		$this->imagesService = WiseChatContainer::get('services/WiseChatImagesService');
		$this->abuses = WiseChatContainer::getLazy('services/user/WiseChatAbuses');
		$this->bansService = WiseChatContainer::get('services/WiseChatBansService');
		$this->authentication = WiseChatContainer::getLazy('services/user/WiseChatAuthentication');
	}
	
	/**
	* Maintenance actions performed at start-up.
	*
	* @param WiseChatChannel $channel
	*
	* @return null
	*/
	public function startUpMaintenance($channel) {
		$this->deleteOldMessages($channel);
	}
	
	/**
	* Maintenance actions performed periodically.
	*
	* @param WiseChatChannel $channel
	*
	* @return null
	*/
	public function periodicMaintenance($channel) {
		$this->deleteOldMessages($channel);
	}

	/**
	 * Publishes a message in the given channel of the chat and returns it.
	 *
	 * @param WiseChatUser $user Author of the message
	 * @param WiseChatChannel $channel A channel to publish in
	 * @param string $text Content of the message
	 * @param boolean $isAdmin Indicates whether to mark the message as admin-owned
	 *
	 * @return WiseChatMessage|null
	 * @throws Exception On validation error
	 */
	public function addMessage($user, $channel, $text, $isAdmin = false) {
		$text = trim($text);
		$filteredMessage = $text;

		// basic validation:
		if ($user === null) {
			throw new Exception('User cannot be null');
		}
		if ($channel === null) {
			throw new Exception('Channel cannot be null');
		}

		if ($this->bansService->isIpAddressBanned($user->getIp())) {
			throw new Exception($this->options->getOption('message_error_3', 'You were banned from posting messages'));
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
		$filterChain = WiseChatContainer::get('services/WiseChatFilterChain');
		$filteredMessage = $filterChain->filter($filteredMessage);

		// cut the message:
		$messageMaxLength = $this->options->getIntegerOption('message_max_length', 100);
		if ($messageMaxLength > 0) {
			$filteredMessage = substr($filteredMessage, 0, $messageMaxLength);
		}

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
		$message->setText($filteredMessage);
		$message->setChannelName($channel->getName());
		$message->setIp($user->getIp());
		if ($user->getWordPressId() !== null) {
			$message->setWordPressUserId($user->getWordPressId());
		}

		$message = $this->messagesDAO->save($message);

		// mark attachments created by the links pre-filter:
		$createdAttachments = $linksPreFilter->getCreatedAttachments();
		if (count($createdAttachments) > 0) {
			$this->attachmentsService->markAttachmentsWithDetails($createdAttachments, $channel->getName(), $message->getId());
		}

		return $message;
	}

	/**
	 * Publishes a message with given attachments in the given channel.
	 *
	 * @param WiseChatUser $user Author of the message
	 * @param WiseChatChannel $channel A channel to publish in
	 * @param string $text Content of the message
	 * @param array $attachments Array of attachments (only single image is supported)
	 *
	 * @return WiseChatMessage|null Added message
	 * @throws Exception On validation error
	 */
	public function addMessageWithAttachments($user, $channel, $text, $attachments) {
		$message = $this->addMessage($user, $channel, $text);
		list($attachmentShortcode, $attachmentIds)= $this->saveAttachments($channel, $attachments);
		$this->attachmentsService->markAttachmentsWithDetails($attachmentIds, $channel->getName(), $message->getId());

		$message->setText($message->getText().$attachmentShortcode);
		$this->messagesDAO->save($message);

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
	 * Limit, order and admin messages inclusion are taken from the plugin's options.
	 *
	 * @param string $channelName Name of the channel
	 * @param integer $fromId Begin from specific message ID
	 *
	 * @return WiseChatMessage[]
	 */
	public function getAllByChannelNameAndOffset($channelName, $fromId = null) {
		$orderMode = $this->options->getEncodedOption('messages_order', '');


		$criteria = new WiseChatMessagesCriteria();
		$criteria->setChannelName($channelName);
		$criteria->setOffsetId($fromId);
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setLimit($this->options->getIntegerOption('messages_limit', 100));
		$criteria->setOrderMode(
			$orderMode == WiseChatMessagesCriteria::ORDER_DESCENDING ? $orderMode : WiseChatMessagesCriteria::ORDER_ASCENDING
		);

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
		return $this->messagesDAO->getAllByCriteria(WiseChatMessagesCriteria::build()->setChannelName($channelName));
	}

	/**
	 * Returns number of messages in the channel.
	 *
	 * @param string $channelName Name of the channel
	 *
	 * @return integer
	 */
	public function getNumberByChannelName($channelName) {
		return $this->messagesDAO->getNumberByCriteria(WiseChatMessagesCriteria::build()->setChannelName($channelName));
	}

	/**
	 * Deletes message by ID.
	 * Images connected to the message (WordPress Media Library attachments) are also deleted.
	 *
	 * @param integer $id
	 *
	 * @return null
	 */
	public function deleteById($id) {
		if ($this->messagesDAO->get($id) !== null) {
			$this->messagesDAO->deleteById($id);
			$this->attachmentsService->deleteAttachmentsByMessageIds(array($id));
		}
	}

	/**
	 * Deletes all messages (in all channels).
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @return null
	 */
	public function deleteAll() {
		$this->messagesDAO->deleteAllByCriteria(WiseChatMessagesCriteria::build()->setIncludeAdminMessages(true));
		$this->attachmentsService->deleteAllAttachments();
	}

	/**
	 * Deletes all messages from specified channel.
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @param string $channelName Name of the channel
	 *
	 * @return null
	 */
	public function deleteByChannel($channelName) {
		$this->messagesDAO->deleteAllByCriteria(
            WiseChatMessagesCriteria::build()
                ->setChannelName($channelName)
                ->setIncludeAdminMessages(true)
        );
		$this->attachmentsService->deleteAttachmentsByChannel($channelName);
	}

	/**
	 * Deletes old messages according to the plugin's settings.
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @param WiseChatChannel $channel
	 *
	 * @throws Exception
	 */
	private function deleteOldMessages($channel) {
		$minutesThreshold = $this->options->getIntegerOption('auto_clean_after', 0);
		
		if ($minutesThreshold > 0) {
			$criteria = new WiseChatMessagesCriteria();
			$criteria->setChannelName($channel->getName());
			$criteria->setIncludeAdminMessages(true);
			$criteria->setMaximumTime(time() - $minutesThreshold * 60);
			$messages = $this->messagesDAO->getAllByCriteria($criteria);

			$messagesIds = array();
			foreach ($messages as $message) {
				$messagesIds[] = $message->getId();
			}

			if (count($messagesIds) > 0) {
				$this->attachmentsService->deleteAttachmentsByMessageIds($messagesIds);
				$this->actions->publishAction('deleteMessages', array('ids' => $messagesIds));
				$this->messagesDAO->deleteAllByCriteria($criteria);
			}
		}
	}
}