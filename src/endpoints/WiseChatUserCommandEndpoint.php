<?php

WiseChatContainer::load('endpoints/WiseChatEndpoint');

/**
 * Wise Chat user commands endpoint class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatUserCommandEndpoint extends WiseChatEndpoint {

	/**
	 * User commands endpoint.
	 */
	public function userCommandEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkIpNotKicked();
			$this->checkChatOpen();
			$this->checkUserAuthentication();
			$this->checkUserAuthorization();
			$this->checkUserWriteAuthorization();
			$this->checkPostParams(array('command', 'parameters'));

			$command = $this->getPostParam('command');
			$parameters = $this->getPostParam('parameters');
			switch ($command) {
				case 'setUserProperty':
					$response['value'] = $this->handleSetUserPropertyCommands($parameters);
					break;
				case 'deleteMessage':
					$this->deleteMessage($parameters);
					$response['result'] = 'OK';
					break;
				case 'muteUser':
					$this->muteUser($parameters);
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
				default:
					throw new \Exception('Invalid command');
			}

			$response['parameters'] = $parameters;
			$response['command'] = $command;
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
			$this->checkIpNotKicked();
			$this->checkChatOpen();
			$this->checkUserAuthentication();
			$this->checkUserAuthorization();
			$this->checkUserWriteAuthorization();

			$this->checkPostParams(array('data'));
			$data = $this->getPostParam('data');

			/** @var WiseChatImagesService $imagesService */
			$imagesService = WiseChatContainer::get('services/WiseChatImagesService');
			$decodedImageData = $imagesService->decodePrefixedBase64ImageData($data);
			if ($decodedImageData['mimeType'] == 'image/gif') {
				echo $data;
			} else {
				$preparedImageData = $imagesService->getPreparedImage($decodedImageData['data']);
				echo $imagesService->encodeBase64WithPrefix($preparedImageData, $decodedImageData['mimeType']);
			}
		} catch (WiseChatUnauthorizedAccessException $exception) {
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
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	private function deleteMessage($parameters) {
		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);

		$canDeleteOwnMessages = $this->options->isOptionEnabled('enable_delete_own_messages', false) && $message->getUserId() === $this->authentication->getUserIdOrNull();
		if (!$canDeleteOwnMessages) {
			$this->checkUserRight('delete_message');
		}

		$channel = $this->channelsDAO->getByName($message->getChannelName());
		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$this->messagesService->deleteById($message->getId());
		$this->actions->publishAction('deleteMessage', array('id' => $parameters['id'], 'channel' => $parameters['channel']));
	}

	/**
	 * @param array $parameters
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	private function muteUser($parameters) {
		$this->checkUserRight('ban_user');

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->getByName($message->getChannelName());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$duration = $this->options->getIntegerOption('moderation_ban_duration', 1440);
		$this->bansService->banByMessageId($message->getId(), $duration.'m');
	}

	/**
	 * @param array $parameters
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	private function banUser($parameters) {
		$this->checkUserRight('kick_user');

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->getByName($message->getChannelName());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);
		$this->kicksService->kickByMessageId($message->getId());
	}

	/**
	 * @param array $parameters
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	public function spamReport($parameters) {
		if (!$this->options->isOptionEnabled('spam_report_enable_all', true)) {
			$this->checkUserRight('spam_report');
		}

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->getByName($message->getChannelName());
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
			default:
				$this->userSettingsDAO->setSetting($property, $value, $this->authentication->getUser());
		}

		return $response;
	}

}