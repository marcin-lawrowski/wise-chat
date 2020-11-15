<?php 

/**
 * Wise Chat admin moderation settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatModerationTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Moderation Settings'),
			array('enable_message_actions', 'Enable Admin Actions', 'booleanFieldCallback', 'boolean', 'Displays ban and removal buttons next to each message. The buttons are visible only for roles defined below'),
			array(
				'permission_edit_message_role', 'Edit Message Permission', 'checkboxesCallback', 'multivalues',
				'User roles allowed to edit posted messages.<br /> Alternatively you can assign "wise_chat_edit_message" capability to any custom role.', self::getRoles()
			),
			array(
				'permission_edit_message_role', 'Edit Message Permission', 'checkboxesCallback', 'multivalues',
				'An user role that is allowed to edit posted messages.<br /> Alternatively you can assign "wise_chat_edit_message" capability to any custom role.', self::getRoles()
			),
			array(
				'permission_delete_message_role', 'Delete Message Permission', 'checkboxesCallback', 'multivalues',
				'User roles allowed to delete posted messages.<br /> Alternatively you can assign "wise_chat_delete_message" capability to any custom role.', self::getRoles()
			),
			array(
				'permission_ban_user_role', 'Ban User Permission', 'checkboxesCallback', 'multivalues',
				'User roles allowed to ban users.<br /> Alternatively you can assign "wise_chat_ban_user" capability to any custom role.', self::getRoles()
			),
			array(
				'permission_kick_user_role', 'Kick User Permission', 'checkboxesCallback', 'multivalues',
				'User roles allowed to kick users.<br /> Alternatively you can assign "wise_chat_kick_user" capability to any custom role.', self::getRoles()
			),
			array(
				'permission_approve_message_role', 'Approve Message Permission', 'checkboxesCallback', 'multivalues',
				'User roles allowed to approve posted messages.<br />Alternatively you can assign "wise_chat_approve_message" capability to any custom role.<br />',
				self::getRoles()
			),
			array('enable_approval_confirmation', 'Approval Confirmation', 'booleanFieldCallback', 'boolean',
				'Displays confirmation after clicking approval button.'
			),
			array('moderation_ban_duration', 'Ban Duration', 'stringFieldCallback', 'integer', 'Duration of the ban (in minutes) created by clicking on Ban button next a message. Empty field sets the value to 1440 minutes (1 day)'),

			array('_section', 'Spam Reporting',
				'Configuration of the spam reporting feature. It displays spam reporting button next to the posted message.'
			),
			array(
				'spam_report_enable_all', 'Enable All', 'booleanFieldCallback', 'boolean',
				'Enables spam reporting for all - including anonymous users.'
			),
			array(
				'permission_spam_report_role', 'Enable Roles Only', 'checkboxesCallback', 'multivalues',
				'User roles allowed to report spam messages.<br /> Alternatively you can assign "wise_chat_spam_report" capability to any custom role.', self::getRoles()
			),

			array('_section', 'Spam Reporting Notification',
				'Notification e-mail sent to admin when Report Spam button is clicked.'
			),
			array('spam_report_recipient', 'Recipient', 'stringFieldCallback', 'string'),
			array('spam_report_subject', 'Subject', 'stringFieldCallback', 'string'),
			array('spam_report_content', 'Content', 'multilineFieldCallback', 'multilinestring', 'Available variables: {url}, {channel}, {message}, {message-user}, {message-user-ip}, {report-user}, {report-user-ip}'),

			array('_section', 'Pending Messages',
				'After enabling this feature all posted messages are hidden until they are manually approved using Approve button (enable corresponding moderation permissions in the section above).'
			),
			array(
				'new_messages_hidden', 'Enable', 'booleanFieldCallback', 'boolean',
				'All new messages are hidden. They will become visible as soon as they are manually approved.'
			),
			array(
				'show_hidden_messages_roles', 'Show Hidden Messages For', 'checkboxesCallback', 'multivalues',
				'Shows hidden messages for selected user roles.', self::getRoles()
			),
			array(
				'no_hidden_messages_roles', 'Don\'t Hide Messages For', 'checkboxesCallback', 'multivalues',
				'Prevents from hiding messages for selected user roles.', self::getRoles()
			),
			array(
				'approving_messages_mode', 'Approving Messages Mode', 'selectCallback', 'string',
				'Determines what date and time to set for hidden messages that have been approved.', self::getPendingMessagesApprovalModes()
			),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'enable_message_actions' => 1,
			'permission_delete_message_role' => 'administrator',
			'permission_ban_user_role' => 'administrator',
			'permission_kick_user_role' => 'administrator',
			'moderation_ban_duration' => 1440,
			'spam_report_enable_all' => 1,
			'permission_spam_report_role' => 'administrator',
			'spam_report_recipient' => get_option('admin_email'),
			'spam_report_subject' => '[Wise Chat] Spam Report',
			'spam_report_content' => "Wise Chat Spam Report\n\n".
				'Channel: {channel}'."\n".
				'Message: {message}'."\n".
				'Posted by: {message-user}'."\n".
				'Posted from IP: {message-user-ip}'."\n\n".
				"--\n".
				'This e-mail was sent by {report-user} from {url}'."\n".
				'{report-user-ip}'
		);
	}
	
	public function getParentFields() {
		return array(
			'permission_delete_message_role' => 'enable_message_actions',
			'permission_ban_user_role' => 'enable_message_actions',
			'permission_kick_user_role' => 'enable_message_actions',
			'moderation_ban_duration' => 'enable_message_actions'
		);
	}

	public function getProFields() {
		return array(
			'new_messages_hidden', 'show_hidden_messages_roles', 'no_hidden_messages_roles', 'approving_messages_mode',
			'permission_edit_message_role', 'permission_approve_message_role', 'enable_approval_confirmation',
			'permission_edit_message_role'
		);
	}

	public function getPendingMessagesApprovalModes() {
		return array(
			1 => 'Date and time of the message',
			2 => 'Date and time of the approval'
		);
	}
	
	public function getRoles() {
		$editableRoles = array_reverse(get_editable_roles());
		$rolesOptions = array();

		foreach ($editableRoles as $role => $details) {
			$name = translate_user_role($details['name']);
			$rolesOptions[esc_attr($role)] = $name;
		}
	
		return $rolesOptions;
	}

}