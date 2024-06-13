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
			'messageSearch' => $this->options->getOption('message_search', __('Search ...', 'wise-chat')),
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
			'enableNotifications' => $this->options->getOption('message_enable_notifications', __('Enable E-mail Notifications', 'wise-chat')),
			'textColor' => $this->options->getOption('message_text_color', __('Text color', 'wise-chat')),
			'uploadPicture' => $this->options->getOption('message_picture_upload_hint', __('Upload a picture', 'wise-chat')),
			'attachFile' => $this->options->getOption('message_attach_file_hint', __('Attach a file', 'wise-chat')),
			'insertEmoticon' => $this->options->getOption('message_insert_emoticon', __('Insert an emoticon', 'wise-chat')),
			'messageInputTitle' => $this->options->getOption('message_input_title', __('Use Shift+ENTER in order to move to the next line.', 'wise-chat')),
			'approveMessage' => $this->options->getOption('message_approve_message', __('Approve the message', 'wise-chat')),
			'deleteMessage' => $this->options->getOption('message_delete_message', __('Delete the message', 'wise-chat')),
			'editMessage' => $this->options->getOption('message_edit_message', __('Edit the message', 'wise-chat')),
			'replyToMessage' => $this->options->getOption('message_reply_to_message', __('Reply to', 'wise-chat')),
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
			'approveConfirmation' => $this->options->getOption('message_approve_confirmation', __('Are you sure you want to approve this message?', 'wise-chat')),
			'directChannelCloseConfirmation' => $this->options->getOption('message_direct_channel_close_confirmation', __('Are you sure you want to leave this conversation?', 'wise-chat')),
			'approvalWarning' => $this->options->getOption('message_info_3', __('The message has been posted, but first it must be approved by the administrator.', 'wise-chat')),
			'replyingTo' => $this->options->getOption('message_replying_to_message', __('Replying to', 'wise-chat')),
			'subChannelsSearchHint' => $this->options->getOption('users_list_search_hint', __('Search ...', 'wise-chat')),
			'incomingAskApproval' => $this->options->getOption('message_info_2', __('invites you to the private chat. Do you accept it?', 'wise-chat')),
			'ignoreUser' => $this->options->getOption('message_ignore_user', __('Ignore this user', 'wise-chat')),
			'ignoredInfo' => $this->options->getOption('message_info_1', __('This user is ignored by you. Would you like to stop ignoring this user?', 'wise-chat')),
			'noRecentChats' => $this->options->getOption('message_no_recent_chats', __('No recent chats', 'wise-chat')),
			'notAllowedToSendDirectMessages' => $this->options->getOption('message_error_14', __('You are not allowed to send private messages to this user.', 'wise-chat')),
			'savedSettings' => $this->options->getOption('message_saved_settings', __('Settings have been saved.', 'wise-chat')),
			'maximize' => $this->options->getOption('message_maximize', __('Maximize', 'wise-chat')),
			'minimize' => $this->options->getOption('message_minimize', __('Minimize', 'wise-chat')),
			'chatFull' => $this->options->getOption('message_error_6', __('The chat is full now. Try again later.', 'wise-chat')),
			'chatsArchive' => $this->options->getOption('message_chats_archive', __('Chats archive', 'wise-chat')),
			'openChats' => $this->options->getOption('message_open_chats', __('Open chats', 'wise-chat')),
			'unreadChats' => $this->options->getOption('message_unread_chats', __('Unread chats', 'wise-chat')),
			'unreadMessages' => $this->options->getOption('message_unread_messages', __('Unread Messages', 'wise-chat')),
			'messagesArchive' => $this->options->getOption('message_messages_archive', __('Messages Archive', 'wise-chat')),
			'messageLengthExceeds' => $this->options->getOption('message_error_13', __('The length of the message exceeds allowed limit.', 'wise-chat')),
			'userNotFound' => $this->options->getOption('message_user_not_found_in_chat', __('The user is not in the chat', 'wise-chat')),
			'onlineUsers' => $this->options->getOption('message_online_users', __('Online users', 'wise-chat')),
			'attachingVoiceMessage' => $this->options->getOption('message_attaching_voice_message', __('Attaching voice message', 'wise-chat')),
			'noMicrophoneError' => $this->options->getOption('message_error_16', __('Could not initialize the microphone. Please set your web browser to allow for sound recording.', 'wise-chat')),

			'incomingVideoStream' => $this->options->getOption('message_incoming_video_stream', __('Incoming video call', 'wise-chat')),
			'videoCall' => $this->options->getOption('message_video_call', __('Video call', 'wise-chat')),
			'directChannelWithStreamCloseConfirmation' => $this->options->getOption('message_direct_channel_stream_close_confirmation', __('There is an active video stream in this channel. Are you sure you want to end the call?', 'wise-chat')),
			'makeVideoCall' => $this->options->getOption('message_make_video_call', __('Make a video call', 'wise-chat')),
			'callWasNotAnswered' => $this->options->getOption('message_call_was_not_answered', __('The call was not answered', 'wise-chat')),
			'callWasEndedRemote' => $this->options->getOption('message_call_was_ended_remote', __('The call was ended by the remote participant', 'wise-chat')),
			'callWasEnded' => $this->options->getOption('message_call_was_ended', __('The call was ended', 'wise-chat')),
			'calling' => $this->options->getOption('message_calling', __('Calling ...', 'wise-chat')),
			'endCall' => $this->options->getOption('message_end_call', __('End the call', 'wise-chat')),
			'videoCallAccept' => $this->options->getOption('message_video_accept', __('Accept', 'wise-chat')),
			'videoCallDecline' => $this->options->getOption('message_video_decline', __('Decline', 'wise-chat')),
			'audioMute' => $this->options->getOption('message_audio_mute', __('Mute', 'wise-chat')),
			'audioUnmute' => $this->options->getOption('message_audio_unmute', __('Unmute', 'wise-chat')),
			'videoMute' => $this->options->getOption('message_video_mute', __('Camera off', 'wise-chat')),
			'videoUnmute' => $this->options->getOption('message_video_unmute', __('Camera on', 'wise-chat')),
			'switchToChat' => $this->options->getOption('message_switch_to_chat', __('Switch to chat', 'wise-chat')),
			'switchToVideoCall' => $this->options->getOption('message_switch_to_video_call', __('Switch to video call', 'wise-chat')),
			'oneStreamWarning' => $this->options->getOption('message_one_stream_warning', __('You can open only one video stream at time', 'wise-chat')),
		);
	}

}