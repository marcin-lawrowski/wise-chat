<?php

/**
 * Wise Chat admin appearance settings tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatAppearanceTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Chat Window Appearance'),
			array('theme', 'Theme', 'selectCallback', 'string', '', self::getAllThemes()),
			array('background_color_chat', 'Background Color', 'colorFieldCallback', 'string', ''),
			array('text_color_chat', 'Font Color', 'colorFieldCallback', 'string', ''),
			array('text_size_chat', 'Font Size', 'selectCallback', 'string', '', WiseChatAppearanceTab::getFontSizes()),
			
			array('_section', 'Messages List Appearance'),
			array('messages_limit', 'Messages Limit', 'stringFieldCallback', 'integer', 'Maximum number of messages loaded on start-up. The higher number the less performance.'),
			array('private_messages_limit', 'Private Messages Limit', 'stringFieldCallback', 'integer', 'Maximum number of private messages loaded on start-up. The higher number the less performance.'),
			array('recent_chats_limit', 'Recent Chats Limit', 'stringFieldCallback', 'integer', 'Maximum number of read conversations loaded on start-up. They are displayed in the recent chats popup. The higher number the less performance.'),
			array('messages_order', 'Messages Order', 'selectCallback', 'string', 'Sorting order of the messages', WiseChatAppearanceTab::getSortingOrder()),
			
			array('_section', 'Message Appearance'),
			array('background_color', 'Background Color', 'colorFieldCallback', 'string', ''),
			array('text_color', 'Font Color', 'colorFieldCallback', 'string', ''),
			array('text_color_user', 'Username Font Color<br />(any user)', 'colorFieldCallback', 'string', 'Font color of username text in messages sent by any user'),
			array('text_color_logged_user', 'Username Font Color<br />(logged in users)', 'colorFieldCallback', 'string', 'Font color of username text in messages sent by logged in users'),
			array('text_color_user_roles', 'Username Font Color<br />(logged in users in roles)', 'textColorUserRolesCallback', 'multivalues'),
			array('text_size', 'Font Size', 'selectCallback', 'string', '', WiseChatAppearanceTab::getFontSizes()),
			
			array('messages_time_mode', 'Message Time Mode', 'selectCallback', 'string', 'Format of the date and time displayed next to each message', WiseChatAppearanceTab::getTimeModes()),
			array(
				'messages_date_format', 'Message Date Format', 'stringFieldCallback', 'string',
				'Format of date displayed next to each message. Empty value means web browser\'s local date format is used.<br />'.
				'For detailed format options check <a href="https://momentjs.com/docs/#/displaying/" target="_blank">the docs</a>.'
			),
			array(
				'messages_time_format', 'Message Time Format', 'stringFieldCallback', 'string',
				'Format of time displayed next to each message. Empty value means web browser\'s local time format is used.<br />'.
				'For detailed format options check <a href="https://momentjs.com/docs/#/displaying/" target="_blank">the docs</a>.'
			),
			array('link_wp_user_name', 'Username Display Mode', 'selectCallback', 'string', '
			    Controls how username is displayed in each message:<br />
			    <strong>- Plain text:</strong> username is displayed as a plain text.<br />
                <strong>- Link to the page:</strong> username is displayed as a link. The link is displayed only if the message was sent either by WordPress user or externally logged user (through Facebook, Twitter or Google).
                In case of WordPress user the link direct to the auhtor\'s page. In case of externally logged user the link direct to the profile of that user.
                If you would like to link every user name (including anonymous users) provide a template for the link (see the option below).<br />
                <strong>- Link for @mentioning the user:</strong> username is displayed as a link that inserts <strong>"@UserName: "</strong> text into the message input field. Useful when an user wants to mention someone.
			    ', WiseChatAppearanceTab::getUserNameLinkModes()),
			array('link_user_name_template', 'Username Link Template', 'stringFieldCallback', 'string', '
                A template of the URL used to construct a link from username in each message (see the option above). While creating the template you can use the following dynamic variables: id, username, displayname. <br />
                <strong>Example 1:</strong> http://my.website.com/users/{username}/profile<br />
                <strong>Example 2:</strong> http://my.website.com/{id}/about<br />
                <strong>Example 3:</strong> http://my.website.com/search?user={displayname}
                '),
			array('show_avatars', 'Show Avatar', 'booleanFieldCallback', 'boolean', 'Shows user avatar next to each message'),

			array('_section', 'Input Section Appearance', 'Input section is the area around the message input field'),
			array('background_color_input', 'Background Color', 'colorFieldCallback', 'string', ''),
			array('text_color_input_field', 'Font Color', 'colorFieldCallback', 'string', ''),
            array('show_emoticon_insert_button', 'Show Emoticon Button', 'booleanFieldCallback', 'boolean', 'Shows a button, near the message input field, that enables to insert an emoticon'),
			array('show_message_submit_button', 'Show Submit Button', 'booleanFieldCallback', 'boolean', 'Displays the submit button next to the message input field, might be useful on mobile devices'),
			array('show_user_name', 'Show User Name', 'booleanFieldCallback', 'boolean', 'Shows the name of the current user near the message input field'),
			array('multiline_support', 'Multiline Messages', 'booleanFieldCallback', 'boolean', 'Changes input field into multiline input field. ENTER key sends the message. Shift+ENTER key combination can be used in order to move cursor to the new line.'),
			array('multiline_easy_mode', 'Multiline Easy Mode', 'booleanFieldCallback', 'boolean', 'ENTER moves cursor to the new line. Shift+ENTER key combination sends the message.'),
			array('input_controls_location', 'Input Controls Location', 'selectCallback', 'string', 'Location of the input field, submit button and customizations panel section.', WiseChatAppearanceTab::getInputFieldLocation()),

			array('_section', 'User Profile Section Settings', 'User profile section is the rectangular area that is displayed after clicking Customize link below the input field. Customize link becomes visible when at least one Allow option (see below) is enabled.'),
			array('allow_change_user_name', 'Allow To Change User Name', 'booleanFieldCallback', 'boolean', 'Permits an anonymous user to change his/her name displayed on the chat.'),
			array('allow_control_user_notifications', 'Allow To Control Notifications', 'booleanFieldCallback', 'boolean', 'Permits user to enable / disable notifications sent after receiving private messages. The option is available for logged in WordPress users only. Please configure user notifications in Notifications tab and enable private messages.'),
			array('disable_user_name_duplication_check', 'Disable User Name Duplication Check', 'booleanFieldCallback', 'boolean', 'Permits the use of user names of inactive users. By default all user names are locked for 24 hours counting from the last activity time.'),
			array('user_name_lock_window_seconds', 'User Names Lock Time', 'stringFieldCallback', 'integer', 'Determines how many seconds user name is locked since the last activity. When user name is locked it cannot be used by other user if the option above is not checked. Empty field means 86400 seconds (24 hours)'),
			array('user_name_length_limit', 'User Name Length Limit', 'stringFieldCallback', 'integer', 'Maximum length of user name. Empty field means there is no limit to the length.'),
			array('allow_mute_sound', 'Allow To Mute Sounds', 'booleanFieldCallback', 'boolean', 'Permits an user to mute all sounds generated by the chat. The option will be visible only if sound notifications are enabled.'),
			array(
				'allow_change_text_color', 'Allow To Change Text Color', 'booleanFieldCallback', 'boolean',
				'After enabling this option user is allowed to change color of some parts of the chat related to him/her.<br />You can configure what is affected by this option below.'
			),
			array(
				'text_color_parts', 'Text Color Setting Affects', 'checkboxesCallback', 'multivalues',
				'Determines what chat parts are affected by text color change.',
				self::getColorsMode()
			),
			
			array('_section', 'Browser Appearance', 'The browser is the area containing the list of users and channels'),
			array('show_users', 'Browser Enabled', 'booleanFieldCallback', 'boolean', 'Shows the browser. It lists current channels and users.'),
			array('auto_open_first_public_channel', 'Auto-open First Channel', 'booleanFieldCallback', 'boolean', 'By default opens the first public channel on startup'),
			array('show_users_list_search_box', 'Show Users Search Box', 'booleanFieldCallback', 'boolean', 'Displays users search box'),
			array('enable_private_messages', 'Enable Private Messages', 'booleanFieldCallback', 'boolean', 'Allows users to exchange private messages. I have to show users list (option above) in order to enable private messages.'),
			array('private_message_confirmation', 'Private Message Confirmation', 'booleanFieldCallback', 'boolean', 'Displays a confirmation dialog when a new private message arrives. If this option is disabled then every private message is opened automatically.'),
			array('show_users_list_avatars', 'Show Avatars', 'booleanFieldCallback', 'boolean', 'Shows user avatar next to each username on the list'),
            array('show_users_flags', 'Show National Flags', 'booleanFieldCallback', 'boolean', '
                Shows national flag next to each user on the list. Country is obtained from IP address and this may not be successful sometimes.<br />
                <strong>Notice:</strong> In order to show flags enable "Collect User Statistics" option in General tab
                '),
            array('show_users_city_and_country', 'Show City And Country', 'booleanFieldCallback', 'boolean', '
                Shows city and country code next to each user on the list. City and country are obtained from IP address and this may not be successful sometimes.<br />
                <strong>Notice:</strong> In order to show cities and countries enable "Collect User Statistics" option in General tab
                '),
			array('show_users_online_offline_mark', 'Show Online / Offline Mark', 'booleanFieldCallback', 'boolean', '
                Displays little icon that indicates whether user is online or offline. It usually goes with "Enable Offline Users" option (see few lines below).'),
			array('show_users_counter', 'Show Online Users Counter', 'booleanFieldCallback', 'boolean', 'Displays the number of online users'),

			array('background_color_users_list', 'Background Color', 'colorFieldCallback', 'string', 'Background color of the users list'),
			array('text_color_users_list', 'Font Color', 'colorFieldCallback', 'string', 'Font color of the texts inside the users list'),
			array('text_size_users_list', 'Font Size', 'selectCallback', 'string', 'Font size', WiseChatAppearanceTab::getFontSizes()),

			array('users_list_linking', 'Usernames Mode', 'booleanFieldCallback', 'boolean', 'Makes usernames like it is set in Username Display Mode option. <br />
				<strong>Notice:</strong> This option will work only if private messages are disabled.
			'),

			array('_section', 'Users List Info Window Appearance', 'Information windows are displayed when mouse pointer enters username on the users list'),
			array('show_users_list_info_windows', 'Show Info Windows', 'booleanFieldCallback', 'boolean'),
			array(
				'users_list_info_window_template', 'Info Window Template', 'multilineFieldCallback', 'multilinestring',
				'A template of info windows. Dynamic variables: {role}, {roles}, {id}, {username}, {displayname}, {email}'),

			array('_section', 'Users List Sources', 'Settings to configure which users are visible on the users list'),
			array('users_list_offline_enable', 'Enable Offline Users', 'booleanFieldCallback', 'boolean', 'Lists all users (including offline).'),
			array(
				'users_list_bp_users_only', 'BuddyPress Friends Only', 'booleanFieldCallback', 'boolean',
				'Displays BuddyPress friends only.<br />'.
				'<strong>Notice:</strong> Please remember to enable BuddyPress integration in General settings and friends component in BuddyPress configuration.'
			),
			array('users_list_hide_anonymous', 'Hide Anonymous Users', 'booleanFieldCallback', 'boolean', 'Hides anonymous users on the users list'),
			array('users_list_hide_roles', 'Hide User Roles', 'checkboxesCallback', 'multivalues', 'Hides users belonging to these roles on the users list', self::getRoles()),

			array('_section', 'BuddyPress Customization', 'BuddyPress integration adjustments'),
			array(
				'bp_member_profile_chat_button', 'Show Chat Button On Member Profile', 'booleanFieldCallback', 'boolean',
				"Displays chat button on member profiles. Clicking the button opens a private chat window if and only if the chat is currently visible on the member profile page. <br />".
				'<a href="https://kainex.pl/projects/wp-plugins/wise-chat-pro/documentation/features/private-chat-button/">Read more</a>'
			),

			array('_section', 'Advanced Customization'),
			array('custom_styles', 'Custom CSS Styles', 'multilineFieldCallback', 'multilinestring', 'Custom CSS styles for the chat, valid CSS syntax is required.'),
			array('css_classes_for_user_roles', 'CSS Classes For Roles', 'booleanFieldCallback', 'boolean',
				'Adds CSS classes indicating user roles for users list and for each posted message. <br />'.
				'<strong>Notice:</strong> Enable this option only if you want to add custom styles depending on user roles.'
			),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'theme' => 'lightgray',
			'background_color_chat' => '',
			'text_color_chat' => '',
			'text_size_chat' => '',
			'messages_limit' => 30,
			'private_messages_limit' => 200,
			'recent_chats_limit' => 20,
			'background_color' => '',
			'background_color_input' => '',
			'text_color' => '',
			'text_color_user' => '',
			'text_color_logged_user' => '',
			'text_color_input_field' => '',
			'show_user_name' => 0,
			'link_wp_user_name' => 0,
			'link_user_name_template' => '',
			'show_message_submit_button' => 1,
			'allow_change_user_name' => 1,
			'allow_control_user_notifications' => 0,
			'disable_user_name_duplication_check' => 1,
			'user_name_length_limit' => 25,
			'multiline_support' => 0,
			'multiline_easy_mode' => 0,
			'show_users' => 1,
			'auto_open_first_public_channel' => 1,
			'show_users_list_search_box' => 1,
			'show_users_counter' => 0,
			'input_controls_location' => '',
			'messages_order' => '',
			'custom_styles' => '',
			'css_classes_for_user_roles' => 0,
			'allow_mute_sound' => '',
			'messages_time_mode' => 'elapsed',
			'messages_date_format' => '',
			'messages_time_format' => '',
			'background_color_users_list' => '',
			'text_color_users_list' => '',
			'text_size' => '',
			'text_size_users_list' => '',
			'allow_change_text_color' => 1,
			'text_color_parts' => array('message', 'messageUserName'),
            'show_emoticon_insert_button' => 1,
            'show_users_flags' => 0,
            'show_users_city_and_country' => 0,
            'show_users_online_offline_mark' => 1,
			'enable_private_messages' => 0,
			'private_message_confirmation' => 1,
			'show_avatars' => 1,
			'show_users_list_avatars' => 1,
			'users_list_hide_anonymous' => 0,
			'users_list_hide_roles' => array(),
			'users_list_linking' => 0,
			'users_list_offline_enable' => 1,
			'users_list_bp_users_only' => 0,
			'bp_member_profile_chat_button' => 0,
			'show_users_list_info_windows' => 1,
			'users_list_info_window_template' => "{role}"
		);
	}

    public function getParentFields() {
        return array(
            'show_users_flags' => 'collect_user_stats',
            'show_users_city_and_country' => 'collect_user_stats',
            'enable_private_messages' => 'show_users',
            'show_users_list_search_box' => 'show_users',
            'private_message_confirmation' => 'enable_private_messages',
			'private_messages_limit' => 'enable_private_messages',
			'text_color_parts' => 'allow_change_text_color',
			'disable_user_name_duplication_check' => 'allow_change_user_name',
			'users_list_info_window_template' => 'show_users_list_info_windows',
        );
    }

	public static function getColorsMode() {
		return array(
			'message' => 'Message Text',
			'messageUserName' => 'User Name in Message'
		);
	}

    public static function getUserNameLinkModes() {
        return array(
            0 => 'Plain text',
            1 => 'Link to the page',
            2 => 'Link for @mentioning the user'
        );
    }
	
	public static function getTimeModes() {
		return array(
			'hidden' => 'Hidden',
			'' => 'Full',
			'elapsed' => 'Elapsed'
		);
	}
	
	public static function getInputFieldLocation() {
		return array(
			'' => 'Bottom',
			'top' => 'Top'
		);
	}
	
	public static function getSortingOrder() {
		return array(
			'' => 'Newest on the bottom',
			'descending' => 'Newest on the top'
		);
	}

	public static function getAllThemes() {
		return array(
			'' => 'Default',
			'lightgray' => 'Light Gray',
			'colddark' => 'Cold Dark',
			'airflow' => 'Air Flow',
			'_DISABLED_pro_crystal' => 'Crystal (available in Wise Chat Pro)',
			'_DISABLED_pro_clear' => 'Clear (available in Wise Chat Pro)',
			'_DISABLED_pro_balloon' => 'Balloon (available in Wise Chat Pro)'
		);
	}

	public function getProFields() {
        return array(
        	'allow_control_user_notifications', 'auto_open_first_public_channel', 'enable_private_messages',
	        'private_message_confirmation', 'show_users_list_info_windows', 'users_list_info_window_template',
	        'users_list_bp_users_only', 'bp_member_profile_chat_button', 'recent_chats_limit'
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
	
	public static function getFontSizes() {
		$sizes = array();
		$sizes[''] = 'Default';
		
		for ($i = 5; $i <= 15; $i += 1) {
			$sizes[sprintf('%d.%dem', intval($i / 10), $i % 10)] = ($i * 10).' %';
		}
		$sizes['2.0em'] = '200 %';
		$sizes['3.0em'] = '300 %';
		
		for ($i = 8; $i <= 18; $i++) {
			$sizes[$i.'px'] = $i;
		}
		$sizes['20px'] = '20';
		$sizes['22px'] = '22';
		$sizes['24px'] = '24';
		$sizes['26px'] = '26';
		$sizes['28px'] = '28';
		$sizes['30px'] = '30';
		$sizes['36px'] = '36';
		
		return $sizes;
	}

	public function textColorUserRolesCallback() {
		$roles = $this->getRoles();
		$values = $this->options->getOption('text_color_user_roles', array());

		print('
			<style type="text/css">
				table.packed tr td { padding: 0; }
			</style>
		');
		print('<table class="packed">');
		foreach ($roles as $roleSlug => $roleName) {
			$value = array_key_exists($roleSlug, $values) ? $values[$roleSlug] : '';

			printf(
				'<tr>'.
				'<td>%s:&nbsp;</td><td><input type="text" id="text_color_user_roles_%s" name="'.WiseChatOptions::OPTIONS_NAME.'[text_color_user_roles][%s]" value="%s" class="wc-color-picker" /> </td>'.
				'</tr>',
				$roleName, $roleSlug, $roleSlug, $value
			);
		}
		print('</table>');
	}
}