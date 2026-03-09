<?php 

namespace Kainex\WiseChat\Admin;

use Kainex\WiseChat\Settings;

/**
 * Wise Chat admin muting settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserMutesTab extends AbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Muted Users', 'Prevents users from posting messages in all channels.'),
			array('mutes_mode', 'Mode', 'radioCallback', 'string', '', self::getMuteModes()),
			array('muted_users', 'Muted Users', 'userMutesCallback', 'void'),
			array('muted_user_add', 'Mute by IP', 'userMuteAddCallback', 'void'),
			
			array('_section', 'Automatic Muting', 'Automatically mutes IP addresses after posting certain number of messages containing bad words.'),
			array('enable_automute', 'Enable Automatic Muting', 'booleanFieldCallback', 'boolean'),
			array('automute_threshold', 'Threshold', 'stringFieldCallback', 'integer', 'Determines how many messages containing bad words can be posted before the user gets automatically muted'),
			array('automute_duration', 'Duration', 'stringFieldCallback', 'integer', 'Duration of the automatic muting (in minutes). Empty field sets the value to 1440 minutes (1 day)'),

			array('_section', 'Flood Control', 'Detects spammers by counting how often their post messages and mutes them.'),
			array('enable_flood_control', 'Enable Flood Control', 'booleanFieldCallback', 'boolean'),
			array('flood_control_threshold', 'Threshold', 'stringFieldCallback', 'integer', 'Determines how many messages (in given time window) could be posted before the user gets automatically muted'),
			array('flood_control_time_frame', 'Time Window', 'stringFieldCallback', 'integer', 'Time window (in minutes) of the flood control'),
			array('flood_control_mute_duration', 'Duration', 'stringFieldCallback', 'integer', 'Determines how long the IP address is muted (in minutes). Empty field sets the value to 1440 minutes (1 day)'),

		);
	}
	
	public function getDefaultValues() {
		return array(
			'mutes_mode' => 'userId',
			'muted_users' => null,
			'muted_user_add' => null,
			'enable_automute' => 0,
			'automute_threshold' => '3',
			'automute_duration' => 1440,
			'enable_flood_control' => 0,
			'flood_control_threshold' => 200,
			'flood_control_time_frame' => 1,
			'flood_control_mute_duration' => 1440
		);
	}
	
	public function getParentFields() {
		return array(
			'automute_threshold' => 'enable_automute',
			'automute_duration' => 'enable_automute',
			'flood_control_threshold' => 'enable_flood_control',
			'flood_control_time_frame' => 'enable_flood_control',
			'flood_control_mute_duration' => 'enable_flood_control'
		);
	}

	private static function getMuteModes() {
		return array(
			'userId' => array('User ID', 'Mutes users by their IDs. This works fine for logged in users, but anonymous users may clear cookies and create a new user ID bypassing the restriction.'),
			'ip' => array('IP Address', 'Mutes users by their IP address. This may not work if some users share the same IP address.')
		);
	}

	public function deleteUserMuteAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteUserMute')) {
			return;
		}

		$this->userMutesDAO->deleteById($_GET['id']);
		$this->addMessage('Deleted from the muted list');
	}
	
	public function addUserMuteAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'addUserMute')) {
			return;
		}

		$newUserMuteIP = $_GET['newUserMuteIP'];
		$newUserMuteDuration = $_GET['newUserMuteDuration'];
		
		$userMute = $this->userMutesDAO->getByIp($newUserMuteIP);
		if ($userMute !== null) {
			$this->addErrorMessage('This IP is already muted');
			return;
		}
		
		if ($newUserMuteIP) {
			$duration = $this->userMutesService->getDurationFromString($newUserMuteDuration);
			
			$this->userMutesService->muteIP($newUserMuteIP, $duration);
			$this->addMessage("IP address has been added to the muted users list");
		}
	}
	
	public function userMutesCallback() {
		$url = admin_url("options-general.php?page=".Settings::MENU_SLUG);
		$userMutes = $this->userMutesDAO->getAll();
		$userIDs = array_map(function ($userMute) { return $userMute->getUserId(); }, $userMutes);
		$users = $this->usersService->getAll($userIDs);
		$usersMap = array();
		foreach ($users as $user) {
			$usersMap[$user->getId()] = $user->getName();
		}
		
		$html = "<div style='height: 150px; overflow: scroll; border: 1px solid #aaa; padding: 5px;'>";
		if (count($userMutes) == 0) {
			$html .= '<small>No muted users added yet</small>';
		}
		foreach ($userMutes as $userMute) {
			$userName = $userMute->getUserId()
				? (isset($usersMap[$userMute->getUserId()]) ? $usersMap[$userMute->getUserId()] : 'Unknown User')
				: 'All users matching IP address';
			$deleteURL = $url.'&wc_action=deleteUserMute&wc_tab=userMutes&id='.urlencode($userMute->getId()).'&nonce='.wp_create_nonce('deleteUserMute');
			$deleteLink = "<a href='{$deleteURL}' onclick='return confirm(\"Are you sure?\")'>Delete</a><br />";
			$html .= sprintf("%s [%s] %s left | %s", $userName, sanitize_text_field($userMute->getIp()), $this->getTimeSummary($userMute->getTime() - time()), $deleteLink);
		}
		$html .= "</div>";
		print($html);
	}
	
	public function userMuteAddCallback() {
		$url = admin_url("options-general.php?page=".Settings::MENU_SLUG."&wc_tab=userMutes&wc_action=addUserMute".'&nonce='.wp_create_nonce('addUserMute'));
		
		printf(
			'<input type="text" value="" placeholder="IP address to mute" id="newUserMuteIP" name="newUserMuteIP" />'.
			'<input type="text" value="" placeholder="Duration, e.g. 4m, 2d, 16h" id="newUserMuteDuration" name="newUserMuteDuration" />'.
			'<a class="button-secondary" href="%s" onclick="%s">Mute IP</a>',
			$url,
			'this.href += \'&newUserMuteIP=\' + jQuery(\'#newUserMuteIP\').val() + \'&newUserMuteDuration=\' + jQuery(\'#newUserMuteDuration\').val();'
		);
	}
	
	private function getTimeSummary($seconds) {
		$dateFirst = new \DateTime("@0");
		$dateSecond = new \DateTime("@$seconds");
		
		return $dateFirst->diff($dateSecond)->format('%a days, %h hours, %i minutes and %s seconds');
	}
}