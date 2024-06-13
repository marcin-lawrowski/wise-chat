<?php
/**
 * Wise Chat admin kicks settings tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatKicksTab extends WiseChatAbstractTab {
	public function getFields() {
		return array(
			array('_section', 'Banned Users', 'List of IP addresses blocked from accessing the chat.', array('hideSubmitButton' => true)),
			array('kicks', 'Banned IPs', 'kicksCallback', 'void'),
			array('kick_add', 'Ban IP', 'kickAddCallback', 'void'),
		);
	}
	public function getDefaultValues() {
		return array(
			'kicks' => null,
			'kick_add' => null,
		);
	}
	public function deleteKickAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteKick')) {
			return;
		}

		$id = intval($_GET['id']);
		$kick = $this->kicksDAO->get($id);
		if ($kick !== null) {
			$this->kicksDAO->delete($id);
			$this->addMessage('The IP address has been deleted from the banned list');
		} else {
			$this->addErrorMessage('Invalid ban');
		}
	}
	public function addKickAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'addKick')) {
			return;
		}

		$newKickIP = $_GET['newKickIP'];
		if (!filter_var($newKickIP, FILTER_VALIDATE_IP)) {
			$this->addErrorMessage('Invalid IP address');
			return;
		}
		if ($this->kicksService->isIpAddressKicked($newKickIP)) {
			$this->addErrorMessage('This IP is already banned');
			return;
		}
		$this->kicksService->kickIpAddress($newKickIP, 'No name');
		$this->addMessage("The IP address has been added to the banned list");
	}
	public function kicksCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		$kicks = $this->kicksDAO->getAll();
		$html = "<div style='height: 150px; overflow: scroll; border: 1px solid #aaa; padding: 5px;'>";
		if (count($kicks) == 0) {
			$html .= '<small>No bans added yet</small>';
		}
		foreach ($kicks as $kick) {
			$deleteURL = $url.'&wc_action=deleteKick&id='.urlencode($kick->getId()).'&tab=kicks'.'&nonce='.wp_create_nonce('deleteKick');
			$deleteLink = "<a href='{$deleteURL}' onclick='return confirm(\"Are you sure you want to delete this IP?\")'>Delete</a><br />";
			$html .= sprintf("[%s] %s | <i>%s</i> | %s", $kick->getIp(), date('Y-m-d H:i:s', $kick->getCreated()), $kick->getLastUserName(), $deleteLink);
		}
		$html .= "</div>";
		print($html);
	}
	public function kickAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addKick&tab=kicks".'&nonce='.wp_create_nonce('addKick'));
		printf(
			'<input type="text" value="" placeholder="IP address" id="newKickIP" name="newKickIP" />'.
			'<a class="button-secondary" href="%s" onclick="%s">Ban IP</a>',
			wp_nonce_url($url),
			'this.href += \'&newKickIP=\' + jQuery(\'#newKickIP\').val();'
		);
	}
}