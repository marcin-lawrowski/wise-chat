<?php 

namespace Kainex\WiseChat\Admin;

use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Services\ChannelsService;
use Kainex\WiseChat\Crypt;
use Kainex\WiseChat\Settings;

/**
 * Wise Chat admin channels settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class ChannelsTab extends AbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Channels Settings'),
			array('channels', 'Channels', 'channelsCallback', 'void'),
			array('permission_create_channels_role', 'Create Channels', 'checkboxesCallback', 'multivalues', 'Roles allowed to create new channels', self::getRoles()),
			array('permission_create_channels_anonymous', 'Create Channels by Anonymous', 'booleanFieldCallback', 'boolean', 'Allow creating new channels by anonymous users'),
			array('permission_search_channels_role', 'Search Channels', 'checkboxesCallback', 'multivalues', 'Roles allowed to search for channels', self::getRoles()),
			array('permission_search_channels_anonymous', 'Search Channels by Anonymous', 'booleanFieldCallback', 'boolean', 'Allow searching for channels by anonymous users'),
			array('admin_actions', 'Group Actions', 'adminActionsCallback', 'void'),
			array('auto_clean_after', 'Auto-remove Messages', 'stringFieldCallback', 'integer', 'The chat will delete messages older than the given amount of minutes. Empty field means no messages will be auto-deleted.'),
			array('auto_clean_direct_after', 'Auto-remove Private Messages', 'stringFieldCallback', 'integer', 'The chat will delete private messages older than the given amount of minutes. Empty field means no messages will be auto-deleted.'),
			array('channel_users_limit', 'Users Limit', 'stringFieldCallback', 'integer', 'The maximum number of users allowed to enter the chat. Empty field means there is no limit.'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'channels' => null,
			'permission_create_channels_anonymous' => 0,
			'permission_create_channels_role' => array('administrator', 'editor', 'author', 'contributor', 'subscriber'),
			'permission_search_channels_anonymous' => 0,
			'permission_search_channels_role' => array('administrator', 'editor', 'author', 'contributor', 'subscriber'),
			'admin_actions' => null,
			'auto_clean_after' => null,
			'auto_clean_direct_after' => null,
			'channel_users_limit' => null
		);
	}

	public function getProFields() {
		return array(
			'permission_create_channels_anonymous', 'permission_create_channels_role', 'permission_search_channels_anonymous', 'permission_search_channels_role'
		);
	}
	
	public function clearChannelAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'clearChannel')) {
			return;
		}

		try {
			$channelId = intval($_GET['channel']);
			$this->messagesService->deleteByChannel($channelId);
			$this->actions->publishAction('deleteAllMessagesFromChannel', array('channelId' => Crypt::encryptToString('c|' . $channelId)));
			$this->addMessage('All messages from the channel have been deleted');
		} catch (\Exception $e) {
			$this->addErrorMessage('Error: '.$e->getMessage());
		}
	}

    public function deleteChannelAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteChannel')) {
			return;
		}

		try {
			$channelId = intval($_GET['channel']);
			$channel = $this->channelsService->get($channelId);
			$this->channelsService->deleteChannel($channel);

			$this->addMessage(
				'The channel and all messages posted in the channel have been deleted. <br /><br />
	            Note: You have to remove [wise-chat] shortcode as well. If you don\'t remove it the channel will be created again when someone visits the page containing [wise-chat] shortcode.');
		} catch (\Exception $e) {
			$this->addErrorMessage('Error: '.$e->getMessage());
		}
    }
	
	public function backupChannelAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'backupChannel')) {
			return;
		}

		$channelId = intval($_GET['channel']);
		$channel = $this->channelsService->get($channelId);
		$channelStripped = preg_replace("/[^[:alnum:][:space:]]/ui", '', $channel->getName());
		$filename = "WiseChatChannelBackup-{$channelStripped}.csv";
		
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");
		
		$messages = $this->messagesService->getAllByChannelID($channelId);
		
		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array('Message ID', 'Time', 'User', 'Message', 'IP'));
		foreach ($messages as $message) {
			$messageArray = array(
				$message->getId(), date("Y-m-d H:i:s", $message->getTime()), $message->getUser() ? $message->getUser()->getName() : 'Unknown', $message->getText(), $message->getUser() ? $message->getUser()->getIp() : null
			);
			fputcsv($df, $this->cleanCSVRow($messageArray));
		}
		fclose($df);
		
		echo ob_get_clean();
		
		die();
	}

	private function cleanCSVRow($row) {
		$specialCharacters = array('+', '-', '=', '@');
		foreach ($row as $key => $value) {
			foreach ($specialCharacters as $character) {
				$value = preg_replace('/^'.preg_quote($character).'/', "'".$character, $value);
			}
			$row[$key] = $value;
		}

		return $row;
	}
	
	public function clearAllChannelsAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'clearAllChannels')) {
			return;
		}

		try {
			$this->messagesService->deleteAll();
			$this->actions->publishAction('deleteAllMessages', array());
			$this->addMessage('All messages have been deleted');
		} catch (\Exception $e) {
			$this->addErrorMessage('Error: '.$e->getMessage());
		}
	}
	
	public function setChannelPasswordAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'setChannelPassword')) {
			return;
		}

		$password = $_GET['p'];
		$channelId = intval($_GET['channel']);
		
		$channel = $this->channelsService->get($channelId);
		if ($channel !== null) {
			$channel->setPassword(md5($password));
			$this->channelsService->save($channel);
			$this->addMessage('The password has been set for the channel. The channel is now protected.');
		} else {
			$this->addErrorMessage('The channel does not exist');
		}
	}
	
	public function deleteChannelPasswordAction() {
		if (!current_user_can(Settings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteChannelPassword')) {
			return;
		}

		$channelId = intval($_GET['channel']);
		
		$channel = $this->channelsService->get($channelId);
		if ($channel !== null) {
			$channel->setPassword(null);
			$this->channelsService->save($channel);
			$this->addMessage('The password has been removed. The channel is not protected now.');
		} else {
			$this->addErrorMessage('The channel does not exist');
		}
	}
	
	public function channelsCallback() {
		$url = admin_url("options-general.php?page=".Settings::MENU_SLUG);
		
		$summary = $this->messagesDAO->getChannelsSummary();
		
		$html = "<table class='wp-list-table widefat'>";
		if (count($summary) == 0) {
			$html .= '<tr><td>No channels created yet</td></tr>';
		} else {
			$html .= '<thead><tr><th>&nbsp;Name</th><th>Type</th><th>Messages</th><th>Unique Authors</th><th>Last message</th><th width="200">Actions</th></tr></thead>';
		}
		
		foreach ($summary as $key => $channel) {
			$channelId = 'channel'.$key;
			$channelBackupId = 'channelBackup'.$key;
			$passwordLink = sprintf(
				'<a href="javascript://" title="Sets or replaces password for the channel" onclick="jQuery(\'#%s\').toggle()">Password</a>', $channelId
			);
		
			$clearURL = $url.'&wc_action=clearChannel&wc_tab=channels&channel='.urlencode($channel->channelId).'&nonce='.wp_create_nonce('clearChannel');
			$clearLink = "<a href='{$clearURL}' title='Deletes all messages from the channel' onclick='return confirm(\"Are you sure?\")'>Clear</a>";

			$backupLink = sprintf(
				'<a href="javascript://" title="Options to backup the channel" onclick="jQuery(\'#%s\').toggle()">Backup</a>', $channelBackupId
			);

            $deleteURL = $url.'&wc_action=deleteChannel&wc_tab=channels&channel='.urlencode($channel->channelId).'&nonce='.wp_create_nonce('deleteChannel');
            $deleteLink = "<a href='{$deleteURL}' title='Delete channel and all messages' onclick='return confirm(\"Are you sure you want to delete the channel?\")'>Delete</a>";
			
			$securedChannel = '';
			if ($channel->secured) {
				$securedChannel = sprintf('<img src="%s/gfx/icons/lock.png" alt="Secured channel" title="Secured channel" />', $this->options->getBaseDir());
			}
			
			$actions = array($passwordLink, $clearLink, $backupLink, $deleteLink);
			
			$classes = $key % 2 == 0 ? 'alternate' : '';

			$channelName = $channel->channelName;
			$typeName = $channel->channelType === Channel::TYPE_PRIVATE ? 'Private' : 'Public';
			$html .= sprintf(
				'<tr class="%s"><td>%s %s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				$classes, $securedChannel, $channelName, $typeName, $channel->messages, $channel->users,
				$channel->last_message != null ? date('Y-m-d H:i:s', $channel->last_message) : '', implode('&nbsp;|&nbsp;', $actions)
			);
			
			$passwordInputId = 'passwordInput'.$key;
			$setPasswordURL = admin_url("options-general.php?page=".Settings::MENU_SLUG."&wc_action=setChannelPassword&wc_tab=channels&channel=".urlencode($channel->channelId).'&nonce='.wp_create_nonce('setChannelPassword'));
			$deletePasswordURL = admin_url("options-general.php?page=".Settings::MENU_SLUG."&wc_action=deleteChannelPassword&wc_tab=channels&channel=".urlencode($channel->channelId).'&nonce='.wp_create_nonce('deleteChannelPassword'));
			$setPasswordAction = sprintf("this.href += '&p=' + jQuery('#%s').val();", $passwordInputId);
			$html .= sprintf(
				'<tr id="%s" class="%s" style="display: none;">
					<td colspan="5">
						Password: <input type="password" value="" placeholder="New password" id="%s" />
						<a class="button-secondary" href="%s" title="Sets or replaces channel password" onclick="%s">Set Password</a> | 
						<a class="button-secondary" href="%s" title="Removes password protection" onclick="return confirm(\'Are you sure?\')">Delete Password</a>
					</td>
				</tr>', 
				$channelId, $classes, $passwordInputId, $setPasswordURL, $setPasswordAction, $deletePasswordURL
			);

			$backupURL = $url.'&wc_action=backupChannel&wc_tab=channels&channel='.urlencode($channel->channelId).'&nonce='.wp_create_nonce('backupChannel');
			$html .= sprintf(
				'<tr id="%s" class="%s" style="display: none;">
					<td colspan="5">
						<a class="button-secondary" href="%s" title="Messages CSV">Download Messages</a>
						<p class=\'description\'><strong>Notice:</strong> Backups CSV files are UTF-8 encoded and comma-separated</p>
					</td>
				</tr>',
				$channelBackupId, $classes, $backupURL
			);
		}
		$html .= "</table><p class='description'><strong>Notice:</strong> users' counter accuracy: 120 s.</p>";

		print($html);
	}
	
	public function adminActionsCallback() {
		$url = admin_url("options-general.php?page=".Settings::MENU_SLUG."&wc_action=clearAllChannels&nonce=".wp_create_nonce('clearAllChannels'));
		
		printf(
			'<a class="button-secondary" href="%s" title="Deletes all messages sent to any channel" onclick="return confirm(\'Are you sure? All messages will be lost.\')">Clear All Messages</a>',
			wp_nonce_url($url)
		);
	}
}