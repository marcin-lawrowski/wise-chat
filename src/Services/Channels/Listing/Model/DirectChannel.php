<?php

namespace Kainex\WiseChat\Services\Channels\Listing\Model;

use Kainex\WiseChat\Model\User;

/**
 * Helper class for direct channels.
 */
class DirectChannel {

	private User $user;
	private bool $online;

	public function getUser(): User {
		return $this->user;
	}

	public function setUser(User $user): void {
		$this->user = $user;
	}

	public function isOnline(): bool {
		return $this->online;
	}

	public function setOnline(bool $online): void {
		$this->online = $online;
	}

}