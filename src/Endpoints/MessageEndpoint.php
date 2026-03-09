<?php

namespace Kainex\WiseChat\Endpoints;

use Exception;
use Kainex\WiseChat\Container;
use Kainex\WiseChat\Exceptions\UnauthorizedAccessException;

/**
 * Wise Chat message actions endpoint class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MessageEndpoint extends WiseChatEndpoint {

	/**
	 * New message endpoint.
	 */
	public function messageEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$encryptedChannelId = trim($this->getPostParam('channelId'));
		$message = trim($this->getPostParam('message'));
		$attachments = $this->getPostParam('attachments');
		$replyToMessageId = $this->getPostParam('replyToMessageId');
		if (!is_array($attachments)) {
			$attachments = array();
		}

		$response = array();
		try {
			$this->checkBanned();
			$this->checkUserAuthentication();
			$this->checkUserAuthorization();
			$this->checkUserWriteAuthorization();
			$this->checkChatOpen();

			$channel = $this->clientSide->getChannelFromEncryptedId($encryptedChannelId);
			$effectiveChannelId = $this->clientSide->encryptChannelId($channel);
			$this->channelsService->ensureWriteAccess($channel);

			if (!$message && count($attachments) === 0) {
				throw new Exception('Missing required fields');
			}

			$user = $this->authentication->getUser();

			$replyToMessage = $replyToMessageId ? $this->clientSide->getMessageOrThrowException($replyToMessageId) : null;
			$addedMessage = $this->messagesService->addMessage($user, $channel, $message, $attachments, false, null, $replyToMessage);

			if ($addedMessage !== null && $this->channelsService->isDirect($channel)) {
				$this->userService->setInactiveUsersOfflineStatus();

				foreach ($this->channelsService->getChannelMembers($channel) as $member) {
					if ($member->getUserId() !== $this->authentication->getUserIdOrNull() && !$this->channelUsersDAO->isOnline($member->getUserId())) {

						// TODO: possibly move to addMessage() - to make it working in other places
						$this->pendingChatsService->addPendingChat($member->getUserId(), $addedMessage, $channel);
					}
				}
			}

			if ($addedMessage !== null) {
				$response['message'] = array(
					'text' => $addedMessage->getText(),
					'hidden' => $addedMessage->isHidden()
				);
			}

			// in case the ID of the channel changed:
			if ($effectiveChannelId !== $encryptedChannelId) {
				$response['channelMapping'] = [ 'from' => $encryptedChannelId, 'to' => $effectiveChannelId ];
			}

			$response['result'] = 'OK';
		} catch (UnauthorizedAccessException $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendUnauthorizedStatus();
		} catch (Exception $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendBadRequestStatus();
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Returns a message by given ID.
	 */
	public function getMessageEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->confirmUserAuthenticationOrEndRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkGetParams(['id']);
			$message = $this->clientSide->getMessageOrThrowException($this->getGetParam('id'));
			$channel = $this->channelsDAO->get($message->getChannelId());
			$this->channelsService->ensureReadAccess($channel);

			$this->checkBanned();
			$this->checkUserAuthorization();
			$this->checkChatOpen();

			$response['result'] = array();
			$response['nowTime'] = gmdate('c', time());
			$messages = [$message];
			$clientChannels = $this->clientSide->convertToClientChannels([$channel]);
			$this->messageReactionsService->cacheReactions($messages);
			$this->userService->cacheUsersOfMessages($messages);

			$response['result'][] = $this->clientSide->toPlainMessage($message, $clientChannels[$channel->getId()], ['live' => false]);
		} catch (UnauthorizedAccessException $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendUnauthorizedStatus();
		} catch (Exception $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendBadRequestStatus();
		}

		echo json_encode($response);
		die();
	}

}