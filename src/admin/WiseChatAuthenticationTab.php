<?php 

/**
 * Wise Chat authentication tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatAuthenticationTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Chat Authentication Methods', 'Set a method of user authentication'),
			array('auth_mode', 'Method', 'radioCallback', 'string', '', self::getAuthModes()),
			array('auth_username_fields', 'Additional Fields', 'customFieldsCallback', 'json'),

			array('_section_ext_an', 'Anonymous Login'),
			array('anonymous_login_enabled', 'Enable', 'booleanFieldCallback', 'boolean', 'Enables anonymous authentication option. It is an additional option to allow your users authenticate without external services.'),

			array('_section_ext_fb', 'Facebook Login',
				'In order to setup Facebook authentication you need to register an Application with Facebook. Then you will be able to get your Application ID and Secret. More details <a href="https://kainex.pl/projects/wp-plugins/wise-chat/documentation/external-authentication/facebook-authentication/" target="_blank">here</a>.<br/>
				Wise Chat Pro collects the following data of a Facebook user: ID, name, avatar URL, authentication token.'
			),
			array('facebook_login_enabled', 'Enable', 'booleanFieldCallback', 'boolean', 'Enables Facebook authentication option.'),
			array('facebook_login_app_id', 'Application ID', 'stringFieldCallback', 'string', 'Required application ID.'),
			array('facebook_login_app_secret', 'Application Secret', 'stringFieldCallback', 'string', 'Required application secret key'),

			array('_section_ext_tw', 'Twitter Login',
				'In order to setup Twitter authentication you need to register an Application with Twitter. Then you will be able to get your OAuth 2.0 Client ID and Secret. More details <a href="https://kainex.pl/projects/wp-plugins/wise-chat/documentation/external-authentication/twitter-authentication/" target="_blank">here</a>.<br />
				Wise Chat Pro collects the following data of a Twitter user: ID, name, username, avatar URL.'
			),
			array('twitter_login_enabled', 'Enable', 'booleanFieldCallback', 'boolean', 'Enables Twitter authentication option.'),
			array('twitter_login_client_id', 'OAuth 2.0 Client ID', 'stringFieldCallback', 'string', 'Required OAuth 2.0 Client ID.'),
			array('twitter_login_client_secret', 'OAuth 2.0 Client Secret', 'stringFieldCallback', 'string', 'Required OAuth 2.0 Client Secret'),

			array('_section_ext_go', 'Google Login',
				'In order to setup Google authentication you need to create new project with Google. Then you will be able to get your Client ID and Client Secret. More details <a href="https://kainex.pl/projects/wp-plugins/wise-chat/documentation/external-authentication/google-authentication/" target="_blank">here</a>.<br />
				Wise Chat Pro collects the following data of a Google user: ID, name, username, avatar URL.'
			),
			array('google_login_enabled', 'Enable', 'booleanFieldCallback', 'boolean', 'Enables Google authentication option.'),
			array('google_login_client_id', 'Client ID', 'stringFieldCallback', 'string', 'Required Client ID.'),
			array('google_login_client_secret', 'Client Secret', 'stringFieldCallback', 'string', 'Required Client Secret'),

		);
	}

	private static function getAuthModes() {
		return array(
			'auto' => array('Auto', 'Anonymous visitors receive anonymous name like Anonymous1234, WordPress users receive their profile name, no password screen'),
			'username' => array('Username', 'A visitor is asked to enter username and other optional fields (see below)'),
			'external' => array('External', 'Uses external services to authenticate in Wise Chat<br/><strong>Notice: </strong> PHP 8.0 is required to enable the external authentication. Current version of PHP: '.PHP_VERSION),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'auth_mode' => 'auto',

			'anonymous_login_enabled' => 1,

			'facebook_login_enabled' => 0,
			'facebook_login_app_id' => '',
			'facebook_login_app_secret' => '',

			'twitter_login_enabled' => 0,
			'twitter_login_api_key' => '',
			'twitter_login_api_secret' => '',

			'google_login_enabled' => 0,
			'google_login_client_id' => '',
			'google_login_client_secret' => '',
		);
	}

	public function getParentFields() {
		return array(
			'facebook_login_app_id' => 'facebook_login_enabled',
			'facebook_login_app_secret' => 'facebook_login_enabled',

			'twitter_login_api_key' => 'twitter_login_enabled',
			'twitter_login_api_secret' => 'twitter_login_enabled',

			'google_login_client_id' => 'google_login_enabled',
			'google_login_client_secret' => 'google_login_enabled',
		);
	}

	public function getRadioGroups() {
		return array(
			'auth_mode' => array(
				'auto' => array(),
				'username' => array('auth_username_fields'),
				'external' => array(
					'anonymous_login_enabled',
					'facebook_login_enabled',
					'facebook_login_app_id',
					'facebook_login_app_secret',
					'twitter_login_enabled',
					'twitter_login_api_key',
					'twitter_login_api_secret',
					'google_login_enabled',
					'google_login_client_id',
					'google_login_client_secret',
					'_section_ext_an',
					'_section_ext_fb',
					'_section_ext_tw',
					'_section_ext_go'
				)
			)
		);
	}

	public function customFieldsCallback() {
		$customJson = json_decode($this->options->getOption('auth_username_fields'), true);
		$custom = is_array($customJson) ? $customJson : array();

		$html = "<table class='wp-list-table widefat' name='wise_chat_options_name[auth_username_fields]'>";
		$html .= '<thead><tr><td width="30">No.</td><td>Name</td><td>Type</td></tr></thead>';

		$types = array(
			'text' => 'Text',
			'long_text' => 'Long Text',
		);


		for ($i = 1; $i <= 7; $i++) {
			$classes = $i % 2 == 0 ? 'alternate' : '';

			if (!array_key_exists($i, $custom)) {
				$custom[$i] = array(
					'name' => '', 'type' => 'text',
				);
			}

			$key = $i - 1;

			$typesOptions = array();
			foreach ($types as $type => $name) {
				$typesOptions[] = "<option ".($type === $custom[$key]['type'] ? 'selected' : '')." value='{$type}'>{$name}</option>";
			}

			$idInput = sprintf(
				'<input type="hidden" name="%s[auth_username_fields][%d][id]" value="%d">',
				WiseChatOptions::OPTIONS_NAME, $key, $i
			);
			$nameInput = sprintf(
				'<input type="text" name="%s[auth_username_fields][%d][name]" value="%s" style="max-width: 200px; min-width: 100px; ">',
				WiseChatOptions::OPTIONS_NAME, $key, htmlspecialchars($custom[$key]['name'])
			);
			$typeSelect = sprintf(
				'<select name="%s[auth_username_fields][%d][type]" style="max-width: 100px;">%s</select>',
				WiseChatOptions::OPTIONS_NAME, $key, implode('', $typesOptions)
			);

			$html .= sprintf(
				'<tr class="%s">
					<td>%s.</td><td>%s</td><td>%s</td>
				</tr>',
				$classes, $i, $idInput.$nameInput, $typeSelect
			);
		}
		$html .= "</table><p class=\"description\">Please specify additional optional fields to be displayed under username field request</p>";

		print($html);
	}

}