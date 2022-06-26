<?php

/**
 * WiseChat client side utilities.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatClientSide {

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/**
	 * @var WiseChatMessagesService
	 */
	private $messagesService;

	public function __construct() {
		WiseChatContainer::load('WiseChatCrypt');

		$this->messagesService = WiseChatContainer::getLazy('services/WiseChatMessagesService');
		$this->options = WiseChatOptions::getInstance();
	}

	/**
	 * @param $user
	 * @return string
	 */
	public function getUserCacheId($user) {
		return $this->getInstanceId().'_'.WiseChatCrypt::encryptToString($user->getId());
	}

	/**
	 * Get chat's instance ID.
	 *
	 * @return string
	 */
	public function getInstanceId() {
		return sha1(serialize($this->options->getOption('channel')));
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptUserId($id) {
		return WiseChatCrypt::encryptToString($id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptDirectChannelId($id) {
		return WiseChatCrypt::encryptToString('d|'.$id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptMessageId($id) {
		return WiseChatCrypt::encryptToString($id);
	}

	/**
	 * @param integer[] $ids
	 * @return string[]
	 */
	public function encryptMessageIds($ids) {
		return array_map(function($id) {
			return WiseChatCrypt::encryptToString($id);
		}, $ids);
	}

	/**
	 * @param string $encryptedId
	 * @return integer
	 */
	public function decryptMessageId($encryptedId) {
		return intval(WiseChatCrypt::decryptFromString($encryptedId));
	}

	/**
	 * Decrypts the message ID and loads the message.
	 *
	 * @param string $encryptedMessageId
	 * @return WiseChatMessage
	 * @throws Exception If the message does not exist
	 */
	public function getMessageOrThrowException($encryptedMessageId) {
		$message = $this->messagesService->getById($this->decryptMessageId($encryptedMessageId));
		if ($message === null) {
			throw new \Exception('The message does not exist');
		}

		return $message;
	}

}