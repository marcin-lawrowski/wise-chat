<?php 

/**
 * Wise Chat admin muting settings tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatBansTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Muted Users', 'Prevents IP addresses from posting messages in all channels.', array('hideSubmitButton' => true)),
			array('bans', 'Muted IPs', 'bansCallback', 'void'),
			array('ban_add', 'Mute IP', 'banAddCallback', 'void'),
			
			array('_section', 'Automatic Muting', 'Automatically mutes IP addresses after posting certain number of messages containing bad words.'),
			array('enable_autoban', 'Enable Automatic Muting', 'booleanFieldCallback', 'boolean'),
			array('autoban_threshold', 'Threshold', 'stringFieldCallback', 'integer', 'Determines how many messages containing bad words can be posted before the user gets automatically muted'),
			array('autoban_duration', 'Duration', 'stringFieldCallback', 'integer', 'Duration of the automatic muting (in minutes). Empty field sets the value to 1440 minutes (1 day)'),

			array('_section', 'Flood Control', 'Detects spammers by counting how often their post messages and mutes them.'),
			array('enable_flood_control', 'Enable Flood Control', 'booleanFieldCallback', 'boolean'),
			array('flood_control_threshold', 'Threshold', 'stringFieldCallback', 'integer', 'Determines how many messages (in given time window) could be posted before the user gets automatically muted'),
			array('flood_control_time_frame', 'Time Window', 'stringFieldCallback', 'integer', 'Time window (in minutes) of the flood control'),
			array('flood_control_ban_duration', 'Duration', 'stringFieldCallback', 'integer', 'Determines how long the IP address is muted (in minutes). Empty field sets the value to 1440 minutes (1 day)'),

		);
	}
	
	public function getDefaultValues() {
		return array(
			'bans' => null,
			'ban_add' => null,
			'enable_autoban' => 0,
			'autoban_threshold' => '3',
			'autoban_duration' => 1440,
			'enable_flood_control' => 0,
			'flood_control_threshold' => 200,
			'flood_control_time_frame' => 1,
			'flood_control_ban_duration' => 1440
		);
	}
	
	public function getParentFields() {
		return array(
			'autoban_threshold' => 'enable_autoban',
			'autoban_duration' => 'enable_autoban',
			'flood_control_threshold' => 'enable_flood_control',
			'flood_control_time_frame' => 'enable_flood_control',
			'flood_control_ban_duration' => 'enable_flood_control'
		);
	}
	
	public function deleteBanAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteBan')) {
			return;
		}

		$ip = $_GET['ip'];
		$ban = $this->bansDAO->getByIp($ip);
		if ($ban !== null) {
			$this->bansDAO->deleteByIp($ip);
			$this->addMessage('IP address has been deleted from the muted list');
		}
	}
	
	public function addBanAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'addBan')) {
			return;
		}

		$newBanIP = $_GET['newBanIP'];
		$newBanDuration = $_GET['newBanDuration'];
		
		$ban = $this->bansDAO->getByIp($newBanIP);
		if ($ban !== null) {
			$this->addErrorMessage('This IP is already muted');
			return;
		}
		
		if ($newBanIP) {
			$duration = $this->bansService->getDurationFromString($newBanDuration);
			
			$this->bansService->banIpAddress($newBanIP, $duration);
			$this->addMessage("IP address has been added to the muted list");
		}
	}
	
	public function bansCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		$bans = $this->bansDAO->getAll();
		
		$html = "<div style='height: 150px; overflow: scroll; border: 1px solid #aaa; padding: 5px;'>";
		if (count($bans) == 0) {
			$html .= '<small>No muted IP addresses added yet</small>';
		}
		foreach ($bans as $ban) {
			$deleteURL = $url.'&wc_action=deleteBan&tab=bans&ip='.urlencode($ban->getIp()).'&nonce='.wp_create_nonce('deleteBan');
			$deleteLink = "<a href='{$deleteURL}' onclick='return confirm(\"Are you sure?\")'>Delete</a><br />";
			$html .= sprintf("[%s] %s left | %s", sanitize_text_field($ban->getIp()), $this->getTimeSummary($ban->getTime() - time()), $deleteLink);
		}
		$html .= "</div>";
		print($html);
	}
	
	public function banAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&tab=bans&wc_action=addBan".'&nonce='.wp_create_nonce('addBan'));
		
		printf(
			'<input type="text" value="" placeholder="IP address to mute" id="newBanIP" name="newBanIP" />'.
			'<input type="text" value="" placeholder="Duration, e.g. 4m, 2d, 16h" id="newBanDuration" name="newBanDuration" />'.
			'<a class="button-secondary" href="%s" onclick="%s">Mute IP</a>',
			$url,
			'this.href += \'&newBanIP=\' + jQuery(\'#newBanIP\').val() + \'&newBanDuration=\' + jQuery(\'#newBanDuration\').val();'
		);
	}
	
	private function getTimeSummary($seconds) {
		$dateFirst = new DateTime("@0");
		$dateSecond = new DateTime("@$seconds");
		
		return $dateFirst->diff($dateSecond)->format('%a days, %h hours, %i minutes and %s seconds');
	}
}