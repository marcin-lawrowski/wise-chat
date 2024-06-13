<?php

/**
 * Wise Chat admin messages notifications tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatNotificationsTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'User Notifications', 'User notifications are sent when a private message is posted. Note that only registered WordPress users can receive such notifications. If the user is currently online then no notification is sent.', array('hideSubmitButton' => true)),
			array('user_notifications', 'E-mail Notifications', 'userNotificationsListCallback', 'void'),
			array('user_notification_add', 'New E-mail Notification', 'userNotificationAddCallback', 'void'),

			array('_section', 'Admin Notifications', 'Admin notifications are sent when a message is posted in the chat\'s public channel.', array('hideSubmitButton' => true)),
			array('notifications', 'E-mail Notifications', 'notificationsListCallback', 'void'),
			array('notification_add', 'New E-mail Notification', 'notificationAddCallback', 'void'),
		);
	}

	public function getDefaultValues() {
		return array(
			'notifications' => null,
			'notification_add' => null,
			'user_notifications' => null,
			'user_notification_add' => null,
		);
	}

	public function addNotificationAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'addNotification')) {
			return;
		}
	}

	public function editNotificationAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'editNotification')) {
			return;
		}
	}

	public function deleteNotificationAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteNotification')) {
			return;
		}
	}

	public function notificationsListCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);

		$notifications = [];

		$html = "<table class='wp-list-table widefat'>";
		if (count($notifications) == 0) {
			$html .= '<tr><td>No notifications created yet</td></tr>';
		} else {
			$html .= '<thead><tr><th>&nbsp;Send when</th><th>No more than</th><th>E-mail</th><th>Subject</th><th></th></tr></thead>';
		}
		$html .= '</table>';

		print($html);
		$this->printProFeatureNotice();
	}

	public function notificationAddCallback() {
		print($this->getNotificationForm(null));

		$this->printProFeatureNotice();
	}

	/**
	 * @param $notification
	 * @return string HTML form
	 */
	private function getNotificationForm($notification) {
		$details = $notification !== null ? $notification->getDetails() : array();
		$currentUser = wp_get_current_user();
		$url = $notification !== null
				? admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=editNotification&notificationId=".$notification->getId().'&nonce='.wp_create_nonce('editNotification'))
				: admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addNotification&nonce=".wp_create_nonce('addNotification'));

		// actions:
		$actionsHtmlOptions = '<option value="message">a message is posted</option>';

		// frequencies:
		$frequenciesHtmlOptions = '<option value="daily">one a day</option>';

		$recipient = $notification !== null
			? (array_key_exists('recipientEmail', $details) ? $details['recipientEmail'] : '')
			: ($currentUser instanceof WP_User ? $currentUser->user_email : '');

		$subject = $notification !== null
			? (array_key_exists('subject', $details) ? $details['subject'] : '')
			: 'New Message in Chat';

		$content = $notification !== null
			? (array_key_exists('content', $details) ? $details['content'] : '')
			: sprintf("Hello%s,\n\nA new message has been posted in the chat.\n\nUser: {user}\nChannel: {channel}\nMessage: {message}\n\nBest regards,\n%s", $currentUser instanceof WP_User ? ' '.$currentUser->display_name : '', get_bloginfo( 'name' ));

		$buttonLabel = $notification !== null ? 'Save Notification' : 'Add Notification';

		return sprintf(
			'<table class="wp-list-table widefat wc-notification-form">'.
				'<tr>'.
					'<td class="th-full" width="150">Send when:</td>'.
					'<td>
						<select id="notificationAction">%s</select>
						<p class="description" style="display: inline;"></p>
					</td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">No more than:</td>'.
					'<td>
						<label><select id="notificationFrequency">%s</select></label>
					</td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">E-mail:</td>'.
					'<td><input type="email" value="%s" placeholder="E-mail" id="notificationRecipientEmail" style="width: 100%%;" /></td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">Subject:</td>'.
					'<td><input type="text" value="%s" placeholder="Subject" id="notificationSubject" style="width: 100%%;" /></td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">Content:</td>'.
					'<td>
						<textarea placeholder="Content" id="notificationContent" rows="10" style="width: 100%%;">%s</textarea>
						<p class="description">Available variables: {user}, {message}, {channel}</p>
					</td>'.
				'</tr>'.
				'<tr>'.
					'<td colspan="2"><a class="button-secondary wc-save-notification-button" disabled href="">%s</a></td>'.
				'</tr>'.
			'</table>',

			$actionsHtmlOptions,
			$frequenciesHtmlOptions,
			$recipient,
			$this->fixImunify360RuleText($subject),
			$this->fixImunify360RuleText($content),
			$buttonLabel
		);
	}

	public function addUserNotificationAction() {

	}

	public function editUserNotificationAction() {

	}

	public function deleteUserNotificationAction() {

	}

	/**
	 * @param $notification
	 * @return string HTML form
	 */
	private function getUserNotificationForm($notification) {
		$details = $notification !== null ? $notification->getDetails() : array();
		$url = $notification !== null
			? admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=editUserNotification&notificationId=".$notification->getId())
			: admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addUserNotification");

		// frequencies:
		$frequenciesHtmlOptions = '<option value="daily">one a day</option>';

		$subject = $notification !== null
			? (array_key_exists('subject', $details) ? $details['subject'] : '')
			: 'New Private Message from {sender}';

		$content = $notification !== null
			? (array_key_exists('content', $details) ? $details['content'] : '')
			: "Hello {recipient},\n\nA new message has been sent to you in the chat.\n\nSender: {sender}\nGo to the chat page: {link}\n\nBest regards,\n".get_bloginfo( 'name' );

		$buttonLabel = $notification !== null ? 'Save Notification' : 'Add Notification';

		return sprintf(
			'<table class="wp-list-table widefat wc-user-notification-form">'.
				'<tr>'.
					'<td class="th-full">No more than:</td>'.
					'<td>
						<label><select id="userNotificationFrequency">%s</select></label> from each user separately
					</td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">Subject:</td>'.
					'<td><input type="text" value="%s" placeholder="Subject" id="userNotificationSubject" style="width: 100%%;" /></td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">Content:</td>'.
					'<td>
						<textarea placeholder="Content" id="userNotificationContent" rows="10" style="width: 100%%;">%s</textarea>
						<p class="description">Available variables: {recipient}, {recipient-email}, {sender}, {link}, {message}, {channel}</p>
					</td>'.
				'</tr>'.
				'<tr>'.
					'<td colspan="2"><a class="button-secondary wc-save-user-notification-button" disabled href="">%s</a></td>'.
				'</tr>'.
			'</table>',

			$frequenciesHtmlOptions,
			$this->fixImunify360RuleText($subject),
			$this->fixImunify360RuleText($content),
			$buttonLabel
		);
	}

	public function userNotificationsListCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);

		$notifications = [];

		$html = "<table class='wp-list-table widefat'>";
		if (count($notifications) == 0) {
			$html .= '<tr><td>No user notifications created yet</td></tr>';
		} else {
			$html .= '<thead><tr><th>No more than</th><th>Subject</th><th></th></tr></thead>';
		}
		$html .= '</table>';

		print($html);

		$this->printProFeatureNotice();
	}

	public function userNotificationAddCallback() {
		print($this->getUserNotificationForm(null));

		$this->printProFeatureNotice();
	}
}