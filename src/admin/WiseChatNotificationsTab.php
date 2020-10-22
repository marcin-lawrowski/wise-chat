<?php

/**
 * Wise Chat admin messages notifications tab class.
 *
 * @author Kainex <contact@kaine.pl>
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

	public function notificationsListCallback() {
		$html = "<table class='wp-list-table widefat'>";
		$html .= '<tr><td>No notifications created yet</td></tr>';
		$html .= '</table>';

		print($html);
	}

	public function notificationAddCallback() {
		$currentUser = wp_get_current_user();

		// actions:
		$actionsHtmlOptions = '<option value="">A message is posted</option>';

		// frequencies:
		$frequenciesHtmlOptions = '<option value="">One a day</option>';

		$recipient = ($currentUser instanceof WP_User ? $currentUser->user_email : '');

		$subject = 'New Message in Chat';

		$content = sprintf("Hello%s,\n\nA new message has been posted in the chat.\n\nUser: \${user}\nChannel: \${channel}\nMessage: \${message}\n\nBest regards,\n%s", $currentUser instanceof WP_User ? ' '.$currentUser->display_name : '', get_bloginfo( 'name' ));

		$buttonLabel = 'Add Notification';

		echo sprintf(
			'<table class="wp-list-table widefat wc-notification-form">'.
			'<tr>'.
			'<td class="th-full" width="150">Send when:</td>'.
			'<td>
						<select id="notificationAction" disabled>%s</select>
						<p class="description" style="display: inline;"></p>
					</td>'.
			'</tr>'.
			'<tr>'.
			'<td class="th-full">No more than:</td>'.
			'<td>
						<label><select id="notificationFrequency" disabled>%s</select></label>
					</td>'.
			'</tr>'.
			'<tr>'.
			'<td class="th-full">E-mail:</td>'.
			'<td><input type="email" value="%s" placeholder="E-mail" id="notificationRecipientEmail" disabled style="width: 100%%;" /></td>'.
			'</tr>'.
			'<tr>'.
			'<td class="th-full">Subject:</td>'.
			'<td><input type="text" value="%s" placeholder="Subject" id="notificationSubject" disabled style="width: 100%%;" /></td>'.
			'</tr>'.
			'<tr>'.
			'<td class="th-full">Content:</td>'.
			'<td>
						<textarea placeholder="Content" id="notificationContent" rows="10" disabled style="width: 100%%;">%s</textarea>
						<p class="description">Available variables: ${user}, ${message}, ${channel}</p>
					</td>'.
			'</tr>'.
			'<tr>'.
			'<td colspan="2"><a class="button-secondary wc-save-notification-button" disabled href="#">%s</a></td>'.
			'</tr>'.
			'</table>',

			$actionsHtmlOptions,
			$frequenciesHtmlOptions,
			$recipient,
			$subject,
			$content,
			$buttonLabel
		);
		$this->printProFeatureNotice();
	}

	public function userNotificationAddCallback() {
		$frequenciesHtmlOptions = '<option value="">One a day</option>';

		$subject = 'New Private Message from ${sender}';

		$content = "Hello \${recipient},\n\nA new message has been sent to you in the chat.\n\nSender: \${sender}\nGo to the chat page: \${link}\n\nBest regards,\n".get_bloginfo( 'name' );

		$buttonLabel = 'Add Notification';

		echo sprintf(
			'<table class="wp-list-table widefat wc-user-notification-form">'.
			'<tr>'.
			'<td class="th-full">No more than:</td>'.
			'<td>
						<label><select id="userNotificationFrequency" disabled>%s</select></label> from each user separately
					</td>'.
			'</tr>'.
			'<tr>'.
			'<td class="th-full">Subject:</td>'.
			'<td><input type="text" value="%s" placeholder="Subject" id="userNotificationSubject" disabled style="width: 100%%;" /></td>'.
			'</tr>'.
			'<tr>'.
			'<td class="th-full">Content:</td>'.
			'<td>
						<textarea placeholder="Content" id="userNotificationContent" disabled rows="10" style="width: 100%%;">%s</textarea>
						<p class="description">Available variables: ${recipient}, ${recipient-email}, ${sender}, ${link}, ${message}, ${channel}</p>
					</td>'.
			'</tr>'.
			'<tr>'.
			'<td colspan="2"><a class="button-secondary wc-save-user-notification-button" disabled="" href="#">%s</a></td>'.
			'</tr>'.
			'</table>',

			$frequenciesHtmlOptions,
			$subject,
			$content,
			$buttonLabel
		);
		$this->printProFeatureNotice();
	}

	public function userNotificationsListCallback() {
		$html = "<table class='wp-list-table widefat'>";
		$html .= '<tr><td>No user notifications created yet</td></tr>';
		$html .= '</table>';

		print($html);
	}
}