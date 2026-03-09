<?php

namespace Kainex\WiseChat\Endpoints;

use Exception;
use Kainex\WiseChat\Container;
use Kainex\WiseChat\Exceptions\UnauthorizedAccessException;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Services\ImagesService;
use Kainex\WiseChat\Crypt;

/**
 * Wise Chat user commands endpoint class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserCommandEndpoint extends WiseChatEndpoint {

	/**
	 * User commands endpoint.
	 */
	public function userCommandEndpoint(): array {
		$response = array();

		$command = $this->getPostParam('command');
		$parameters = $this->getPostParam('parameters');
		switch ($command) {
			case 'markChannelAsRead':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channel']);
				$this->channelsService->ensureReadAccess($channel);
				$this->pendingChatsService->setPendingChatChecked($this->authentication->getUser(), $channel);

				$response['value'] = 'OK';
				break;
			case 'setUserProperty':
				$response['value'] = $this->handleSetUserPropertyCommands($parameters);
				break;
			case 'saveMessage':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$message = $this->saveMessage($parameters);
				$clientChannels = $this->clientSide->convertToClientChannels([$channel]);
				$response['result'] = 'OK';
				$response['message'] = $this->clientSide->toPlainMessage($message, $clientChannels[$channel->getId()]);
				break;
			case 'deleteMessage':
				$this->deleteMessage($parameters);
				$response['result'] = 'OK';
				break;
			case 'muteUser':
				$this->muteUser($parameters);
				$response['result'] = 'OK';
				break;
			case 'unMuteUser':
				$this->unMuteUser($parameters);
				$response['result'] = 'OK';
				break;
			case 'banUser':
				$this->banUser($parameters);
				$response['result'] = 'OK';
				break;
			case 'reportSpam':
				$this->spamReport($parameters);
				$response['result'] = 'OK';
				break;
			case 'reactToMessage':
				$this->reactToMessage($parameters);
				$response['result'] = 'OK';
				break;
			case 'reactionsLog':
				$response = array_merge($response, $this->reactionsLog($parameters));
				break;
			case 'logOff':
				$this->logOff($parameters);
				$response['result'] = 'OK';
				break;
			case 'createChannel':
				$channel = $this->channelsService->createChannel($parameters);
				$response['channel'] = $this->clientSide->channelToPlain($channel);
				$response['result'] = 'OK';
				break;
			case 'saveChannel':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['id']);
				$this->channelsService->ensureEditAccess($channel);
				$channel = $this->channelsService->saveChannel($channel, $parameters);
				$response['channel'] = $this->clientSide->channelToPlain($channel);
				$response['result'] = 'OK';
				break;
			case 'getChannelMembers':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['id']);
				$this->channelsService->ensureEditAccess($channel);
				$response['members'] = $this->clientSide->channelMembersToPlain($this->channelsService->getChannelMembers($channel));
				$response['result'] = 'OK';
				break;
			case 'deleteChannelMember':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureEditAccess($channel);
				$memberId = (int) Crypt::decryptFromString($parameters['memberId']);
				if ($memberId === $this->authentication->getUserIdOrNull()) {
					throw new \Exception(__('Channel administrator cannot remove itself from the channel', 'wise-chat'));
				}
				$this->channelsService->deleteChannelMember($channel, $memberId);
				$response['channel'] = $this->clientSide->channelToPlain($channel);
				$response['result'] = 'OK';
				break;
			case 'deleteChannel':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureEditAccess($channel);
				$this->channelsService->deleteChannel($channel);
				$response['channelId'] = $parameters['channelId'];
				$response['result'] = 'OK';
				break;
			case 'sendChannelNotifications':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureEditAccess($channel);
				$this->channelsService->sendChannelNotifications($channel, $parameters['users']);
				$response['channelId'] = $parameters['channelId'];
				$response['result'] = 'OK';
				break;
			case 'addChannelMember':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureEditAccess($channel);
				if (isset($parameters['wpUserId'])) {
					$userId = (int)Crypt::decryptFromString($parameters['wpUserId']);
					$this->channelsService->addChannelMemberOfWPUser($channel, $userId);
				} else {
					$userId = (int)Crypt::decryptFromString($parameters['userId']);
					$this->channelsService->addChannelMember($channel, $userId);
				}
				$response['channel'] = $this->clientSide->channelToPlain($channel);
				$response['result'] = 'OK';
				break;
			case 'confirmChannelMembership':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->confirmChannelMembership($channel);
				$response['channel'] = $this->clientSide->channelToPlain($channel);
				$response['result'] = 'OK';
				break;
			case 'inviteChannelMember':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureEditAccess($channel);
				$wpUserId = (int)Crypt::decryptFromString($parameters['wpUserId']);
				$this->channelsService->addChannelMemberOfWPUser($channel, $wpUserId, false);
				$response['channel'] = $this->clientSide->channelToPlain($channel);
				$response['result'] = 'OK';
				break;
			case 'markFeedEntryAsSeen':
				$userFeedEntryId = (int) Crypt::decryptFromString($parameters['id']); // actually WP user ID
				$this->userFeedService->markAsSeen($userFeedEntryId, $this->authentication->getUser()->getId());
				$response['result'] = 'OK';
				break;
			case 'searchUsers':
				$users = $this->usersDAO->getWPUsers(array('search' => $parameters['search'], 'number' => 20));
				$response['users'] = $this->clientSide->wpUsersToPlain($users);
				break;

			case 'requestChannelMembership':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->requestChannelMembership($channel);

				break;
			case 'removeUserChannel':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureReadAccess($channel);
				$this->channelsService->removeUserChannel($channel);
				break;
			case 'quitMembership':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureReadAccess($channel);
				$this->channelsService->quitChannelMembership($channel);
				break;
			case 'openChannel':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureOpeningAccess($channel);
				$response = array_merge($response, $this->openChannel($channel));
				break;
			case 'closeChannel':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->closeChannel($channel);
				$response['channel'] = $this->clientSide->channelToPlain($channel);
				break;
			case 'addUserChannel':
				$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
				$this->channelsService->ensureReadAccess($channel);
				$this->channelsService->addUserChannel($channel);
				$response['channel'] = $this->clientSide->channelToPlain($channel);
				break;
			case 'getFeed':
				$response['feed'] = $this->userFeedService->getLatest($this->authentication->getUser(), intval(Crypt::decryptFromString($parameters['lastId'])));
				$response['id'] = intval(Crypt::decryptFromString($parameters['lastId']));
				break;
			default:
				throw new \Exception('Invalid command');
		}

		return $response;
	}

	/**
	 * @param array $parameters
	 * @return Message
	 * @throws UnauthorizedAccessException
	 * @throws Exception
	 */
	public function saveMessage(array $parameters): Message {
		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());
		$this->channelsService->ensureWriteAccess($channel);

		// check permissions:
		$deniedEditing = true;
		if ($message->getUserId() > 0 && $message->getUserId() == $this->authentication->getUserIdOrNull()) {
			$deniedEditing = false;
		}
		if ($deniedEditing) {
			$this->checkUserRight('edit_message');
		}

		$this->messagesService->saveRawMessageContent($message, trim($parameters['content']));
		$this->actions->publishAction('refreshMessage', array('id' => $parameters['id']));

		return $message;
	}

	/**
	 * Endpoint that prepares an image for further upload:
	 * - basic checks
	 * - resizing
	 * - fixing orientation
	 *
	 * @notice GIFs are returned unchanged because of the lack of proper resizing abilities
	 *
	 * @return null
	 */
	public function prepareImageEndpoint() {
		$this->verifyCheckSum();

		try {
			$this->checkBanned();
			$this->checkChatOpen();
			$this->checkUserAuthentication();
			$this->checkUserAuthorization();
			$this->checkUserWriteAuthorization();

			$this->checkPostParams(array('data'));
			$data = $this->getPostParam('data');

			/** @var ImagesService $imagesService */
			$imagesService = Container::getInstance()->get(ImagesService::class);
			$decodedImageData = $imagesService->decodePrefixedBase64ImageData($data);
			if ($decodedImageData['mimeType'] == 'image/gif') {
				echo $data;
			} else {
				$preparedImageData = $imagesService->getPreparedImage($decodedImageData['data']);
				echo $imagesService->encodeBase64WithPrefix($preparedImageData, $decodedImageData['mimeType']);
			}
		} catch (UnauthorizedAccessException $exception) {
			echo json_encode(array('error' => $exception->getMessage()));
			$this->sendUnauthorizedStatus();
		} catch (Exception $exception) {
			echo json_encode(array('error' => $exception->getMessage()));
			$this->sendBadRequestStatus();
		}

		die();
	}

	/**
	 * @param array $parameters
	 * @throws UnauthorizedAccessException
	 * @throws Exception
	 */
	private function deleteMessage($parameters) {
		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);

		$this->checkUserRight('delete_message');

		$channel = $this->channelsDAO->get($message->getChannelId());
		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$this->messagesService->deleteById($message->getId());

		$this->actions->publishAction('deleteMessage', array('id' => $parameters['id']));
	}

	/**
	 * @param array $parameters
	 * @throws UnauthorizedAccessException
	 * @throws Exception
	 */
	private function muteUser(array $parameters) {
		$this->checkUserRight('mute_user');

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$this->userMutesService->muteUserByMessage($message);
	}

	/**
	 * @param array $parameters
	 * @throws UnauthorizedAccessException
	 * @throws Exception
	 */
	private function unMuteUser(array $parameters) {
		$this->checkUserRight('mute_user');

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$this->userMutesService->unMuteUserByMessage($message);
	}

	/**
	 * @param array $parameters
	 * @throws UnauthorizedAccessException
	 * @throws Exception
	 */
	private function banUser($parameters) {
		$this->checkUserRight('ban_user');

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);
		$this->bansService->banByMessageId($message->getId());
	}

	/**
	 * @param array $parameters
	 * @throws UnauthorizedAccessException
	 * @throws Exception
	 */
	public function spamReport($parameters) {
		if (!$this->options->isOptionEnabled('spam_report_enable_all', true)) {
			$this->checkUserRight('spam_report');
		}

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());
		$url = trim($parameters['url']);

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);
		$this->messagesService->reportSpam($channel->getId(), $message->getId(), $url);
	}

	/**
	 * @param $parameters
	 * @return mixed
	 * @throws Exception
	 */
	private function handleSetUserPropertyCommands($parameters) {
		$response = null;
		$property = $parameters['property'];
		$value = $parameters['value'];

		switch ($property) {
			case 'name':
				$userNameLengthLimit = $this->options->getIntegerOption('user_name_length_limit', 25);
				if ($userNameLengthLimit > 0) {
					$value = substr($value, 0, $userNameLengthLimit);
				}
				$response = $this->userService->changeUserName($value);
				break;
			case 'textColor':
				$this->userService->setUserTextColor($value);
				break;
			case 'emailNotifications':
				$this->userService->setProperty('disableNotifications', $value === 'false');
				$user = $this->authentication->getUser();
				$response = $user->getData();
				break;
			default:
				$this->userSettingsDAO->setSetting($property, $value, $this->authentication->getUser());
		}

		return $response;
	}

	private function reactionsLog($parameters) {
		$message = $this->clientSide->getMessageOrThrowException($parameters['messageId']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		return ['log' => $this->messageReactionsService->getReactionsLog($message)];
	}

	private function reactToMessage($parameters) {
		//$this->checkUserRight('react_to_message'); TODO

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$this->messageReactionsService->toggleReaction($message, intval($parameters['reactionId']));
		$this->actions->publishAction('refreshMessageReactionsCounters', array(
			'id' => $parameters['id'],
			'channel' => array('id' => $parameters['channel']['id']),
			'reactions' => $this->messageReactionsService->getReactionsAsPlainArray($message, true, false)
		));
	}

	private function logOff($parameters) {
		$this->authentication->dropAuthentication();
	}

	private function openChannel(Channel $channel): array {
		if (!$this->channelsService->openChannel($channel)) {
			return [];
		}

		if ($this->options->isOptionNotEmpty('auto_open')) {
			$this->messagesService->addWelcomeMessage($channel);
		}

		// refresh messages only if the channel was in fact opened:
		$messages = $this->messagesService->getPortionByChannelId($channel->getId());
		$clientChannels = $this->clientSide->convertToClientChannels([$channel]);
		$this->messageReactionsService->cacheReactions($messages);
		$this->userService->cacheUsersOfMessages($messages);

		$messagesOut = [];
		foreach ($messages as $message) {
			$clientChannel = $clientChannels[$message->getChannelId()];
			$messagesOut[] = $this->clientSide->toPlainMessage($message, $clientChannel);
		}

		return ['messages' => $messagesOut, 'channel' => $this->clientSide->clientChannelToPlain(reset($clientChannels))];
	}

}