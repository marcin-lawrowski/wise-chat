<?php

namespace Kainex\WiseChat\Services;

use Exception;
use Kainex\WiseChat\DAO\Criteria\MessagesCriteria;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\ChannelsDAO;
use Kainex\WiseChat\DAO\MessagesDAO;
use Kainex\WiseChat\Integrations\OpenAI\OpenAIService;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Rendering\Filters\PostReversed\PostReversedFilter;
use Kainex\WiseChat\Rendering\Filters\Pre\PreFilter;
use Kainex\WiseChat\Rendering\Filters\Pre\LinksPreFilter;
use Kainex\WiseChat\Rendering\Filters\ShortcodeConstructor;
use Kainex\WiseChat\Rendering\UITemplates;
use Kainex\WiseChat\Services\ClientSide\ClientSide;
use Kainex\WiseChat\Services\Message\TextProcessing;
use Kainex\WiseChat\Services\User\AbusesService;
use Kainex\WiseChat\Services\User\ActionsService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Options;

/**
 * WiseChat messages services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MessagesService extends MessagesDAO {

	/**
	 * @var ChannelsService
	 */
	private $channelsService;

	/**
	 * @var ClientSide
	 */
	private $clientSide;

	/**
	 * @var UsersDAO
	 */
	private $usersDAO;

	/**
	 * @var ActionsService
	 */
	protected $actions;

	/**
	 * @var AttachmentsService
	 */
	private $attachmentsService;

	/**
	 * @var ImagesService
	 */
	private $imagesService;

	/**
	 * @var AbusesService
	 */
	private $abuses;

	/**
	 * @var UserMutesService
	 */
	private $userMutesService;

	/**
	 * @var NotificationsService
	 */
	private $notificationsService;

	/**
	 * @var UserNotificationsService
	 */
	private $userNotificationsService;

	/**
	 * @var AuthenticationService
	 */
	private $authentication;

	/**
	* @var Options
	*/
	private $options;

	/** @var ChannelsDAO */
	private $channelsDAO;

	/** @var FilterChain $filterChain */
	private $filterChain;

	/** @var LinksPreFilter $linksPreFilter */
	private $linksPreFilter;

	/** @var PostReversedFilter $filterReversed */
	private $filterReversed;

	private OpenAIService $openAIService;
	private UITemplates $uiTemplates;

	/**
	 * @param ChannelsService $channelsService
	 * @param ClientSide $clientSide
	 * @param UsersDAO $usersDAO
	 * @param ActionsService $actions
	 * @param AttachmentsService $attachmentsService
	 * @param ImagesService $imagesService
	 * @param AbusesService $abuses
	 * @param UserMutesService $userMutesService
	 * @param NotificationsService $notificationsService
	 * @param UserNotificationsService $userNotificationsService
	 * @param AuthenticationService $authentication
	 * @param Options $options
	 * @param ChannelsDAO $channelsDAO
	 * @param FilterChain $filterChain
	 * @param LinksPreFilter $linksPreFilter
	 * @param PostReversedFilter $filterReversed
	 * @param OpenAIService $openAIService
	 * @param UITemplates $uiTemplates
	 */
	public function __construct(ChannelsService $channelsService, ClientSide $clientSide, UsersDAO $usersDAO, ActionsService $actions, AttachmentsService $attachmentsService, ImagesService $imagesService, AbusesService $abuses, UserMutesService $userMutesService, NotificationsService $notificationsService, UserNotificationsService $userNotificationsService, AuthenticationService $authentication, Options $options, ChannelsDAO $channelsDAO, FilterChain $filterChain, LinksPreFilter $linksPreFilter, PostReversedFilter $filterReversed, \Kainex\WiseChat\Integrations\OpenAI\OpenAIService $openAIService, \Kainex\WiseChat\Rendering\UITemplates $uiTemplates) {
		parent::__construct($channelsDAO, $usersDAO);
		$this->channelsService = $channelsService;
		$this->clientSide = $clientSide;
		$this->usersDAO = $usersDAO;
		$this->actions = $actions;
		$this->attachmentsService = $attachmentsService;
		$this->imagesService = $imagesService;
		$this->abuses = $abuses;
		$this->userMutesService = $userMutesService;
		$this->notificationsService = $notificationsService;
		$this->userNotificationsService = $userNotificationsService;
		$this->authentication = $authentication;
		$this->options = $options;
		$this->channelsDAO = $channelsDAO;
		$this->filterChain = $filterChain;
		$this->linksPreFilter = $linksPreFilter;
		$this->filterReversed = $filterReversed;
		$this->openAIService = $openAIService;
		$this->uiTemplates = $uiTemplates;
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
	 * Maintenance actions performed very often.
	 *
	 * @throws Exception
	 */
	public function frequentMaintenance() {

	}

	/**
	 * Publishes a message in the given channel of the chat and returns it.
	 *
	 * @param User $user Author of the message
	 * @param Channel $channel A channel to publish in
	 * @param string $text Content of the message
	 * @param array $attachments Array of attachments (only single image is supported)
	 * @param boolean $isAdmin Indicates whether to mark the message as admin-owned
	 * @param User|null $recipient The recipient of the message
	 * @param Message|null $replyToMessage
	 * @param array $options
	 * @return Message|null
	 * @throws Exception On validation error
	 */
	public function addMessage($user, $channel, $text, $attachments, $isAdmin = false, $recipient = null, $replyToMessage = null, $options = array()) {
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
		if ($user->getId() > 0 && $this->authentication->getSystemUser()->getId() != $user->getId() && $this->userMutesService->isUserMuted($user)) {
			throw new Exception(__('You are not allowed to send messages. You have been muted.', 'wise-chat'));
		}

        // use bad words filtering:
        if ($this->options->isOptionEnabled('filter_bad_words')) {
            $badWordsFilterReplacement = $this->options->getOption('bad_words_replacement_text');
            $filteredMessage = PreFilter::filter(
                $filteredMessage,
                strlen($badWordsFilterReplacement) > 0 ? $badWordsFilterReplacement : null
            );
        }

		// auto-mute feature:
		if ($this->options->isOptionEnabled('enable_automute') && $filteredMessage != $text) {
			$counter = $this->abuses->incrementAndGetAbusesCounter();
			$threshold = $this->options->getIntegerOption('automute_threshold', 3);
			if ($counter >= $threshold && $threshold > 0) {
				$this->userMutesService->muteUser($user, $this->options->getIntegerOption('automute_duration', 1440));
				$this->abuses->clearAbusesCounter();
			}
		}

		// flood prevention feature:
		if ($this->options->isOptionEnabled('enable_flood_control')) {
			$floodControlThreshold = $this->options->getIntegerOption('flood_control_threshold', 200);
			$floodControlTimeFrame = $this->options->getIntegerOption('flood_control_time_frame', 1);
			if ($floodControlThreshold > 0 && $floodControlTimeFrame > 0) {
				$messagesAmount = $this->getNumberByCriteria(
					MessagesCriteria::build()
						->setUserId($user->getId())
						->setMinimumTime(time() - $floodControlTimeFrame * 60)
				);
				if ($messagesAmount > $floodControlThreshold) {
					$this->userMutesService->muteUser($user, $this->options->getIntegerOption('flood_control_mute_duration', 1440));
				}
			}
		}

		// go through the custom filters:
		$filteredMessage = $this->filterChain->filter($filteredMessage);

		// cut the message:
		if (!array_key_exists('disableCrop', $options)) {
			$filteredMessage = TextProcessing::cutMessageText($filteredMessage, $this->options->getIntegerOption('message_max_length', 100));
		}

		// convert images and links into proper shortcodes and download images (if enabled):
		if (!array_key_exists('disableFilters', $options)) {
			$filteredMessage = $this->linksPreFilter->filter(
				$filteredMessage,
				$this->options->isOptionEnabled('allow_post_images'),
				$this->options->isOptionEnabled('enable_youtube')
			);
		}

		$message = new Message();
		$message->setTime(time());
		$message->setAdmin($isAdmin);
		$message->setUserId($user->getId());
		$message->setText($filteredMessage);
		$message->setChannelId($channel->getId());
		if ($user->getWordPressId() !== null) {
			$message->setWordPressUserId($user->getWordPressId());
		}
		$message->setHidden($this->checkNewMessagesHidden());

		if ($this->options->isOptionEnabled('enable_reply_to_messages', true) && $replyToMessage !== null) {
			$message->setReplyToMessageId($replyToMessage->getId());
		}

		/**
		 * Filters a message just before it is inserted into the database.
		 *
		 * @param Message $message A fully prepared message object. Depending on enabled features:
		 *                                    - links are converted to internal tags: [link]
		 *                                    - image links are downloaded to Media Library and converted to internal tags: [img]
		 *                                    - the text of the message is trimmed and filtered using the bad words dictionary
		 *                                    - YouTube links are converted to internal tags: [youtube]
		 *                                    - images or files attached to the message via the uploader are converted to internal tags: [img] or [attachment]
		 * @param string $text A raw text of the message typed by chat user
		 *@since 2.3.2
		 *
		 */
		$message = apply_filters('wc_insert_message', $message, $text);

		// save the attachment and include it into the message:
		$attachmentIds = array();
		if (count($attachments) > 0) {
			list($attachmentShortcode, $attachmentIds) = $this->saveAttachments($channel, $attachments);
			$message->setText($message->getText() . $attachmentShortcode);
		}

		$message = $this->save($message);

		// mark attachments created by the links pre-filter:
		$createdAttachments = $this->linksPreFilter->getCreatedAttachments();
		if (count($createdAttachments) > 0) {
			$this->attachmentsService->markAttachmentsWithDetails($createdAttachments, $channel->getName(), $message->getId());
		}

		// mark attachments uploaded together with the message:
		if (count($attachmentIds) > 0) {
			$this->attachmentsService->markAttachmentsWithDetails($attachmentIds, $channel->getName(), $message->getId());
		}

		/**
 		 * Fires once a message has been saved.
 		 *
 		 * @param Message $message A message object.
 		 * @param array $attachmentIds Attachment IDs of the uploaded images or files
 		 *@since 2.3.2
 		 *
 		 */
 		do_action("wc_message_inserted", $message, $attachmentIds);

		if (defined('WISE_CHAT_VERSION_AI')) {
			$this->openAIService->onMessageAdded($message, $attachmentIds, $channel, $user);
		}
		$this->notifyParticipants($channel);

		return $message;
	}

	/**
	 * Saves message's content.
	 *
	 * @param Message $message
	 * @param string $rawHTML Raw HTML received from user
	 * @throws \Exception
	 */
	public function saveRawMessageContent($message, $rawHTML) {
		$messageMaxLength = $this->options->getIntegerOption('message_max_length', 100);
		$rawHTML = trim($rawHTML);
		$originalText = $message->getText();
		$newText = '';
		try {
			if (strlen($rawHTML) > 0) {
				$count = $this->filterReversed->getTextCharactersCount($rawHTML);
				if ($count > $messageMaxLength) {
					throw new \Exception('Number of characters exceeded');
				}

				$newText = $this->filterReversed->filtersReverse($rawHTML);
			}

			// update the message:
			$message->setText($newText);
			$this->save($message);

			/**
			 * Fires once a message has been updated.
			 *
			 * @param Message $message A message object.
			 *@since 2.3.2
			 *
			 */
			do_action("wc_message_updated", $message);

		} catch (\Exception $e) {
			throw new \Exception("Could not save the raw message content (".$e->getMessage().").");
		}
	}

	/**
	 * Checks if the current user's messages have to be hidden.
	 *
	 * @return boolean
	 */
	private function checkNewMessagesHidden() {
		return false;
	}

	/**
	 * Saves attachments in the Media Library and attaches them to the end of the message.
	 *
	 * @param Channel $channel
	 * @param array $attachments Array of attachments
	 *
	 * @return array Array consisting of the two elements: a shortcode representing the attachments and array of IDs of created attachments
	 */
	private function saveAttachments($channel, $attachments) {
		if (!is_array($attachments) || count($attachments) === 0) {
			return array(null, array());
		}

		$channelConfiguration = array_merge(ChannelsDAO::DEFAULT_CONFIGURATION, $channel->getConfiguration() ? $channel->getConfiguration() : []);
		$firstAttachment = $attachments[0];
		$data = $firstAttachment['data'];
		$data = substr($data, strpos($data, ",") + 1);
		$decodedData = base64_decode($data);

		$attachmentShortcode = null;
		$attachmentIds = array();
		if ($this->options->isOptionEnabled('enable_images_uploader') && $channelConfiguration['enableImages'] !== false && $firstAttachment['type'] === 'image') {
			$image = $this->imagesService->saveImage($decodedData);
			if (is_array($image)) {
				$attachmentShortcode = ' '.ShortcodeConstructor::getImageShortcode($image['id'], $image['image'], $image['image-th'], '_');
				$attachmentIds = array($image['id']);
			}
		}

		if ($this->options->isOptionEnabled('enable_attachments_uploader') && $channelConfiguration['enableAttachments'] !== false && $firstAttachment['type'] === 'file') {
			$fileName = $firstAttachment['name'];
			$file = $this->attachmentsService->saveAttachment($fileName, $decodedData, $channel->getName());
			if (is_array($file)) {
				$attachmentShortcode = ' '.ShortcodeConstructor::getAttachmentShortcode($file['id'], $file['file'], $fileName);
				$attachmentIds = array($file['id']);
			}
		}

		if ($firstAttachment['type'] === 'mp3' && $channelConfiguration['enableVoiceMessages'] !== false) {
			$fileName = $firstAttachment['name'].'.mp3';
			$file = $this->attachmentsService->saveAttachment($fileName, $decodedData, $channel->getName());
			if (is_array($file)) {
				$attachmentShortcode = ' '.ShortcodeConstructor::getSoundShortcode($file['id'], $file['file'], $fileName);
				$attachmentIds = array($file['id']);
			}
		}

		return array($attachmentShortcode, $attachmentIds);
	}

	/**
	 * @param Channel $channel
	 * @param ?Message $beforeMessage
	 * @return Message[]
	 * @throws Exception
	 */
	public function getMessagesOfChannel(Channel $channel, ?Message $beforeMessage = null): array {
		$criteria = new MessagesCriteria();
		$criteria->setChannelIDs(array($channel->getId()));
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setLimit($this->options->getIntegerOption('messages_preload_limit', 20));
		$criteria->setOrderMode(MessagesCriteria::ORDER_ASCENDING);

		if ($beforeMessage) {
			$criteria->setMaximumMessageId($beforeMessage->getId());
		}

		return $this->getAllByCriteria($criteria);
	}

	/**
	 * Returns all messages from the given channel and (optionally) beginning from the given offset.
	 * Limit and admin messages inclusion are taken from the plugin's options.
	 *
	 * @param Channel[] $channels
	 * @param int|null $fromId Begin from specific message ID
	 * @return Message[]
	 * @throws Exception
	 */
	public function getAllByChannelsAndOffset(array $channels, ?int $fromId = null): array {

		$criteria = new MessagesCriteria();
		$criteria->setChannelIDs(array_map(function($channel) { return $channel->getId(); }, $channels));
		$criteria->setOffsetId($fromId);
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setLimit($this->options->getIntegerOption('messages_limit', 50));
		$criteria->setOrderMode(MessagesCriteria::ORDER_ASCENDING);

		return $this->getAllByCriteria($criteria);
	}

	/**
	 * Returns all messages from the given channel.
	 * Limit and admin messages inclusion are taken from the plugin's options.
	 *
	 * @param integer $channelID
	 *
	 * @return Message[]
	 * @throws Exception
	 */
	public function getPortionByChannelId(int $channelID): array {
		$criteria = new MessagesCriteria();
		$criteria->setChannelIDs(array($channelID));
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setLimit($this->options->getIntegerOption('messages_limit', 50));
		$criteria->setOrderMode(MessagesCriteria::ORDER_ASCENDING);

		return $this->getAllByCriteria($criteria);
	}

	/**
	 * Returns all messages from the given channel without limit and with the default order.
	 * Admin messages are not returned.
	 *
	 * @param integer $channelID
	 *
	 * @return Message[]
	 * @throws Exception
	 */
	public function getAllByChannelID(int $channelID): array {
		return $this->getAllByCriteria(MessagesCriteria::build()->setChannelIDs(array($channelID)));
	}

	/**
	 * Returns message by ID.
	 *
	 * @param integer $id
	 * @param bool $populateUser
	 * @return Message|null
	 */
	public function getById($id, $populateUser = false) {
		$message = $this->get($id);
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
	 * @param integer $channelID
	 *
	 * @return integer
	 */
	public function getNumberByChannelId($channelID) {
		return $this->getNumberByCriteria(MessagesCriteria::build()->setChannelIDs(array($channelID)));
	}

	/**
	 * Deletes message by ID.
	 * Images connected to the message (WordPress Media Library attachments) are also deleted.
	 *
	 * @param integer $id
	 */
	public function deleteById($id) {
		$message = $this->get($id);
		if ($message !== null) {
			$this->deleteBy(['id' => [$id, '%d']]);
			$this->attachmentsService->deleteAttachmentsByMessageIds(array($id));

			/**
			 * Fires once a message has been deleted.
			 *
			 * @param Message $message A deleted message object.
			 *@since 2.3.2
			 *
			 */
			do_action("wc_message_deleted", $message);
		}
	}

	/**
	 * Approves message by ID.
	 *
	 * @param integer $id
	 */
	public function approveById($id) {
		$this->unhideById($id);

		$message = $this->get($id);
		/**
		 * Fires once a message has been approved.
		 *
		 * @param Message $message A message object.
		 *@since 2.3.2
		 *
		 */
		do_action("wc_message_approved", $message);
	}

	/**
	 * Replicates message and makes it visible (not hidden).
	 *
	 * @param Message $message
	 * @throws Exception
	 */
	public function replicateHiddenMessage($message) {
		$clone = $message->getClone();
		$clone->setTime(time());
		$clone->setHidden(false);
		$this->save($clone);

		$messagesIds = array();
		if ($this->options->isOptionEnabled('enable_reply_to_messages', true)) {
			$replies = $this->getAllRepliesToMessage($message);
			foreach ($replies as $reply) {
				$replyClone = $reply->getClone();
				$replyClone->setTime(time());
				$replyClone->setHidden(false);
				$replyClone->setReplyToMessageId($clone->getId());
				$this->save($replyClone);

				$messagesIds[] = $reply->getId();
				$this->deleteById($reply->getId());
			}
		}

		$messagesIds[] = $message->getId();
		$this->deleteById($message->getId());

		$this->actions->publishAction('deleteMessages', array('ids' => $this->clientSide->encryptMessageIds($messagesIds)));
	}

	/**
	 * Deletes all messages (in all channels).
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 */
	public function deleteAll() {
		$this->deleteAllByCriteria(MessagesCriteria::build()->setIncludeAdminMessages(true));
		$this->attachmentsService->deleteAllAttachments();
	}

	/**
	 * Deletes all messages from specified channel.
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @param integer $channelID
	 * @throws Exception
	 */
	public function deleteByChannel($channelID) {
		$this->deleteAllByCriteria(
            MessagesCriteria::build()
                ->setChannelIDs(array($channelID))
                ->setIncludeAdminMessages(true)
        );
		$this->attachmentsService->deleteAttachmentsByChannel($channelID);
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
		$message = $this->get($messageId);
		if ($message === null || $currentUser === null) {
			return;
		}
		$channel = $this->channelsService->get($message->getChannelId());
		if (!$channel) {
			return;
		}

		$user = $this->usersDAO->get($message->getUserId());

		$variables = array(
			'url' => $url,
			'channel' => $channel->getName(),
			'message' => $message->getText(),
			'message-user' => $user ? $user->getName() : 'Unknown',
			'message-user-ip' => $user ? $user->getIp() : 'Unknown',
			'report-user' => $currentUser->getName(),
			'report-user-ip' => $currentUser->getIp()
		);
		foreach ($variables as $key => $variable) {
			$content = str_replace(array('${'.$key.'}', '{'.$key.'}'), $variable, $content);
		}
		wp_mail($recipient, $subject, $content);

		/**
		 * Fires once a spam message has been reported.
		 *
		 * @param Message $message A reported spam message
		 * @param string $url URL of the chat page
		 *@since 2.3.2
		 *
		 */
		do_action("wc_spam_reported", $message, $url);
	}

	/**
	 * Deletes old messages if auto-remove option is on.
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @throws Exception
	 */
	private function deleteOldMessages() {
		$minutesThreshold = $this->options->getIntegerOption('auto_clean_after', 0);
		$minutesThresholdOfDirect = $this->options->getIntegerOption('auto_clean_direct_after', 0);

		$messagesIds = array();
		if ($minutesThreshold > 0) {
			$channels = $this->channelsDAO->getByNames((array) $this->options->getOption('channel'));

			$criteria = new MessagesCriteria();
			$criteria->setChannelIDs(array_map(function($channel) { return $channel->getId(); }, $channels));
			$criteria->setIncludeAdminMessages(true);
			$criteria->setMaximumTime(time() - $minutesThreshold * 60);
			$messages = $this->getAllByCriteria($criteria);
			foreach ($messages as $message) {
				$messagesIds[] = $message->getId();
			}
			$this->deleteAllByCriteria($criteria);
		}
		if ($minutesThresholdOfDirect > 0) {
			$criteria = new MessagesCriteria();
			$criteria->setIncludeAdminMessages(true);
			$criteria->setMaximumTime(time() - $minutesThresholdOfDirect * 60);
			$messages = $this->getAllByCriteria($criteria);
			foreach ($messages as $message) {
				$messagesIds[] = $message->getId();
			}
			$this->deleteAllByCriteria($criteria);
		}

		if (count($messagesIds) > 0) {
			$this->attachmentsService->deleteAttachmentsByMessageIds($messagesIds);
			$this->actions->publishAction('deleteMessages', array('ids' => $this->clientSide->encryptMessageIds($messagesIds)));
		}
	}

	private function notifyParticipants(Channel $channel) {
		if ($channel->getType() === Channel::TYPE_DIRECT) {
			$members = $this->channelsService->getChannelMembers($channel, true);
			foreach ($members as $member) {
				if ($member->getUserId() !== $this->authentication->getUserIdOrNull() && !$this->channelsService->isChannelOpen($member->getUserId(), $channel->getId())) {
					$this->actions->publishAction('incomingMessage', array('channelId' => $this->clientSide->encryptDirectChannelId($this->authentication->getUserIdOrNull()), 'channelName' => $this->authentication->getUser()->getName()), $member->getUserId());
				}
			}
		}

	}

	public function addWelcomeMessage(Channel $channel) {
		if ($channel->getType() !== Channel::TYPE_DIRECT) {
			return;
		}

		$otherSideUser = null;
		$members = $this->channelsService->getChannelMembers($channel, true);
		foreach ($members as $member) {
			if ($member->getUserId() !== $this->authentication->getUserIdOrNull()) {
				$otherSideUser = $member->getUser();
				break;
			}
		}
		if (!$otherSideUser || !$this->uiTemplates->hasWelcomeMessage($otherSideUser, $this->authentication->getUser())) {
			return;
		}

		$lastMessages = $this->getLatestMessages([$channel->getId()], $otherSideUser->getId(), 1);
		if (count($lastMessages) === 1 && $lastMessages[0]->getTime() > (time() - 60 * 60)) {
			return;
		}

		$welcomeMessage = $this->uiTemplates->getWelcomeMessage($otherSideUser, $this->authentication->getUser());
		$this->addMessage(
			$otherSideUser, $channel, $welcomeMessage, array(), false, null, null, array('disableFilters' => true, 'disableCrop' => true)
		);
	}

}