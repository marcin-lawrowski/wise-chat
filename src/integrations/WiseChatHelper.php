<?php

namespace Kainex\WiseChat\Integrations;

use Kainex\WiseChat\Container;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\Services\ClientSide\ClientSide;

/**
 * WiseChat integrations helper class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatHelper {

	/**
	 * TODO: FIX!
	 *
	 * @param integer $wordPressUserId
	 * @return string
	 */
	public static function getDirectChannelId($wordPressUserId) {
		/** @var UsersDAO $usersDAO */
		$usersDAO = Container::getInstance()->get(UsersDAO::class);

		/** @var ClientSide $clientSide */
		$clientSide = Container::getInstance()->get(ClientSide::class);


		$id = $usersDAO->getLatestByWordPressId($wordPressUserId);

		return $clientSide->encryptDirectChannelId($id ? $id->getId() : 'v'.$wordPressUserId);
	}

}