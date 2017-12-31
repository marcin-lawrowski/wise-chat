<?php 

WiseChatContainer::load('WiseChatThemes');

/**
 * Wise Chat admin appearance settings tab class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatAppearanceTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Chat Window Appearance'),
			array(
				'theme', 'Theme', 'selectCallback', 'string', '',
				array_merge(WiseChatThemes::getAllThemes(), array(
					'_DISABLED_pro_1' => 'Crystal (available in Wise Chat Pro)',
					'_DISABLED_pro_2' => 'Clear (available in Wise Chat Pro)',
					'_DISABLED_pro_3' => 'Balloon (available in Wise Chat Pro)',
				))
			),
			array('background_color_chat', 'Background Color', 'colorFieldCallback', 'string', ''),
			array('text_color_chat', 'Font Color', 'colorFieldCallback', 'string', ''),
			array('text_size_chat', 'Font Size', 'selectCallback', 'string', '', WiseChatAppearanceTab::getFontSizes()),
			array('chat_width', 'Width', 'stringFieldCallback', 'string', 'Allowed values: a number with or without an unit (px or %), default: 100%'),
			array('chat_height', 'Height', 'stringFieldCallback', 'string', 'Allowed values: a number with or without an unit (px or %), default: 350px'),
			
			array('_section', 'Messages List Appearance'),
			array('messages_limit', 'Messages Limit', 'stringFieldCallback', 'integer', 'Maximum number of messages loaded on start-up'),
			array('messages_order', 'Messages Order', 'selectCallback', 'string', 'Sorting order of the messages', WiseChatAppearanceTab::getSortingOrder()),
			
			array('_section', 'Message Appearance'),
			array('background_color', 'Background Color', 'colorFieldCallback', 'string', ''),
			array('text_color', 'Font Color', 'colorFieldCallback', 'string', ''),
			array('text_color_user', 'Username Font Color<br />(any user)', 'colorFieldCallback', 'string', 'Font color of username text in messages sent by any user'),
			array('text_color_logged_user', 'Username Font Color<br />(logged in users)', 'colorFieldCallback', 'string', 'Font color of username text in messages sent by logged in users'),
			array('text_color_user_roles', 'Username Font Color<br />(logged in users in roles)', 'textColorUserRolesCallback', 'multivalues'),
			array('text_size', 'Font Size', 'selectCallback', 'string', '', WiseChatAppearanceTab::getFontSizes()),
			
			array('messages_time_mode', 'Message Time Mode', 'selectCallback', 'string', 'Format of the date and time displayed next to each message', WiseChatAppearanceTab::getTimeModes()),
			array('messages_inline', 'Inline Message', 'booleanFieldCallback', 'boolean', 'Displays message and username in the same line'),
			array('link_wp_user_name', 'Username Display Mode', 'selectCallback', 'string', '
			    Controls how username is displayed in each message:<br />
			    <strong>- Plain text:</strong> username is displayed as a plain text.<br />
                <strong>- Link to the page:</strong> username is displayed as a link. By default the link directs to the author\'s page. Only messages sent by WordPress users are taken into account. If you would like to link every user name provide a template for the link (see the option below).<br />
                <strong>- Link for @mentioning the user:</strong> username is displayed as a link that inserts <strong>"@UserName: "</strong> text into the message input field. Useful when an user wants to mention someone.
			    ', WiseChatAppearanceTab::getUserNameLinkModes()),
			array('link_user_name_template', 'Username Link Template', 'stringFieldCallback', 'string', '
                A template of the URL used to construct a link from username in each message (see the option above). While creating the template you can use the following dynamic variables: id, username, displayname. <br />
                <strong>Example 1:</strong> http://my.website.com/users/{username}/profile<br />
                <strong>Example 2:</strong> http://my.website.com/{id}/about<br />
                <strong>Example 3:</strong> http://my.website.com/search?user={displayname}
                '),
			array('show_avatars', 'Show Avatar', 'booleanFieldCallback', 'boolean', 'Shows user avatar next to each message'),

			array('_section', 'Input Section Appearance', 'Input section is the rectangular area around message input field'),
			array('background_color_input', 'Background Color', 'colorFieldCallback', 'string', ''),
			array('text_color_input_field', 'Font Color', 'colorFieldCallback', 'string', ''),
			array('show_users_counter', 'Show Users Counter', 'booleanFieldCallback', 'boolean', 'Shows number of users visiting current channel'),
			array('counter_without_anonymous', "Counter Without Anonymous", 'booleanFieldCallback', 'boolean', 'Does not include anonymous users in counter calculation'),
            array('show_emoticon_insert_button', 'Show Emoticon Button', 'booleanFieldCallback', 'boolean', 'Shows a button, near the message input field, that enables to insert an emoticon'),
			array('show_message_submit_button', 'Show Submit Button', 'booleanFieldCallback', 'boolean', 'Displays the submit button next to the message input field, might be useful on mobile devices'),
			array('show_user_name', 'Show User Name', 'booleanFieldCallback', 'boolean', 'Shows the name of the current user near the message input field'),
			array('multiline_support', 'Multiline Messages', 'booleanFieldCallback', 'boolean', 'Changes input field into multiline input field. ENTER key sends the message. Shift+ENTER key combination can be used in order to move cursor to the new line.'),
			array('input_controls_location', 'Input Controls Location', 'selectCallback', 'string', 'Location of the input field, submit button and customizations panel section', WiseChatAppearanceTab::getInputFieldLocation()),
			array('allow_change_user_name', 'Allow To Change User Name', 'booleanFieldCallback', 'boolean', 'Permits an anonymous user to change his/her name displayed on the chat'),
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
			
			array('_section', 'Users List Appearance'),
			array('show_users', 'Show Users List', 'booleanFieldCallback', 'boolean', 'Shows users currently visiting the channel'),
			array('enable_private_messages', 'Enable Private Messages', 'booleanFieldCallback', 'boolean', 'Allows users to exchange private messages. I have to show users list (option above) in order to enable private messages.'),
			array('show_users_list_avatars', 'Show Avatars', 'booleanFieldCallback', 'boolean', 'Shows user avatar next to each username on the list'),
			array('show_users_flags', 'Show National Flags', 'booleanFieldCallback', 'boolean', '
                Shows national flag next to each user on the list. Country is obtained from IP address and this may not be successful sometimes.<br />
                <strong>Notice:</strong> In order to show flags enable "Collect User Statistics" option in General tab
                '),
            array('show_users_city_and_country', 'Show City And Country', 'booleanFieldCallback', 'boolean', '
                Shows city and country code next to each user on the list. City and country are obtained from IP address and this may not be successful sometimes.<br />
                <strong>Notice:</strong> In order to show cities and countries enable "Collect User Statistics" option in General tab
                '),
			array('users_list_width', 'Users List Width', 'stringFieldCallback', 'integer', 'Percentage width of the list of users. Empty field sets default value of 29%.'),
			array('background_color_users_list', 'Background Color', 'colorFieldCallback', 'string', 'Background color of the users list'),
			array('text_color_users_list', 'Font Color', 'colorFieldCallback', 'string', 'Font color of the texts inside the users list'),
			array('text_size_users_list', 'Font Size', 'selectCallback', 'string', 'Font size', WiseChatAppearanceTab::getFontSizes()),
			array('autohide_users_list', 'Auto-hide Users List', 'booleanFieldCallback', 'boolean', 'Auto-hides users lists when the chat window gets narrow enough (see the threshold below)'),
			array('autohide_users_list_width', 'Auto-hide Width Threshold', 'stringFieldCallback', 'integer', 'Minimum width of the chat window when users list is visible'),
			array('users_list_hide_anonymous', 'Hide Anonymous Users', 'booleanFieldCallback', 'boolean', 'Hides anonymous users on the users list'),
			array('users_list_hide_roles', 'Hide User Roles', 'checkboxesCallback', 'multivalues', 'Hides users belonging to these roles on the users list', self::getRoles()),
			array('users_list_linking', 'Usernames Mode', 'booleanFieldCallback', 'boolean', 'Makes usernames like it is set in Username Display Mode option.'),

			array('_section', 'Advanced Customization'),
			array('custom_styles', 'Custom CSS Styles', 'multilineFieldCallback', 'multilinestring', 'Custom CSS styles for the chat, valid CSS syntax is required.'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'theme' => 'airflow',
			'background_color_chat' => '',
			'text_color_chat' => '',
			'text_size_chat' => '',
			'messages_limit' => 30,
			'background_color' => '',
			'background_color_input' => '',
			'text_color' => '',
			'text_color_user' => '',
			'text_color_logged_user' => '',
			'text_color_input_field' => '',
			'chat_width' => '100%',
			'chat_height' => '350px',
			'show_user_name' => 1,
			'link_wp_user_name' => 0,
			'link_user_name_template' => '',
			'show_message_submit_button' => 1,
			'allow_change_user_name' => 1,
			'user_name_length_limit' => 25,
			'multiline_support' => 0,
			'show_users' => 0,
			'show_users_counter' => 0,
			'counter_without_anonymous' => 0,
			'input_controls_location' => '',
			'messages_order' => '',
			'custom_styles' => '',
			'allow_mute_sound' => '',
			'messages_time_mode' => 'elapsed',
			'messages_inline' => 0,
			'background_color_users_list' => '',
			'text_color_users_list' => '',
			'text_size' => '',
			'text_size_users_list' => '',
			'allow_change_text_color' => 1,
			'text_color_parts' => array('message', 'messageUserName'),
			'users_list_width' => '',
            'show_emoticon_insert_button' => 1,
            'show_users_flags' => 0,
            'show_users_city_and_country' => 0,
            'autohide_users_list' => 0,
            'autohide_users_list_width' => 300,
			'users_list_hide_anonymous' => 0,
			'users_list_hide_roles' => array(),
			'users_list_linking' => 0,
		);
	}

    public function getParentFields() {
        return array(
            'show_users_flags' => 'collect_user_stats',
            'show_users_city_and_country' => 'collect_user_stats',
            'autohide_users_list_width' => 'autohide_users_list',
            'counter_without_anonymous' => 'show_users_counter',
            'text_color_parts' => 'allow_change_text_color',
        );
    }

	public function getProFields() {
		return array('enable_private_messages', 'show_users_list_avatars', 'show_avatars');
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

	public static function getColorsMode() {
		return array(
			'message' => 'Message Text',
			'messageUserName' => 'User Name in Message'
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