<?php

/**
 * WiseChat integrations helper class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatHelper {

	/**
	 * @param integer $wordPressUserId
	 * @return string
	 */
	public static function getDirectChannelId($wordPressUserId) {
		/** @var WiseChatUsersDAO $usersDAO */
		$usersDAO = WiseChatContainer::getLazy('dao/user/WiseChatUsersDAO');

		/** @var WiseChatClientSide $clientSide */
		$clientSide = WiseChatContainer::getLazy('services/client-side/WiseChatClientSide');


		$id = $usersDAO->getLatestByWordPressId($wordPressUserId);

		return $clientSide->encryptDirectChannelId($id ? $id->getId() : 'v'.$wordPressUserId);
	}

}