<?php

namespace Kainex\WiseChat\Admin;

use Kainex\WiseChat\Settings;

/**
 * Wise Chat admin bans settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class BansTab extends AbstractTab {
	public function getFields() {
		return array(
			array('_section', 'Banned Users', 'Users banned from using the chat.'),
			array('bans_mode', 'Mode', 'radioCallback', 'string', '', self::getBansModes()),
			array('bans', 'Banned IPs', 'bansCallback', 'void'),
			array('ban_add', 'Ban IP', 'banAddCallback', 'void'),
		);
	}
	public function getDefaultValues() {
		return array(
			'bans_mode' => 'userId',
			'bans' => null,
			'ban_add' => null,
		);
	}

	private static function getBansModes() {
		return array(
			'userId' => array('User ID', 'Bans users by their IDs. This works fine for logged in users, but anonymous users may clear cookies and create a new user ID bypassing the ban.'),
			'ip' => array('IP Address', 'Bans users by their IP address. This may not work if some users share the same IP address.')
		);
	}

	public function deleteBanAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteBan')) {
			return;
		}

		$id = intval($_GET['id']);
		$ban = $this->bansService->get($id);
		if ($ban !== null) {
			$this->bansService->deleteById($id);
			$this->addMessage('Deleted from the banned list');
		} else {
			$this->addErrorMessage('Invalid ban');
		}
	}
	public function addBanAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'addBan')) {
			return;
		}

		$newBanIP = $_GET['newBanIP'];
		if (!filter_var($newBanIP, FILTER_VALIDATE_IP)) {
			$this->addErrorMessage('Invalid IP address');
			return;
		}
		if ($this->bansService->getByIp($newBanIP)) {
			$this->addErrorMessage('This IP is already banned');
			return;
		}
		$this->bansService->banIP($newBanIP);
		$this->addMessage("The IP address has been banned");
	}

	public function bansCallback() {
		$url = admin_url("options-general.php?page=".Settings::MENU_SLUG);
		$bans = $this->bansService->getAll();
		$html = "<div style='height: 150px; overflow: scroll; border: 1px solid #aaa; padding: 5px;'>";
		if (count($bans) == 0) {
			$html .= '<small>No bans added yet</small>';
		}
		$userIDs = [];
		foreach ($bans as $ban) {
			if ($ban->getUserId()) {
				$userIDs[] = $ban->getUserId();
			}
		}
		$usersMap = [];
		foreach ($this->usersService->getAll($userIDs) as $user) {
			$usersMap[$user->getId()] = $user->getName();
		}
		foreach ($bans as $ban) {
			$deleteURL = $url.'&wc_action=deleteBan&id='.urlencode($ban->getId()).'&wc_tab=bans'.'&nonce='.wp_create_nonce('deleteBan');
			$deleteLink = "<a href='{$deleteURL}' onclick='return confirm(\"Are you sure you want to delete this ban?\")'>Delete</a><br />";
			$html .= sprintf("%s [%s] %s | %s", $usersMap[$ban->getUserId()] ?? '', sanitize_text_field($ban->getIp()), date('Y-m-d H:i:s', $ban->getCreated()), $deleteLink);
		}
		$html .= "</div>";
		print($html);
	}
	public function banAddCallback() {
		$url = admin_url("options-general.php?page=".Settings::MENU_SLUG."&wc_action=addBan&wc_tab=bans".'&nonce='.wp_create_nonce('addBan'));
		printf(
			'<input type="text" value="" placeholder="IP address" id="newBanIP" name="newBanIP" />'.
			'<a class="button-secondary" href="%s" onclick="%s">Ban IP</a>',
			wp_nonce_url($url),
			'this.href += \'&newBanIP=\' + jQuery(\'#newBanIP\').val();'
		);
	}
}