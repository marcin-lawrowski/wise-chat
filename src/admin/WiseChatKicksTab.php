<?php

/**
 * Wise Chat admin kicks settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatKicksTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Kicks', 'List of IP addresses blocked from publishing messages and seeing the chat.'),
			array('kicks', 'Current Kicks', 'kicksCallback', 'void'),
			array('kick_add', 'Add Kick', 'kickAddCallback', 'void'),
		);
	}

	public function getDefaultValues() {
		return array(
			'kicks' => null,
			'kick_add' => null,
		);
	}

	public function deleteKickAction() {
		$id = intval($_GET['id']);
		$kick = $this->kicksDAO->get($id);
		if ($kick !== null) {
			$this->kicksDAO->delete($id);
			$this->addMessage('Kick has been deleted');
		} else {
			$this->addErrorMessage('Invalid kick');
		}
	}

	public function addKickAction() {
		$newKickIP = $_GET['newKickIP'];

		if (!filter_var($newKickIP, FILTER_VALIDATE_IP)) {
			$this->addErrorMessage('Invalid IP address');
			return;
		}

		if ($this->kicksService->isIpAddressKicked($newKickIP)) {
			$this->addErrorMessage('This IP is already kicked');
			return;
		}

		$this->kicksService->kickIpAddress($newKickIP, 'No name');
		$this->addMessage("Kick has been added");
	}

	public function kicksCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		$kicks = $this->kicksDAO->getAll();

		$html = "<div style='height: 150px; overflow: scroll; border: 1px solid #aaa; padding: 5px;'>";
		if (count($kicks) == 0) {
			$html .= '<small>No kicks were added yet</small>';
		}
		foreach ($kicks as $kick) {
			$deleteURL = $url.'&wc_action=deleteKick&id='.urlencode($kick->getId());
			$deleteLink = "<a href='{$deleteURL}' onclick='return confirm(\"Are you sure you want to delete?\")'>Delete</a><br />";
			$html .= sprintf("[%s] %s | <i>%s</i> | %s", $kick->getIp(), date('Y-m-d H:i:s', $kick->getCreated()), $kick->getLastUserName(), $deleteLink);
		}
		$html .= "</div>";
		print($html);
	}

	public function kickAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addKick");

		printf(
			'<input type="text" value="" placeholder="IP address" id="newKickIP" name="newKickIP" />'.
			'<a class="button-secondary" href="%s" title="Adds a new kick for given IP address" onclick="%s">Add Kick</a>',
			wp_nonce_url($url),
			'this.href += \'&newKickIP=\' + jQuery(\'#newKickIP\').val();'
		);
	}

}