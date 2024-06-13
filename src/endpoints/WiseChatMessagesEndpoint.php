<?php

WiseChatContainer::load('endpoints/WiseChatEndpoint');

/**
 * Wise Chat messages endpoint class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMessagesEndpoint extends WiseChatEndpoint {

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
			$this->checkGetParams(array('lastId', 'channelIds', 'fromActionId'));
			$encryptedLastId = $this->getGetParam('lastId', '0');
			$lastId = intval(WiseChatCrypt::decryptFromString($encryptedLastId));
			$initRequest = $this->getGetParam('init') === '1';
			$directEnabled = $this->options->isOptionEnabled('enable_private_messages');

			$channelIds = array_map('intval', array_filter($this->getGetParam('channelIds')));
			$channels = $this->channelsService->getChannelsByIds($channelIds);

			$this->checkIpNotKicked();
			$this->checkUserAuthorization();
			$this->checkChatOpen();
			$channels = array_filter($channels, function($channel) { return $this->authorization->isUserAuthorizedForChannel($channel); } );

			$response['init'] = $initRequest;
			$response['nowTime'] = gmdate('c', time());
			$response['result'] = array();

			// get and render messages:
			if ($initRequest) {
				// read the past direct messages:
				$messages = $directEnabled
					? $this->messagesService->getAllPrivateByChannelNameAndUser(WiseChatChannelsService::PRIVATE_MESSAGES_CHANNEL, $this->authentication->getUser()->getId())
					: array();

				// read the past channel messages:
				if ($this->hasPublicChannelsAccess()) {
					foreach ($channels as $channel) {
						$messages = array_merge(
							$this->messagesService->getAllPublicByChannelNameAndUser($channel->getName()),
							$messages
						);
					}
				}

				// sort by ID:
				usort($messages, function($a, $b) {
					return $a->getId() > $b->getId() ? 1 : -1;
				});
			} else {
				// read current messages:
				$channelNames = array_map(function($channel) { return $channel->getName(); }, $channels);

				// enable direct channel if enabled:
				$privateMessagesSenderOrRecipientId = null;
				if ($directEnabled) {
					$channelNames[] = WiseChatChannelsService::PRIVATE_MESSAGES_CHANNEL;
					$privateMessagesSenderOrRecipientId = $this->authentication->getUserIdOrNull();
				}

				$messages = $this->messagesService->getAllByChannelNamesAndOffset($channelNames, $encryptedLastId !== '0' ? $lastId : null, $privateMessagesSenderOrRecipientId);
			}

			// generate public IDs:
			$channelEncryptedIds = array();
			foreach ($channels as $channel) {
				$channelEncryptedIds[$channel->getName()] = WiseChatCrypt::encryptToString('c|'.$channel->getId());
			}

			foreach ($messages as $message) {
				// omit non-admin messages:
				if ($message->isAdmin() && !$this->usersDAO->isWpUserAdminLogged()) {
					continue;
				}

				$attributes = array(
					'live' => !$initRequest
				);

				if ($message->getRecipientId() > 0) {
					$directUserId = $this->authentication->getUserIdOrNull() === $message->getRecipientId() ? $message->getUserId() : $message->getRecipientId();
					$channelId = WiseChatCrypt::encryptToString('d|'.$directUserId);
				} else if (!$this->hasPublicChannelsAccess()) {
					continue;
				} else {
					$channelId = $channelEncryptedIds[$message->getChannelName()];
				}

				$response['result'][] = $this->toPlainMessage($message, $channelId, $attributes);
			}

			// load actions:
			$fromActionId = intval($this->getGetParam('fromActionId', 0));
			$response['actions'] = $fromActionId > 0 ? $this->actions->getJSONReadyActions($fromActionId, $this->authentication->getUser()) : array();
			$response['lastActionId'] = $this->actions->getLastActionId();

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
	 * Loads past messages in the given channel. Without beforeMessage parameter it loads last messages.
	 */
	public function pastMessagesEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->confirmUserAuthenticationOrEndRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkGetParams(array('channelId'));
			$encryptedBeforeMessage = $this->getGetParam('beforeMessage', '');
			$channelId = $this->getGetParam('channelId');

			$this->checkIpNotKicked();
			$this->checkUserAuthorization();
			$this->checkChatOpen();

			$response['result'] = array();
			$messages = $this->messagesService->getMessagesOfChannel($channelId, $encryptedBeforeMessage);
			foreach ($messages as $message) {
				$response['result'][] = $this->toPlainMessage($message, $channelId, array( 'live' => false ));
			}

			shuffle($response['result']);

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