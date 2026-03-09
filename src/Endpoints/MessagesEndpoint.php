<?php

namespace Kainex\WiseChat\Endpoints;

use Exception;
use Kainex\WiseChat\Exceptions\UnauthorizedAccessException;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Crypt;

/**
 * Wise Chat messages endpoint class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MessagesEndpoint extends WiseChatEndpoint {

	/**
	 * Returns messages to render in the chat window.
	 */
	public function messagesEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->confirmUserAuthenticationOrEndRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkGetParams(array('lastId', 'fromActionId'));
			$encryptedLastId = $this->getGetParam('lastId', '0');
			$lastId = intval(Crypt::decryptFromString($encryptedLastId));
			$initRequest = $this->getGetParam('init') === '1';

			$channels = $this->channelsSourcesService->getMonitoredChannels();

			$this->checkBanned();
			$this->checkUserAuthorization();
			$this->checkChatOpen();

			$this->messagesService->frequentMaintenance();

			$response['init'] = $initRequest;
			$response['nowTime'] = gmdate('c', time());
			$response['result'] = array();
			$response['channels'] = array_map(function($c) { return '#'.$c->getId().' '.$c->getName(); }, $channels);

			$messages = [];
			if ($initRequest) {
				foreach ($channels as $channel) {
					$messages = array_merge($this->messagesService->getPortionByChannelId($channel->getId()), $messages);
				}

				// sort by ID:
				usort($messages, function($a, $b) {
					return $a->getId() > $b->getId() ? 1 : -1;
				});
			} else if (!empty($channels)) {
				$messages = $this->messagesService->getAllByChannelsAndOffset($channels, $encryptedLastId !== '0' ? $lastId : null);
			}

			$clientChannels = $this->clientSide->convertToClientChannels($channels);
			$this->messageReactionsService->cacheReactions($messages);
			$this->userService->cacheUsersOfMessages($messages);

			/** @var Message $message */
			foreach ($messages as $message) {
				if (!isset($clientChannels[$message->getChannelId()])) {
					continue; // TODO: exclude broken direct channels in getOpenedChannels
				}
				$response['result'][] = $this->clientSide->toPlainMessage($message, $clientChannels[$message->getChannelId()]);
			}

			// load actions:
			$fromActionId = intval($this->getGetParam('fromActionId', 0));
			$response['actions'] = $fromActionId > 0 ? $this->actions->getJSONReadyActions($fromActionId, $this->authentication->getUser()) : array();
			$response['lastActionId'] = $this->actions->getLastActionId();

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
	 * Loads past messages in the given channel. Without beforeMessage parameter it loads last messages.
	 */
	public function pastMessagesEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->confirmUserAuthenticationOrEndRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkGetParams(['channelId']);
			$encryptedBeforeMessage = $this->getGetParam('beforeMessage', '');
			$channelId = $this->getGetParam('channelId');
			$channel = $this->clientSide->getChannelOfEncryptedID($channelId);
			$this->channelsService->ensureReadAccess($channel);

			$this->checkBanned();
			$this->checkUserAuthorization();
			$this->checkChatOpen();

			$message = $encryptedBeforeMessage ? $this->clientSide->getMessageOrThrowException($encryptedBeforeMessage) : null;

			$response['result'] = array();
			$messages = $this->messagesService->getMessagesOfChannel($channel, $message);

			$clientChannels = $this->clientSide->convertToClientChannels([$channel]);
			$this->messageReactionsService->cacheReactions($messages);
			$this->userService->cacheUsersOfMessages($messages);

			foreach ($messages as $message) {
				$response['result'][] = $this->clientSide->toPlainMessage($message, $clientChannels[$channel->getId()], ['live' => false]);
			}

			shuffle($response['result']);

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