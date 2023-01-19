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
			$this->checkGetParams(array('lastId', 'channelIds'));
			$encryptedLastId = $this->getGetParam('lastId', '0');
			$lastId = intval(WiseChatCrypt::decryptFromString($encryptedLastId));
			$initRequest = $this->getGetParam('init') === '1';

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
				$messages = array();

				// read the past channel messages:
				foreach ($channels as $channel) {
					$messages = array_merge($this->messagesService->getAllPublicByChannelNameAndUser($channel->getName()), $messages);
				}

				// sort by ID:
				usort($messages, function($a, $b) {
					return $a->getId() > $b->getId();
				});
			} else {
				// read current messages:
				$channelNames = array_map(function($channel) { return $channel->getName(); }, $channels);

				$messages = $this->messagesService->getAllByChannelNamesAndOffset($channelNames, $encryptedLastId !== '0' ? $lastId : null);
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

				$channelId = $channelEncryptedIds[$message->getChannelName()];

				$response['result'][] = $this->toPlainMessage($message, $channelId, $attributes);
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