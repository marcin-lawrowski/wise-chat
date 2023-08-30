<?php

/**
 * Wise Chat video streams.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatVideoStreamsTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Main Settings', 'Video calls are available to online users only. Please enable private messages, create Twilio account and put the keys below.'),
			array('video_calls_enabled', 'Enable Video Calls', 'booleanFieldCallback', 'boolean'),
			array('video_calls_calling_timeout', 'Calling Timeout', 'stringFieldCallback', 'integer', 'Number of seconds to wait for picking up the call.'),
			array('video_calls_calling_sound_enabled', 'Calling Sound', 'booleanFieldCallback', 'boolean'),
			array('video_calls_incoming_call_sound_enabled', 'Incoming Call Sound', 'booleanFieldCallback', 'boolean'),

			array('_section', 'Twilio Account', 'Register <a href="https://www.twilio.com/">Twilio</a> account and generate connection keys.'),
			array('twilio_account_sid', 'Account SID', 'stringFieldCallback', 'string'),
			array('twilio_api_key_sid', 'API Key SID', 'stringFieldCallback', 'string'),
			array('twilio_api_key_secret', 'API Key Secret', 'stringFieldCallback', 'string'),

			array('_section', 'Twilio Room Settings'),
			array('twilio_video_room_media_region', 'Media Region', 'selectCallback', 'string', 'Please choose the region close to your users. Read more <a href="https://www.twilio.com/docs/video/ip-addresses#media-servers" target="_blank">here</a>', self::getMediaRegions()),
		);
	}


	public function getDefaultValues() {
		return array(
			'video_calls_enabled' => 0,
			'video_calls_calling_timeout' => 25,
			'twilio_account_sid' => '',
			'twilio_api_key_sid' => '',
			'twilio_api_key_secret' => '',
			'video_calls_calling_sound_enabled' => 1,
			'video_calls_incoming_call_sound_enabled' => 1,
			'twilio_video_room_media_region' => 'de1'
		);
	}

	public function getProFields() {
		return array('video_calls_enabled', 'video_calls_calling_timeout', 'twilio_account_sid', 'twilio_api_key_sid', 'twilio_api_key_secret', 'video_calls_calling_sound_enabled', 'video_calls_incoming_call_sound_enabled', 'twilio_video_room_media_region');
	}

	public function getParentFields() {
		return array(
		    'twilio_account_sid' => 'video_calls_enabled',
			'twilio_api_key_sid' => 'video_calls_enabled',
			'twilio_api_key_secret' => 'video_calls_enabled',
			'video_calls_calling_timeout' => 'video_calls_enabled',
			'video_calls_calling_sound_enabled' => 'video_calls_enabled',
			'video_calls_incoming_call_sound_enabled' => 'video_calls_enabled',
			'twilio_video_room_media_region' => 'video_calls_enabled',
		);
	}

	private static function getMediaRegions() {
		return array(
			'au1' => 'Australia',
			'br1' => 'Brazil',
			'de1' => 'Germany',
			'ie1' => 'Ireland',
			'in1' => 'India',
			'jp1' => 'Japan',
			'sg1' => 'Singapore',
			'us1' => 'US East Coast (Virginia)',
			'us2' => 'US West Coast (Oregon)',
		);
	}

}