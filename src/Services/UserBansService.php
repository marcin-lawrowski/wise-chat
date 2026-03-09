<?php

namespace Kainex\WiseChat\Services;

use Exception;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\UserBansDAO;
use Kainex\WiseChat\DAO\MessagesDAO;
use Kainex\WiseChat\Model\UserBan;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Services\User\ActionsService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Options;

/**
 * WiseChat bans services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserBansService extends UserBansDAO {

	private ActionsService $actions;
	private UsersDAO $usersDAO;
	private MessagesDAO $messagesDAO;
	private AuthenticationService $authentication;
	private Options $options;

	/**
	 * @param ActionsService $actions
	 * @param UsersDAO $usersDAO
	 * @param MessagesDAO $messagesDAO
	 * @param AuthenticationService $authentication
	 * @param Options $options
	 */
	public function __construct(ActionsService $actions, UsersDAO $usersDAO, MessagesDAO $messagesDAO, AuthenticationService $authentication, Options $options) {
		$this->actions = $actions;
		$this->usersDAO = $usersDAO;
		$this->messagesDAO = $messagesDAO;
		$this->authentication = $authentication;
		$this->options = $options;
	}

	/**
	 * Bans the user by message ID.
	 *
	 * @param integer $messageId
	 *
	 * @throws Exception If the message or user was not found
	 */
	public function banByMessageId($messageId) {
		$message = $this->messagesDAO->get($messageId);
		if ($message === null) {
			throw new Exception('Message was not found');
		}

		$mode = $this->options->getOption('bans_mode', 'userId');
		$user = $this->usersDAO->get($message->getUserId());
		if ($user !== null) {
			if ($mode === 'userId') {
				$this->banUser($user);
			} else {
				$this->banIP($user->getIp());
			}

			$this->actions->publishAction('reload', array(), $user->getId());

			return;
		}

		throw new Exception('User was not found');
	}


	/**
	 * Creates and saves a new ban on IP address if the IP was not banned previously.
	 *
	 * @param string $ip Given IP address
	 *
	 * @return boolean Returns true the ban was created
	 * @throws Exception
	 */
	public function banIP($ip) {
		if ($this->getByIp($ip) === null) {
			$userBan = new UserBan();
			$userBan->setCreated(time());
			$userBan->setIp($ip);
			$this->save($userBan);

			/**
			 * Fires once IP address has been banned.
			 *
			 * @since 2.3.2
			 *
			 * @param string $ip Banned IP address
			 */
			do_action("wc_ip_banned", $ip);

			return true;
		}

		return false;
	}

	/**
	 * Creates and saves a ban.
	 *
	 * @param User $user
	 *
	 * @return boolean Returns true the ban was created
	 * @throws Exception
	 */
	public function banUser($user) {
		if ($this->getByUserId($user->getId()) === null) {
			$userBan = new UserBan();
			$userBan->setCreated(time());
			$userBan->setIp($user->getIp());
			$userBan->setUserId($user->getId());
			$this->save($userBan);

			/**
			 * Fires once user has been banned.
			 *
			 * @since 2.3.2
			 *
			 * @param integer $userId Banned user
			 */
			do_action("wc_user_banned", $user->getId());

			return true;
		}

		return false;
	}

	/**
	 * Checks if user is banned,
	 *
	 * @return bool
	 */
	public function isBanned(): bool {
		$ip = '';
		if (is_array($_SERVER) && array_key_exists('SERVER_ADDR', $_SERVER)) {
			$ip = $_SERVER['SERVER_ADDR'];
		}
		if (is_array($_SERVER) && array_key_exists('LOCAL_ADDR', $_SERVER)) {
			$ip = $_SERVER['LOCAL_ADDR'];
		}

		$mode = $this->options->getOption('bans_mode', 'userId');
		if ($mode === 'userId') {
			if ($this->authentication->isAuthenticated()) {
				return $this->getByUserId($this->authentication->getUserIdOrNull()) !== null;
			} else {
				return $this->getByIp($ip) !== null;
			}
		} else {
			return $this->getByIp($ip) !== null;
		}
	}

}
