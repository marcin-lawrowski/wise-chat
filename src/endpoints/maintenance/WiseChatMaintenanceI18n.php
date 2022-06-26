<?php

/**
 * Class WiseChatMaintenanceI18n
 *
 * Adds i18n translations table to the maintenance endpoint.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMaintenanceI18n {

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}

	/**
	 * Returns translations required for later displaying.
	 *
	 * @return array
	 */
	public function getTranslations() {
		return array(
			'unsupportedTypeOfFile' => $this->options->getOption('message_error_7', __('Unsupported type of file.', 'wise-chat')),
			'sizeLimitError' => $this->options->getOption('message_error_8', __('The size of the file exceeds allowed limit.', 'wise-chat')),
			'close' => $this->options->getOption('message_close', __('Close', 'wise-chat')),
			'ok' => $this->options->getOption('message_ok', __('OK', 'wise-chat')),
			'yes' => $this->options->getOption('message_yes', __('Yes', 'wise-chat')),
			'no' => $this->options->getOption('message_no', __('No', 'wise-chat')),
			'error' => $this->options->getOption('message_error', __('Error', 'wise-chat')),
			'information' => $this->options->getOption('message_information', __('Information', 'wise-chat')),
			'confirmation' => $this->options->getOption('message_confirmation', __('Confirmation', 'wise-chat')),
			'enterYourUsername' => $this->options->getOption('message_enter_user_name', __('Enter your username', 'wise-chat')),
			'enterPassword' => $this->options->getOption('message_enter_password', __('Enter password', 'wise-chat')),
			'name' => $this->options->getOption('message_name', __('Name', 'wise-chat')),
			'save' => $this->options->getOption('message_save', __('Save', 'wise-chat')),
			'reset' => $this->options->getOption('message_reset', __('Reset', 'wise-chat')),
			'muteSounds' => $this->options->getOption('message_mute_sounds', __('Mute sounds', 'wise-chat')),
			'textColor' => $this->options->getOption('message_text_color', __('Text color', 'wise-chat')),
			'uploadPicture' => $this->options->getOption('message_picture_upload_hint', __('Upload a picture', 'wise-chat')),
			'attachFile' => $this->options->getOption('message_attach_file_hint', __('Attach a file', 'wise-chat')),
			'insertEmoticon' => $this->options->getOption('message_insert_emoticon', __('Insert an emoticon', 'wise-chat')),
			'messageInputTitle' => $this->options->getOption('message_input_title', __('Use Shift+ENTER in order to move to the next line.', 'wise-chat')),
			'deleteMessage' => $this->options->getOption('message_delete_message', __('Delete the message', 'wise-chat')),
			'banThisUser' => $this->options->getOption('message_ban_this_user', __('Ban this user', 'wise-chat')),
			'muteThisUser' => $this->options->getOption('message_mute_this_user', __('Mute this user', 'wise-chat')),
			'reportSpam' => $this->options->getOption('message_report_spam', __('Report spam', 'wise-chat')),
			'deleteConfirmation' => $this->options->getOption('message_delete_confirmation', __('Are you sure you want to delete this message?', 'wise-chat')),
			'banConfirmation' => $this->options->getOption('message_ban_confirmation', __('Are you sure you want to ban this user?', 'wise-chat')),
			'banConfirmed' => $this->options->getOption('message_user_banned', __('The user has been banned.', 'wise-chat')),
			'muteConfirmation' => $this->options->getOption('message_mute_confirmation', __('Are you sure you want to mute this user?', 'wise-chat')),
			'muteConfirmed' => $this->options->getOption('message_user_muted', __('The user has been muted.', 'wise-chat')),
			'spamReportConfirmation' => $this->options->getOption('message_text_1', __('Are you sure you want to report the message as spam?', 'wise-chat')),
			'spamReportConfirmed' => $this->options->getOption('message_spam_reported', __('Thank you for reporting this.', 'wise-chat')),
			'subChannelsSearchHint' => $this->options->getOption('users_list_search_hint', __('Search ...', 'wise-chat')),
			'savedSettings' => $this->options->getOption('message_saved_settings', __('Settings have been saved.', 'wise-chat')),
			'maximize' => $this->options->getOption('message_maximize', __('Maximize', 'wise-chat')),
			'minimize' => $this->options->getOption('message_minimize', __('Minimize', 'wise-chat')),
			'chatFull' => $this->options->getOption('message_error_6', __('The chat is full now. Try again later.', 'wise-chat')),
			'onlineUsers' => $this->options->getOption('message_online_users', __('Online users', 'wise-chat')),
		);
	}

}