<?php

WiseChatContainer::load('endpoints/WiseChatEndpoint');

/**
 * Wise Chat message actions endpoint class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMessageEndpoint extends WiseChatEndpoint {

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
		if (!is_array($attachments)) {
			$attachments = array();
		}

		$response = array();
		try {
			$this->checkIpNotKicked();
			$this->checkUserAuthentication();
			$this->checkUserAuthorization();
			$this->checkUserWriteAuthorization();
			$this->checkChatOpen();

			$channel = $this->getChannelFromEncryptedId($encryptedChannelId);
			$this->checkChannel($channel);
			$this->checkChannelAuthorization($channel);

			if (strlen($message) === 0 && count($attachments) === 0) {
				throw new Exception('Missing required fields');
			}

			$user = $this->authentication->getUser();

			/** @var WiseChatCommandsResolver $wiseChatCommandsResolver */
			$wiseChatCommandsResolver = WiseChatContainer::get('commands/WiseChatCommandsResolver');

			// resolve a command if it is recognized:
			$isCommandResolved = $wiseChatCommandsResolver->resolve(
				$user, $this->authentication->getSystemUser(), $channel, $message
			);

			// add a regular message:
			if (!$isCommandResolved) {
				$addedMessage = $this->messagesService->addMessage($user, $channel, $message, $attachments, false);

				if ($addedMessage !== null) {
					$response['message'] = array(
						'text' => $addedMessage->getText(),
						'hidden' => false
					);
				}
			}

			$response['result'] = 'OK';
		} catch (WiseChatUnauthorizedAccessException $exception) {
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
			$this->checkGetParams(array('id'));
			$message = $this->clientSide->getMessageOrThrowException($this->getGetParam('id'));
			$channel = $this->channelsDAO->getByName($message->getChannelName());

			$this->checkIpNotKicked();
			$this->checkUserAuthorization();
			$this->checkChatOpen();
			$this->checkChannel($channel);
			$this->checkChannelAuthorization($channel);

			$response['result'] = array();
			$response['nowTime'] = gmdate('c', time());
			$userId = $this->authentication->getUserIdOrNull();

			// get the message:
			$messages = array($message);

			// generate public IDs:
			$channelEncryptedIds = array(
				$channel->getName() => WiseChatCrypt::encryptToString('c|'.$channel->getId())
			);

			foreach ($messages as $message) {
				// omit non-admin messages:
				if ($message->isAdmin() && !$this->usersDAO->isWpUserAdminLogged()) {
					continue;
				}

				// omit not-related private messages:
				if ($message->getRecipientId() > 0 && $message->getRecipientId() != $userId && $message->getUserId() != $userId) {
					continue;
				}

				$channelId = $channelEncryptedIds[$message->getChannelName()];

				$response['result'][] = $this->toPlainMessage($message, $channelId);
			}
		} catch (WiseChatUnauthorizedAccessException $exception) {
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