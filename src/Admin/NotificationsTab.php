<?php

namespace Kainex\WiseChat\Admin;

use Kainex\WiseChat\Model\Notification;
use Kainex\WiseChat\Model\UserNotification;
use Kainex\WiseChat\Settings;

/**
 * Wise Chat admin messages notifications tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class NotificationsTab extends AbstractTab {

	public function getFields() {
		return array(
			array('_section', 'User Notifications', 'User notifications are sent when a private message is posted. Note that only registered WordPress users can receive such notifications. If the user is currently online then no notification is sent.', array('hideSubmitButton' => true)),
			array('user_notifications', 'E-mail Notifications', 'userNotificationsListCallback', 'void'),
			array('user_notification_add', 'New E-mail Notification', 'userNotificationAddCallback', 'void'),

			array('_section', 'Admin Notifications', 'Admin notifications are sent when a message is posted in the chat\'s public channel.', array('hideSubmitButton' => true)),
			array('notifications', 'E-mail Notifications', 'notificationsListCallback', 'void'),
			array('notification_add', 'New E-mail Notification', 'notificationAddCallback', 'void'),

			array('_section', 'Feed Notifications', 'User e-mail notifications about new entries in the feed'),
			array('feed_notifications_new_entry_enabled', 'Enabled', 'booleanFieldCallback', 'boolean'),
			array('feed_notifications_new_entry_subject', 'E-mail Subject', 'stringFieldCallback', 'string'),
			array(
				'feed_notifications_new_entry_body', 'E-mail Body', 'multilineFieldCallback', 'multilinestring',
				'Dynamic variables: {id}, {username}, {displayname}, {email}, {firstname}, {lastname}, {nickname}, {description}, {website}, {link}<br />'
			),
		);
	}

	public function getProFields() {
		return array(
			'feed_notifications_new_entry_enabled', 'feed_notifications_new_entry_subject', 'feed_notifications_new_entry_body'
		);
	}

	public function getDefaultValues() {
		return array(
			'notifications' => null,
			'notification_add' => null,
			'user_notifications' => null,
			'user_notification_add' => null,
			'feed_notifications_new_entry_enabled' => 1,
			'feed_notifications_new_entry_subject' => 'New Feed Entry',
			'feed_notifications_new_entry_body' => "Hello {displayname},

You have a pending feed entry in the chat.

{link}

Best regards"
		);
	}

	public function getParentFields() {
		return array(
			'feed_notifications_new_entry_subject' => 'feed_notifications_new_entry_enabled',
			'feed_notifications_new_entry_body' => 'feed_notifications_new_entry_enabled',
		);
	}

	public function addNotificationAction() {

	}

	public function editNotificationAction() {

	}

	public function deleteNotificationAction() {

	}

	public function notificationsListCallback() {
		$url = admin_url("options-general.php?page=".Settings::MENU_SLUG);

		$notifications = $this->notificationsDAO->getAll();

		$html = "<table class='wp-list-table widefat'>";
		if (count($notifications) == 0) {
			$html .= '<tr><td>No notifications created yet</td></tr>';
		} else {
			$html .= '<thead><tr><th>&nbsp;Send when</th><th>No more than</th><th>E-mail</th><th>Subject</th><th></th></tr></thead>';
		}

		foreach ($notifications as $key => $notification) {
			$deleteURL = $url.'&wc_action=deleteNotification&id='.$notification->getId().'&wc_tab=notifications&nonce='.wp_create_nonce('deleteNotification');
			$editLink = '<a href="javascript://" title="Edit notification" onclick="jQuery(\'#editNotification'.$notification->getId().'\').toggle()">Edit</a>';
			$deleteLink = "<a href='{$deleteURL}' title='Delete notification' onclick='return confirm(\"Are you sure you want to delete this notification?\")'>Delete</a>";

			$html .= sprintf(
				'<tr class="%s"><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s | %s</td></tr>',
				($key % 2 == 0 ? 'alternate' : ''),
				$this->notificationsDAO->getAllActions()[$notification->getAction()],
				$this->notificationsDAO->getAllFrequencies()[$notification->getFrequency()],
				$notification->getDetails()['recipientEmail'],
				$notification->getDetails()['subject'],
				$editLink,
				$deleteLink
			);
			$html .= sprintf(
				'<tr id="editNotification%s" class="%s" style="display: none"><td colspan="5">%s</td></tr>',
				$notification->getId(),
				($key % 2 == 0 ? 'alternate' : ''),
				$this->getNotificationForm($notification)
			);
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
	 * @param Notification $notification
	 * @return string HTML form
	 */
	private function getNotificationForm($notification) {
		$details = $notification !== null ? $notification->getDetails() : array();
		$currentUser = wp_get_current_user();
		$url = $notification !== null
				? admin_url("options-general.php?page=".Settings::MENU_SLUG."&wc_action=editNotification&notificationId=".$notification->getId().'&nonce='.wp_create_nonce('editNotification'))
				: admin_url("options-general.php?page=".Settings::MENU_SLUG."&wc_action=addNotification&nonce=".wp_create_nonce('addNotification'));

		// actions:
		$actionsHtmlOptions = '';
		foreach ($this->notificationsDAO->getAllActions() as $key => $option) {
			$actionsHtmlOptions .= sprintf('<option value="%s" %s>%s</option>', $key, $notification !== null && $notification->getAction() == $key ? 'selected' : '', $option);
		}

		// frequencies:
		$frequenciesHtmlOptions = '';
		foreach ($this->notificationsDAO->getAllFrequencies() as $key => $option) {
			$frequenciesHtmlOptions .= sprintf('<option value="%s" %s>%s</option>', $key, $notification !== null && $notification->getFrequency() == $key ? 'selected' : '', $option);
		}

		$recipient = $notification !== null
			? (array_key_exists('recipientEmail', $details) ? $details['recipientEmail'] : '')
			: ($currentUser instanceof \WP_User ? $currentUser->user_email : '');

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
						<select disabled id="notificationAction">%s</select>
						<p class="description" style="display: inline;"></p>
					</td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">No more than:</td>'.
					'<td>
						<label><select disabled id="notificationFrequency">%s</select></label>
					</td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">E-mail:</td>'.
					'<td><input type="email" value="%s" disabled placeholder="E-mail" id="notificationRecipientEmail" style="width: 100%%;" /></td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">Subject:</td>'.
					'<td><input type="text" disabled value="%s" placeholder="Subject" id="notificationSubject" style="width: 100%%;" /></td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">Content:</td>'.
					'<td>
						<textarea disabled placeholder="Content" id="notificationContent" rows="10" style="width: 100%%;">%s</textarea>
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
	 * @param UserNotification $notification
	 * @return string HTML form
	 */
	private function getUserNotificationForm($notification) {
		$details = $notification !== null ? $notification->getDetails() : array();

		// frequencies:
		$frequenciesHtmlOptions = '';
		foreach ($this->userNotificationsDAO->getAllFrequencies() as $key => $option) {
			$frequenciesHtmlOptions .= sprintf('<option value="%s" %s>%s</option>', $key, $notification !== null && $notification->getFrequency() == $key ? 'selected' : '', $option);
		}

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
						<label><select disabled id="userNotificationFrequency">%s</select></label> from each user separately
					</td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">Subject:</td>'.
					'<td><input type="text" disabled value="%s" placeholder="Subject" id="userNotificationSubject" style="width: 100%%;" /></td>'.
				'</tr>'.
				'<tr>'.
					'<td class="th-full">Content:</td>'.
					'<td>
						<textarea disabled placeholder="Content" id="userNotificationContent" rows="10" style="width: 100%%;">%s</textarea>
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
		$url = admin_url("options-general.php?page=".Settings::MENU_SLUG);

		$notifications = $this->userNotificationsDAO->getAll();

		$html = "<table class='wp-list-table widefat'>";
		if (count($notifications) == 0) {
			$html .= '<tr><td>No user notifications created yet</td></tr>';
		} else {
			$html .= '<thead><tr><th>No more than</th><th>Subject</th><th></th></tr></thead>';
		}

		foreach ($notifications as $key => $notification) {
			$deleteURL = $url.'&wc_action=deleteUserNotification&id='.$notification->getId().'&wc_tab=notifications';
			$editLink = '<a href="javascript://" title="Edit user notification" onclick="jQuery(\'#editUserNotification'.$notification->getId().'\').toggle()">Edit</a>';
			$deleteLink = "<a href='{$deleteURL}' title='Delete user notification' onclick='return confirm(\"Are you sure you want to delete this notification?\")'>Delete</a>";

			$html .= sprintf(
				'<tr class="%s"><td>%s</td><td>%s</td><td>%s | %s</td></tr>',
				($key % 2 == 0 ? 'alternate' : ''),
				$this->userNotificationsDAO->getAllFrequencies()[$notification->getFrequency()],
				$notification->getDetails()['subject'],
				$editLink,
				$deleteLink
			);
			$html .= sprintf(
				'<tr id="editUserNotification%s" class="%s" style="display: none"><td colspan="5">%s</td></tr>',
				$notification->getId(),
				($key % 2 == 0 ? 'alternate' : ''),
				$this->getUserNotificationForm($notification)
			);
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