<?php 

/**
 * Wise Chat admin messages settings tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatMessagesTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'General Settings'),
			array('message_max_length', 'Message Maximum Length', 'stringFieldCallback', 'integer', 'Maximum length of a message sent by an user'),
			array('allow_post_links', 'Enable Links', 'booleanFieldCallback', 'boolean', 'Makes posted links clickable'),
			array('enable_twitter_hashtags', 'Enable Twitter Hashtags', 'booleanFieldCallback', 'boolean', 'Detects Twitter hashtags and converts them to links'),

			array(
				'_section', 'Users Notifications',
				'Configure various notifications in reaction to certain events such as new messages, new users, etc. You can give users an option to mute all sounds. Please check the appearance settings. '
			),
			array('enable_title_notifications', 'Window Title', 'booleanFieldCallback', 'boolean', 'Shows the asterisk symbol in the browser\'s window title when a new message arrives and the window is minimized or inactive'),
			array('sound_notification', 'New Message Sound', 'selectCallback', 'string', 'Plays a sound when new messages arrive.', WiseChatMessagesTab::getNotificationSounds()),
			array('enable_join_notification', 'User Online Highlight', 'booleanFieldCallback', 'boolean', 'When user becomes online its name is highlighted on the users list.'),
			array('join_sound_notification', 'User Online Sound', 'selectCallback', 'string', 'Plays a sound when user becomes online.', WiseChatMessagesTab::getNotificationSounds()),
			array('enable_leave_notification', 'User Offline Highlight', 'booleanFieldCallback', 'boolean', 'When user becomes offline its name is highlighted on the users list.'),
			array('leave_sound_notification', 'User Offline Sound', 'selectCallback', 'string', 'Plays a sound when user becomes offline.', WiseChatMessagesTab::getNotificationSounds()),
			array('mentioning_sound_notification', 'Mentioning Sound', 'selectCallback', 'string', 'Plays a sound when user is mentioned using <i>@UserName:</i> notation.', WiseChatMessagesTab::getNotificationSounds()),

			array('_section', 'Images Settings'),
			array('allow_post_images', 'Enable Images', 'booleanFieldCallback', 'boolean', 'Downloads posted images (links pointing to images) into Media Library and displays them'),
			array('enable_images_uploader', 'Enable Uploader', 'booleanFieldCallback', 'boolean', 'Enables the uploader for sending pictures either from local storage or from a camera (on a mobile device). <br />In order to see sent pictures "Enable Images" option has to be enabled'),
			array('images_size_limit', 'Size Limit', 'stringFieldCallback', 'integer', 'Size limit (in bytes) of images that are posted by users'),
			array('images_width_limit', 'Maximum Width', 'stringFieldCallback', 'integer', 'Resize images to the declared width'),
			array('images_height_limit', 'Maximum Height', 'stringFieldCallback', 'integer', 'Resize images to the declared height'),
			array('images_thumbnail_width_limit', 'Thumbnails Maximum Width', 'stringFieldCallback', 'integer', 'Maximum width of the generated thumbnail'),
			array('images_thumbnail_height_limit', 'Thumbnails Maximum Height', 'stringFieldCallback', 'integer', 'Maximum height of the generated thumbnail'),
			
			array('_section', 'Voice Messages Settings', 'Option to record and post voice messages. It requires HTTPS. All sounds are compressed to Mp3 files and stored in Media Library.'),
			array('enable_voice_messages', 'Enable', 'booleanFieldCallback', 'boolean', 'Enables voice messages.'),
			array('voice_message_max_length', 'Maximum Length (s)', 'stringFieldCallback', 'integer', 'Maximal length of a voice message (in seconds). Allowed range: 1 - 300.'),
			array('voice_message_mp3_bitrate', 'MP3 bit rate', 'stringFieldCallback', 'integer', 'Bit rate of voice messages. Allowed range: 64 - 320.'),

			array('_section', 'File Attachments Settings'),
			array('enable_attachments_uploader', 'Enable Uploader', 'booleanFieldCallback', 'boolean', 'Enables the uploader for sending file attachments from local storage. You can specify allowed file formats below'),
			array('attachments_file_formats', 'Allowed File Extensions', 'stringFieldCallback', 'string', 'Comma-separated list of allowed extensions'),
			array('attachments_size_limit', 'Size Limit', 'stringFieldCallback', 'integer', 'Size limit (in bytes) of attachments that are posted by users'),
			
			array('_section', 'YouTube Videos Settings'),
			array('enable_youtube', 'Enable YouTube Videos', 'booleanFieldCallback', 'boolean', 'Detects YouTube links and converts them to video players'),
			array('youtube_width', 'Player Width', 'stringFieldCallback', 'integer', 'Width of the video player'),
			array('youtube_height', 'Player Height', 'stringFieldCallback', 'integer', 'Height of the video player')
		);
	}
	
	public function getDefaultValues() {
		return array(
			'enable_title_notifications' => 0,
			'enable_join_notification' => 1,
			'join_sound_notification' => '',
			'enable_leave_notification' => 1,
			'leave_sound_notification' => '',
			'mention_sound_notification' => '',
			'sound_notification' => '',
			'message_max_length' => 400,
			'allow_post_links' => 1,
			'allow_post_images' => 1,
			'enable_images_uploader' => 1,
			'enable_twitter_hashtags' => 1,
			'enable_attachments_uploader' => 1,
			'attachments_file_formats' => 'pdf,doc,docx',
			'attachments_size_limit' => 3145728,

			'enable_voice_messages' => false,
			'voice_message_max_length' => 60,
			'voice_message_mp3_bitrate' => 160,

			'images_size_limit' => 3145728,
			'images_width_limit' => 1000,
			'images_height_limit' => 1000,
			'images_thumbnail_width_limit' => 60,
			'images_thumbnail_height_limit' => 60,
			
			'enable_youtube' => 1,
			'youtube_width' => 186,
			'youtube_height' => 105
		);
	}

	public function getProFields() {
        return array(
            'enable_voice_messages', 'voice_message_max_length', 'voice_message_mp3_bitrate'
        );
    }
	
	public function getParentFields() {
		return array(
			'attachments_file_formats' => 'enable_attachments_uploader',
			'attachments_size_limit' => 'enable_attachments_uploader',
			'youtube_width' => 'enable_youtube',
			'youtube_height' => 'enable_youtube',

			'voice_message_max_length' => 'enable_voice_messages',
			'voice_message_mp3_bitrate' => 'enable_voice_messages',
		);
	}
	
	public static function getNotificationSounds() {
		return array(
			'' => 'Disabled',
			'sound-01' => 'Legacy Sound 1',
			'sound-02' => 'Legacy Sound 2',
			'sound-03' => 'Legacy Sound 3',
			'sound-04' => 'Legacy Sound 4',
			'wise-chat-01' => 'Wise Chat 1',
			'wise-chat-02' => 'Wise Chat 2',
			'wise-chat-03' => 'Wise Chat 3',
			'wise-chat-04' => 'Wise Chat 4',
			'wise-chat-05' => 'Wise Chat 5',
			'wise-chat-06' => 'Wise Chat 6',
			'wise-chat-07' => 'Wise Chat 7',
			'wise-chat-08' => 'Wise Chat 8',
			'wise-chat-09' => 'Wise Chat 9',
			'wise-chat-10' => 'Wise Chat 10',
			'wise-chat-11' => 'Wise Chat 11',
			'wise-chat-12' => 'Wise Chat 12',
			'wise-chat-13' => 'Wise Chat 13',
			'wise-chat-14' => 'Wise Chat 14',
			'wise-chat-15' => 'Wise Chat 15',
			'wise-chat-16' => 'Wise Chat 16',
			'wise-chat-17' => 'Wise Chat 17',
			'wise-chat-18' => 'Wise Chat 18',
			'wise-chat-19' => 'Wise Chat 19',
			'wise-chat-20' => 'Wise Chat 20',
		);
	}
}