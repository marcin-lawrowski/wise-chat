<?php 

/**
 * Wise Chat admin channels settings tab class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatChannelsTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Channels Settings'),
			array('channels', 'Channels', 'channelsChallback', 'void'),
			array('admin_actions', 'Group Actions', 'adminActionsCallback', 'void'),
			array('auto_clean_after', 'Auto-remove Messages', 'stringFieldCallback', 'integer', 'The chat will delete messages older than given amount of minutes. Empty field means no messages will be deleted.'),
			array('channel_users_limit', 'Users Limit', 'stringFieldCallback', 'integer', 'Maximum amount of users allowed to enter a channel. Empty field means there is no limit.'),
			array('channels_limit', 'Channels Limit', 'stringFieldCallback', 'integer', 'Maximum amount of channels that an user can participate simultaneously. Empty field means there is no limit.'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'channels' => null,
			'admin_actions' => null,
			'auto_clean_after' => null,
			'channel_users_limit' => null,
			'channels_limit' => null
		);
	}
	
	public function clearChannelAction() {
		$channelName = $_GET['channel'];
		
		$this->messagesService->deleteByChannel($channelName);
        $channel = $this->channelsDAO->getByName($channelName);
		$this->actions->publishAction('deleteAllMessagesFromChannel', array('channelId' => $channel->getId()));
		$this->addMessage('All messages from the channel have been deleted');
	}

    public function deleteChannelAction() {
        $channelName = $_GET['channel'];

        $this->messagesService->deleteByChannel($channelName);
        $channel = $this->channelsDAO->getByName($channelName);
        $this->channelsDAO->deleteById($channel->getId());
        $this->actions->publishAction('deleteAllMessagesFromChannel', array('channelId' => $channel->getId()));
        $this->addMessage(
            'The channel and all messages from the channel have been deleted. <br /><br />
            Note: You have to remove [wise-chat] shortcode as well. If you don\'t remove it the channel will be created again when someone enters the page that contains [wise-chat] shortcode.');
    }
	
	public function backupChannelAction() {
		$channel = $_GET['channel'];
		$channelStripped = preg_replace("/[^[:alnum:][:space:]]/ui", '', $channel);
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
		
		$messages = $this->messagesService->getAllByChannelName($channel);
		
		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array('ID', 'Time', 'User', 'Message', 'IP'));
		foreach ($messages as $message) {
			$messageArray = array(
				$message->getId(), date("Y-m-d H:i:s", $message->getTime()), $message->getUserName(), $message->getText(), $message->getIp()
			);
			fputcsv($df, $messageArray);
		}
		fclose($df);
		
		echo ob_get_clean();
		
		die();
	}
	
	public function clearAllChannelsAction() {
		$this->messagesService->deleteAll();
		$this->actions->publishAction('deleteAllMessages', array());
		$this->addMessage('All messages have been deleted');
	}
	
	public function setChannelPasswordAction() {
		$password = $_GET['p'];
		$channelName = $_GET['channel'];
		
		$channel = $this->channelsDAO->getByName($channelName);
		if ($channel !== null) {
			$channel->setPassword(md5($password));
			$this->channelsDAO->save($channel);
			$this->addMessage('The password has been set for the channel. The channel is now protected.');
		} else {
			$this->addErrorMessage('The channel does not exist');
		}
	}
	
	public function deleteChannelPasswordAction() {
		$channelName = $_GET['channel'];
		
		$channel = $this->channelsDAO->getByName($channelName);
		if ($channel !== null) {
			$channel->setPassword(null);
			$this->channelsDAO->save($channel);
			$this->addMessage('The password has been removed. The channel is not protected now.');
		} else {
			$this->addErrorMessage('The channel does not exist');
		}
	}
	
	public function channelsChallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		
		$summary = $this->messagesDAO->getChannelsSummary();
		
		$html = "<table class='wp-list-table widefat'>";
		if (count($summary) == 0) {
			$html .= '<tr><td>No channels created yet</td></tr>';
		} else {
			$html .= '<thead><tr><th>&nbsp;Name</th><th>Messages</th><th>Users</th><th>Last message</th><th>Actions</th></tr></thead>';
		}
		
		foreach ($summary as $key => $channel) {
			$channelId = 'channel'.$key;
			$passwordLink = sprintf(
				'<a href="javascript://" title="Sets or replaces password for the channel" onclick="jQuery(\'#%s\').toggle()">Password</a>', $channelId
			);
		
			$clearURL = $url.'&wc_action=clearChannel&channel='.urlencode($channel->channel);
			$clearLink = "<a href='{$clearURL}' title='Deletes all messages from the channel' onclick='return confirm(\"Are you sure?\")'>Clear</a>";
			
			$backupURL = $url.'&wc_action=backupChannel&channel='.urlencode($channel->channel);
			$backupLink = "<a href='{$backupURL}' title='Export messages to CSV file'>Backup</a>";

            $deleteURL = $url.'&wc_action=deleteChannel&channel='.urlencode($channel->channel);
            $deleteLink = "<a href='{$deleteURL}' title='Delete channel and all messages' onclick='return confirm(\"Are you sure you want to delete the channel?\")'>Delete</a>";
			
			$securedChannel = '';
			if ($channel->secured) {
				$securedChannel = sprintf('<img src="%s/gfx/icons/lock.png" alt="Secured channel" title="Secured channel" />', $this->options->getBaseDir());
			}
			
			$actions = array($passwordLink, $clearLink, $backupLink, $deleteLink);
			
			$classes = $key % 2 == 0 ? 'alternate' : '';
			
			$html .= sprintf(
				'<tr class="%s"><td>%s %s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', 
				$classes, $securedChannel, $channel->channel, $channel->messages, $channel->users, 
				$channel->last_message != null ? date('Y-m-d H:i:s', $channel->last_message) : '', implode('&nbsp;|&nbsp;', $actions)
			);
			
			$passwordInputId = 'passwordInput'.$key;
			$setPasswordURL = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=setChannelPassword&channel=".urlencode($channel->channel));
			$deletePasswordURL = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=deleteChannelPassword&channel=".urlencode($channel->channel));
			$setPasswordAction = sprintf("this.href += '&p=' + jQuery('#%s').val();", $passwordInputId);
			$html .= sprintf(
				'<tr id="%s" class="%s" style="display: none;">
					<td colspan="5">
						Password: <input type="password" value="" placeholder="New password" id="%s" />
						<a class="button-secondary" href="%s" title="Sets or replaces channel password" onclick="%s">Set Password</a> | 
						<a class="button-secondary" href="%s" title="Removes password protection" onclick="return confirm(\'Are you sure?\')">Delete Password</a>
					</td>
				</tr>', 
				$channelId, $classes, $passwordInputId, wp_nonce_url($setPasswordURL), $setPasswordAction, wp_nonce_url($deletePasswordURL)
			);
		}
		$html .= "</table><p class='description'><strong>Notice:</strong> users' counter accuracy: 120 s.</p>";
		$html .= "<p class='description'><strong>Notice:</strong> Backups CSV files are UTF-8 encoded and comma-separated</p>";

		print($html);
	}
	
	public function adminActionsCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=clearAllChannels");
		
		printf(
			'<a class="button-secondary" href="%s" title="Deletes all messages sent to any channel" onclick="return confirm(\'Are you sure? All messages will be lost.\')">Clear All Messages</a>',
			wp_nonce_url($url)
		);
	}
}