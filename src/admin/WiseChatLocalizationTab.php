<?php 

/**
 * Wise Chat admin localization settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatLocalizationTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Localization Settings'),
			array(
				'hint_message', 'Hint Message', 'stringFieldCallback', 'string',
				'A hint message displayed in the message input field'
			),
			array(
				'user_name_prefix', 'User Name Prefix', 'stringFieldCallback', 'string',
				'Anonymous user\'s name prefix'
			),
			array(
				'message_submit_button_caption', 'Submit Button Caption', 'stringFieldCallback', 'string',
				'Caption for message submit button'
			),
			array('window_title', 'Window Title', 'stringFieldCallback', 'string', 'Title of the messages window'),
			array('message_save', '"Save" message', 'stringFieldCallback', 'string'),
			array('message_reset', '"Reset" message', 'stringFieldCallback', 'string'),
			array('message_name', '"Name" message', 'stringFieldCallback', 'string'),
			array('message_customize', '"Customize" message', 'stringFieldCallback', 'string'),
			array('message_sending', '"Sending" message', 'stringFieldCallback', 'string'),
			array('message_mute_sounds', '"Mute sounds" message', 'stringFieldCallback', 'string'),
			array('message_text_color', '"Text color" message', 'stringFieldCallback', 'string'),
			array('message_total_users', '"Total users" message', 'stringFieldCallback', 'string'),
			array('message_sec_ago', '"sec. ago" message', 'stringFieldCallback', 'string'),
			array('message_min_ago', '"min. ago" message', 'stringFieldCallback', 'string'),
			array('message_yesterday', '"yesterday" message', 'stringFieldCallback', 'string'),
            array('message_insert_emoticon', '"Insert an emoticon" message', 'stringFieldCallback', 'string'),
            array('message_insert_into_message', '"Insert into message" message', 'stringFieldCallback', 'string'),
			array('message_picture_upload_hint', '"Upload a picture" message', 'stringFieldCallback', 'string'),
			array('message_attach_file_hint', '"Attach a file" message', 'stringFieldCallback', 'string'),
			array('message_channel_password_authorization_hint', 'Channel Authorization Hint', 'stringFieldCallback', 'string'),
			array('message_login', '"Log in" message', 'stringFieldCallback', 'string'),
			array('message_enter_user_name', '"Enter your username" message', 'stringFieldCallback', 'string'),
			array('message_input_title', 'Message input hint', 'stringFieldCallback', 'string'),
			array('message_has_left_the_channel', 'has left the channel', 'stringFieldCallback', 'string'),
			array('message_has_joined_the_channel', 'has joined the channel', 'stringFieldCallback', 'string'),
			array('message_users_list_empty', '"No users in the channel" message', 'stringFieldCallback', 'string'),

			array('message_error_1', 'Message error #1', 'stringFieldCallback', 'string', 'Message: "Only letters, number, spaces, hyphens and underscores are allowed"'),
			array('message_error_2', 'Message error #2', 'stringFieldCallback', 'string', 'Message: "This name is already occupied"'),
			array('message_error_3', 'Message error #3', 'stringFieldCallback', 'string', 'Message: "You were banned from posting messages"'),
			array('message_error_4', 'Message error #4', 'stringFieldCallback', 'string', 'Message: "Only logged in users are allowed to enter the chat"'),
			array('message_error_5', 'Message error #5', 'stringFieldCallback', 'string', 'Message: "The chat is closed now"'),
			array('message_error_6', 'Message error #6', 'stringFieldCallback', 'string', 'Message: "The chat is full now. Try again later."'),
			array('message_error_7', 'Message error #7', 'stringFieldCallback', 'string', 'Message: "Unsupported type of file."'),
			array('message_error_8', 'Message error #8', 'stringFieldCallback', 'string', 'Message: "The size of the file exceeds allowed limit."'),
			array('message_error_9', 'Message error #9', 'stringFieldCallback', 'string', 'Message: "Invalid password."'),
			array('message_error_10', 'Message error #10', 'stringFieldCallback', 'string', 'Message: "You cannot enter the chat due to the limit of channels you can participate simultaneously."'),
			array('message_error_11', 'Message error #11', 'stringFieldCallback', 'string', 'Message: "You are not allowed to enter the chat."'),
			array('message_error_12', 'Message error #12', 'stringFieldCallback', 'string', 'Message: "You are blocked from using the chat."'),
			
			array('message_text_1', 'Message text #1', 'stringFieldCallback', 'string', 'Message: "Are you sure you want to report the message as spam?"'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'hint_message' => 'Enter message here',
			'user_name_prefix' => 'Anonymous',
			'message_submit_button_caption' => 'Send',
			'message_save' => 'Save',
			'message_reset' => 'Reset',
			'message_name' => 'Name',
			'message_customize' => 'Customize',
			'message_sending' => 'Sending ...',
			'message_error_1' => 'Only letters, number, spaces, hyphens and underscores are allowed',
			'message_error_2' => 'This name is already occupied',
			'message_error_3' => 'You were banned from posting messages',
			'message_error_4' => 'Only logged in users are allowed to enter the chat',
			'message_error_5' => 'The chat is closed now',
			'message_error_6' => 'The chat is full now. Try again later.',
			'message_error_7' => 'Unsupported type of file.',
			'message_error_8' => 'The size of the file exceeds allowed limit.',
			'message_error_9' => 'Invalid password.',
			'message_error_10' => 'You cannot enter the chat due to the limit of channels you can participate simultaneously.',
			'message_error_11' => 'You are not allowed to enter the chat.',
			'message_error_12' => 'You are blocked from using the chat.',
			'message_text_1' => 'Are you sure you want to report the message as spam?',
			'window_title' => 'Wise Chat',
			'message_mute_sounds' => 'Mute sounds',
			'message_text_color' => 'Text color',
			'message_total_users' => 'Total users',
			'message_sec_ago' => 'sec. ago',
			'message_min_ago' => 'min. ago',
			'message_yesterday' => 'yesterday',
            'message_insert_emoticon' => 'Insert an emoticon',
            'message_insert_into_message' => 'Insert into message',
			'message_picture_upload_hint' => 'Upload a picture',
			'message_attach_file_hint' => 'Attach a file',
			'message_channel_password_authorization_hint' => 'This channel is protected. Enter your password:',
			'message_login' => 'Log in',
			'message_enter_user_name' => 'Enter your username',
			'message_input_title' => 'Use Shift+ENTER in order to move to the next line',
			'message_has_left_the_channel' => 'has left the channel',
			'message_has_joined_the_channel' => 'has joined the channel',
			'message_users_list_empty' => 'No users in the channel',
		);
	}
}