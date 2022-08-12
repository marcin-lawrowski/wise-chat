<?php

/**
 * Wise Chat admin modes settings..
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatModesTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Classic Mode Settings', 'These settings apply when the classic chat mode is enabled.'),
			array('classic_disable_channel', 'Disable Public Channels', 'booleanFieldCallback', 'boolean',
				'Disables all public channels. Only private chats are possible.<br />
				<strong>Notice:</strong> This option will take effect only if <a href="https://kainex.pl/projects/wp-plugins/wise-chat-pro/documentation/features/private-messages/">private messages</a> are enabled.'
			),
			array('chat_width', 'Width', 'stringFieldCallback', 'string', 'Allowed values: a number with or without an unit (px or %), default: 100%.'),
			array('chat_height', 'Height', 'stringFieldCallback', 'string', 'Allowed values: a number with or without an unit (px or %), default: 500px'),
			array('browser_location', 'Browser Location', 'selectCallback', 'string', 'The location of <a href="https://kainex.pl/projects/wp-plugins/wise-chat-pro/faq/what-exactly-is-a-browser-i-found-this-in-the-chats-configuration/" target="_blank">the browser</a>.', self::getUsersListLocation()),
			array('users_list_width', 'Browser Width', 'stringFieldCallback', 'integer',
				'Percentage width of <a href="https://kainex.pl/projects/wp-plugins/wise-chat-pro/faq/what-exactly-is-a-browser-i-found-this-in-the-chats-configuration/" target="_blank">the browser</a> area (a column containing the users list). Empty field sets default value of 30%.'
			),

			array('_section', 'Facebook-like Mode Settings', 'These settings apply when the Facebook-like chat mode is enabled.'),
			array('fb_disable_channel', 'Disable Public Channels', 'booleanFieldCallback', 'boolean',
				'Disables all public channels. Only private chats are possible.<br />
				<strong>Notice:</strong> This option will take effect only if <a href="https://kainex.pl/projects/wp-plugins/wise-chat-pro/documentation/features/private-messages/">private messages</a> are enabled.'
			),
			array('fb_location', 'Location', 'selectCallback', 'string', 'Sets the side of the screen to stick to.', self::getLocations()),
			array('fb_channel_width', 'Channel Width', 'stringFieldCallback', 'string', 'The width of each channel window (px unit). Empty field sets default value of 300px.'),
			array('fb_channel_height', 'Channel Height', 'stringFieldCallback', 'string', 'The height of each channel window (px or % unit). Empty field sets default value of 400px.'),
			array('fb_browser_width', 'Browser Width', 'stringFieldCallback', 'integer',
				'The width of the browser area (a column containing the users list). Empty field sets default value of 300px.'
			),
			array('fb_users_list_top_offset', 'Top Offset', 'stringFieldCallback', 'integer',
				'Moves the chat down by defined offset (in <strong>px</strong> unit). It is useful when the chat covers a top toolbar or a menu.'
			),
			array('fb_bottom_offset', 'Bottom Offset', 'stringFieldCallback', 'integer',
				'Moves the chat up from the bottom by defined offset (in <strong>px</strong> unit). It is useful when the chat covers a bottom toolbar or a menu.'
			),
			array('fb_bottom_offset_threshold', 'Bottom Offset Threshold', 'stringFieldCallback', 'integer',
				'Determines the maximal screen width (in <strong>px</strong> unit) for which Bottom Offset (see above) option takes effect. It is useful when you want to enable bottom offset only on narrow screens like mobile phones or tablets. Empty value means no limit is enabled.'
			),
			array('fb_browser_minimize_enabled', 'Browser Minimize Enabled', 'booleanFieldCallback', 'boolean', 'Displays a minimize button in the title bar of the browser.'),
			array('fb_minimize_on_start', 'Minimized By Default', 'booleanFieldCallback', 'boolean',
				'Minimizes both channel windows and the browser by default. If user maximizes chat window and/or the browser then this setting no longer applies.
				This option will become effective again after the user clears LocalStorage in Web browser.'
			),
			array('fb_z_index', 'Z-index Value', 'stringFieldCallback', 'integer', 'Try to increase the value if the chat is covered by other elements of the theme.'),
			array('_section', 'Mobile Mode Settings', 'These settings apply to the mobile version either in FB-like or classic mode. The mobile version of the chat is displayed automatically on narrow screens.'),
			array('mobile_mode_tabs_disable', 'Hide all tabs', 'booleanFieldCallback', 'boolean', 'Hides all tabs in the mobile version. <br /><strong>Notice:</strong> This will make the users list and the configuration tab inaccessible to users.'),
			array('mobile_mode_tab_chats_enabled', '"Chats" Tab Enabled', 'booleanFieldCallback', 'boolean', 'Enables "Chats" tab in the mobile version.'),
		);
	}

	public function getProFields() {
        return array(
        	'classic_disable_channel', 'fb_disable_channel', 'fb_location', 'fb_channel_width', 'fb_channel_height',
	        'fb_browser_width', 'fb_users_list_top_offset', 'fb_bottom_offset', 'fb_bottom_offset_threshold', 'fb_browser_minimize_enabled',
	        'fb_minimize_on_start', 'fb_z_index', 'mobile_mode_tab_chats_enabled'
        );
    }

	public function getDefaultValues() {
		return array(
			'classic_disable_channel' => 0,
			'chat_width' => '100%',
			'chat_height' => '500px',
			'users_list_width' => '',
			'browser_location' => '',
			'fb_users_list_top_offset' => '',
			'fb_bottom_offset' => '',
			'fb_bottom_offset_threshold' => '',
			'fb_browser_minimize_enabled' => 1,
			'fb_minimize_on_start' => 0,
			'fb_disable_channel' => 0,
			'fb_location' => 'right',
			'fb_z_index' => 200000,
			'fb_browser_width' => 300,
			'fb_channel_height' => '',
			'fb_channel_width' => '',
			'mobile_mode_tab_chats_enabled' => 1,
			'mobile_mode_tabs_disable' => 0
		);
	}

	public static function getLocations() {
		return array(
			'right' => 'Right',
			'left' => 'Left'
		);
	}

	public static function getUsersListLocation() {
		return array(
			'' => 'Right',
			'left' => 'Left'
		);
	}

}