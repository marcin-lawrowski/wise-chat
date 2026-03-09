<?php

namespace Kainex\WiseChat\Services;

use Exception;
use Kainex\WiseChat\DAO\User\UsersDAO;
use Kainex\WiseChat\DAO\UserMutesDAO;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\UserMute;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Options;

/**
 * @author Kainex <contact@kaine.pl>
 */
class UserMutesService extends UserMutesDAO {

	private UsersDAO $usersDAO;
	private Options $options;

	/**
	 * @param UsersDAO $usersDAO
	 * @param Options $options
	 */
	public function __construct(UsersDAO $usersDAO, Options $options) {
		$this->usersDAO = $usersDAO;
		$this->options = $options;
	}

	/**
	* Maintenance actions performed periodically.
	*/
	public function periodicMaintenance() {
		$this->deleteOlder(time());
	}

	/**
	 * Mutes user by message.
	 *
	 * @param Message $message
	 * @throws Exception
	 */
	public function muteUserByMessage(Message $message) {
		$user = $this->usersDAO->get($message->getUserId());
		if ($user !== null) {
			$this->muteUser($user, $this->options->getIntegerOption('moderation_mute_user_duration', 1440));
		} else {
			throw new \Exception('No associated user found');
		}
	}

	/**
	 * Unmutes user by message.
	 *
	 * @param Message $message
	 * @throws Exception
	 */
	public function unMuteUserByMessage(Message $message) {
		$user = $this->usersDAO->get($message->getUserId());
		if ($user !== null) {
			$this->unMuteUser($user);
		} else {
			throw new \Exception('No associated user found');
		}
	}

	/**
	 * @param string $ip
	 * @param integer $duration Duration of the ban (in seconds)
	 *
	 * @return boolean Returns true the ban was created
	 * @throws Exception
	 */
	public function muteIP(string $ip, int $duration): bool {
		if ($this->getByIp($ip) === null) {
			$userMute = new UserMute();
			$userMute->setCreated(time());
			$userMute->setTime(time() + $duration);
			$userMute->setIp($ip);
			$this->save($userMute);

			/**
			 * Fires once IP address has been muted.
			 *
			 * @since 3.6.1
			 *
			 * @param string $ip IP address
			 * @param integer $duration Duration of the ban (in seconds)
			 */
			do_action("wc_ip_muted", $ip, $duration);

			return true;
		}

		return false;
	}

	/**
	 * @param User $user
	 * @param integer $duration Duration of the ban (in seconds)
	 *
	 * @return boolean Returns true the ban was created
	 * @throws Exception
	 */
	public function muteUser(User $user, int $duration): bool {
		if ($this->getByUserId($user->getId()) === null) {
			$userMute = new UserMute();
			$userMute->setCreated(time());
			$userMute->setTime(time() + $duration);
			$userMute->setIp($user->getIp());
			$userMute->setUserId($user->getId());
			$this->save($userMute);

			/**
			 * Fires once user has been muted.
			 *
			 * @param User $user Muted user
			 * @param integer $duration Duration of the ban (in seconds)
			 *@since 3.6.1
			 *
			 */
			do_action("wc_user_muted", $user, $duration);

			return true;
		}

		return false;
	}

    /**
     * @param User $user
     * @return bool
     */
    public function isUserMuted(User $user): bool {
		$mode = $this->options->getOption('mutes_mode', 'userId');

		if ($mode === 'userId') {
			$userMute = $this->getByUserId($user->getId());
		} else {
			if (empty($user->getIp())) {
				return false;
			}
			$userMute = $this->getByIp($user->getIp());
		}

	    return $userMute !== null;
    }

	/**
	 * Converts duration string into amount of seconds.
	 * If the value cannot be determined the default value is returned.
	 *
	 * @param string $durationString Eg. 1h, 2d, 7m
	 * @param integer $defaultValue One hour
	 *
	 * @return integer
	 */
	public function getDurationFromString(string $durationString, int $defaultValue = 3600): int {
		$duration = $defaultValue;

		if ($durationString) {
			if (preg_match('/\d+m/', $durationString)) {
				$duration = intval($durationString) * 60;
			}
			if (preg_match('/\d+h/', $durationString)) {
				$duration = intval($durationString) * 60 * 60;
			}
			if (preg_match('/\d+d/', $durationString)) {
				$duration = intval($durationString) * 60 * 60 * 24;
			}

			if ($duration === 0) {
				$duration = $defaultValue;
			}
		}

		return $duration;
	}

	/**
	 * Returns IDs of the given users who were muted.
	 *
	 * @param User[] $users
	 * @return integer[]
	 */
	public function getMutedUsersIDs(array $users): array {
		$mode = $this->options->getOption('mutes_mode', 'userId');
		$mutedUsersIDs = [];

		/** @var User[] $usersChunk */
		foreach (array_chunk($users, 200) as $usersChunk) {
			if ($mode === 'userId') {
				$ids = [];
				foreach ($usersChunk as $user) {
					$ids[] = $user->getId();
				}

				foreach ($this->getAllBy(['user_id' => [$ids, '%d']]) as $record) {
					$mutedUsersIDs[] = intval($record->user_id);
				}
			} else {
				$ips = [];
				foreach ($usersChunk as $user) {
					$ips[] = $user->getIp();
				}

				$mutedIPs = [];
				foreach ($this->getAllBy(['ip' => [$ips, '%s']]) as $record) {
					$mutedIPs[] = $record->ip;
				}

				foreach ($usersChunk as $user) {
					if (in_array($user->getIp(), $mutedIPs)) {
						$mutedUsersIDs[] = $user->getId();
					}
				}
			}
		}

		return $mutedUsersIDs;
	}

	private function unMuteUser(User $user) {
		$userMute = $this->getByUserId($user->getId());
		if ($userMute !== null) {
			$this->deleteById($userMute->getId());
		}
	}

}