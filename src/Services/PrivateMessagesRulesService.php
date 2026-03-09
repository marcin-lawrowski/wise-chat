<?php

namespace Kainex\WiseChat\Services;

use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Services\User\UserService;

class PrivateMessagesRulesService {

	private UserService $userService;

	/**
	 * @param UserService $userService
	 */
	public function __construct(UserService $userService) {
		$this->userService = $userService;
	}

	/**
	 * @param User|null $sender
	 * @param User|null $receiver
	 * @return boolean
	 */
	public function isMessageDeliveryAllowed(?User $sender, ?User $receiver): bool {
		if ($sender === null || $receiver === null) {
			return false;
		}

		if ($sender->getId() === $receiver->getId()) {
			return true;
		}

		$permitted = $this->isMessageDeliveryAllowedToUsers($sender, [$receiver]);

		return !empty($permitted) && $permitted[0] === $receiver->getId();
	}

	/**
	 * @param User $sender
	 * @param User[] $receiversToCheck
	 * @return int[] IDs of users available to send messages to
	 */
	public function isMessageDeliveryAllowedToUsers(User $sender, array $receiversToCheck): array {
		if (empty($receiversToCheck)) {
			return [];
		}

		return array_map(function(User $user) { return $user->getId(); }, $receiversToCheck);
	}

	/**
	 * Get roles applicable to permissions rules.
	 *
	 * @param User[] $users
	 * @return array Map from WP user ID to roles
	 */
	private function getPermissionsRoles(array $users): array {
		$map = [];
		$wpUsersRoles = [];
		foreach ($users as $user) {
			if ($user->getWordPressId()) {
				$wpUser = $this->userService->getWpUserByID($user->getWordPressId());
				if ($wpUser !== null) {
					$wpUsersRoles[$wpUser->ID] = $wpUser->roles ?? [];
				}
			}
		}

		foreach ($users as $user) {
			if (in_array($user->getExternalType(), ['fb', 'go', 'tw'])) {
				$roles = ['_' . $user->getExternalType()];
			} else if ($user->getWordPressId() > 0) {
				$roles = $wpUsersRoles[$user->getWordPressId()] ?? [];
			} else {
				$roles = ['_anonymous'];
			}

			$roles[] = '_any';

			$map[$user->getId()] = $roles;
		}

		return $map;
	}


}