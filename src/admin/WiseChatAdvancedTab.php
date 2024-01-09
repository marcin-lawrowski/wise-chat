<?php 

/**
 * Wise Chat admin advanced settings tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatAdvancedTab extends WiseChatAbstractTab {

	public function __construct() {
		parent::__construct();
		add_filter('pre_update_option_'.WiseChatOptions::OPTIONS_NAME, [$this, 'onUpdate'], 10, 2);
	}
	public function onUpdate($newValue, $oldValue) {
		if (isset($newValue['ajax_engine']) && isset($oldValue['ajax_engine']) && $oldValue['ajax_engine'] && $newValue['ajax_engine'] !== $oldValue['ajax_engine'] && $newValue['ajax_engine'] === 'gold') {
			if (!WiseChatInstaller::registerEngine()) {
				$this->addErrorMessage('Could not switch to Gold engine because it cannot be installed. It is very likely that wp-content directory is not writable. Please make wp-content writable and try again switching to Gold engine. Please check PHP log for details.');
				$newValue['ajax_engine'] = $oldValue['ajax_engine'];
			}
		}
		return $newValue;
	}

	public function getFields() {
		return array(
			array('_section', 'Chat Engine'),
			array(
				'ajax_engine', 'Engine', 'selectCallback', 'string',
				"<ul>
					<li><strong>Default</strong> - very reliable, average performance</li>
					<li><strong>Lightweight</strong> - good performance, may be blocked by security plugins or server configuration</li>
					<li><strong>Ultra Lightweight</strong> - the best performance, may be blocked by security plugins or server configuration</li>
					<li><strong>Gold</strong> - very reliable, good performance</li>
				</ul>",
				WiseChatAdvancedTab::getAllEngines()
			),
			array(
				'messages_refresh_time', 'Refresh Time', 'selectCallback', 'string',
				"Determines how often the chat should check for new messages. Lower value means higher CPU usage and more HTTP requests.",
				WiseChatAdvancedTab::getRefreshTimes()
			),
			array('_section', 'Engines Diagnostics', 'Please run the diagnostics to investigate possible problems with messages delivery.'),
			array('engines_diagnostics', 'Diagnostics', 'diagnosticsCallback', 'void'),

			array('_section', 'User Authentication'),
			array(
				'user_auth_expiration_days', 'Expiration Time (in days)', 'stringFieldCallback', 'integer',
				'The authentication cookie timeout. After the timeout is reached the authentication cookie is deleted and user authentication is lost.<br />'.
				'<strong>Notice: </strong>Empty or zero value means session-time cookie. The authentication is lost as soon as the web browser is closed (including its all tabs and windows).<br />'.
				'<strong>Notice: </strong>Any changes to this field affect new chat users only<br />'
			),
			array(
				'user_auth_keep_logged_in', 'Keep Authenticated', 'booleanFieldCallback', 'boolean',
				'Refreshes authentication cookie if its expiration time is less than half of its initial setting. This will make user always authenticated if the user keeps visiting the chat page at least one in the number of days set in Expiration Time field.<br />'.
				'<strong>Notice:</strong> If Expiration Time field is set to empty or zero value then this setting has no effect.'
			),
			array('_section', 'Other settings'),
			array('enabled_debug', 'Enable Debug Mode', 'booleanFieldCallback', 'boolean', "Displays extended error log. It is useful when reporting issues."),
			array(
				'ajax_validity_time', 'AJAX Validity Time', 'stringFieldCallback', 'integer',
				'Determines how many minutes AJAX requests are considered as valid. It is useful to prevent indexing internal API calls by search engines and Web crawlers.<br />
				<strong>Warning:</strong> Too low value may cause errors on mobile devices. The default value is: 1 day (1440 minutes). '
			),
			array(
				'enabled_xhr_check', 'Enable XHR Request Check', 'booleanFieldCallback', 'boolean',
				'Enables checking for "X-Requested-With" header in AJAX requests'
			),
			array('user_actions', 'Actions', 'adminActionsCallback', 'void'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'ajax_engine' => 'ultralightweight',
			'messages_refresh_time' => 4000,
			'enabled_debug' => 0,
			'ajax_validity_time' => 1440,
			'enabled_xhr_check' => 1,
			'user_auth_expiration_days' => 14,
			'user_auth_keep_logged_in' => 1,
			'user_actions' => null,
		);
	}
	
	public static function getAllEngines() {
		return array(
			'' => 'Default',
			'lightweight' => 'Lightweight',
			'ultralightweight' => 'Ultra Lightweight',
			'gold' => 'Gold'
		);
	}
	
	public static function getRefreshTimes() {
		return array(
			3000 => '3s',
			4000 => '4s',
			5000 => '5s',
			10000 => '10s',
			15000 => '15s',
			20000 => '20s',
			30000 => '30s',
			60000 => '60s',
			120000 => '120s',
		);
	}

	public function adminActionsCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=resetAnonymousCounter&nonce=".wp_create_nonce('resetAnonymousCounter'));

		printf(
			'<a class="button-secondary" href="%s" title="Resets username prefix" onclick="return confirm(\'Are you sure you want to reset the prefix?\')">Reset Username Prefix</a><p class="description">Resets prefix number used to generate username for anonymous users.</p>',
			wp_nonce_url($url)
		);

		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=resetSettings&nonce=".wp_create_nonce('resetSettings'));
		printf(
			'<br /><a class="button-secondary" href="%s" title="Resets Wise Chat settings" onclick="return confirm(\'WARNING: All settings will be permanently deleted. \\n\\nAre you sure you want to reset the settings?\')">Reset All Settings</a><p class="description">Resets all settings to default values.</p>',
			wp_nonce_url($url)
		);

		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=deleteAllUsersAndMessages&nonce=".wp_create_nonce('deleteAllUsersAndMessages'));
		printf(
			'<br /><a class="button-secondary" href="%s" title="Deletes all messages and users" onclick="return confirm(\'WARNING: All messages and users will be permanently deleted. \\n\\nAre you sure you want to proceed?\')">Delete All Messages and Users</a><p class="description">Deletes all messages and users.</p>',
			wp_nonce_url($url)
		);
	}

	public function resetAnonymousCounterAction() {
		if (current_user_can('manage_options') && wp_verify_nonce($_GET['nonce'], 'resetAnonymousCounter')) {
			$this->options->resetUserNameSuffix();
			$this->addMessage('The prefix has been reset.');
		}
	}

	public function resetSettingsAction() {
		if (current_user_can('manage_options') && wp_verify_nonce($_GET['nonce'], 'resetSettings')) {
			$this->options->dropAllOptions();

			// set the default options:
			$settings = WiseChatContainer::get('WiseChatSettings');
			$settings->setDefaultSettings();

			$this->addMessage('All settings have been reset to defaults.');
		}
	}

	public function deleteAllUsersAndMessagesAction() {
		if (current_user_can('manage_options') && wp_verify_nonce($_GET['nonce'], 'deleteAllUsersAndMessages')) {
			$this->messagesService->deleteAll();
			$this->usersDAO->deleteAll();
			$this->actions->publishAction('deleteAllMessages', array());
			$this->addMessage('All messages and users have been deleted.');
		}
	}

	public function diagnosticsCallback() {
		echo '<div class="wc-advanced-diagnostics-result"><i>Please run the diagnostics</i></div><br />';
		echo '<button type="button" class="button-secondary wc-advanced-diagnostics-run">Run</button>';
	}

}