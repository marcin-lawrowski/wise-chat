<?php 

/**
 * Wise Chat admin advanced settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatAdvancedTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
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
			array('_section', 'Chat engine'),
			array(
				'messages_refresh_time', 'Refresh Time', 'selectCallback', 'string', 
				"Determines how often the chat should check for new messages. Lower value means higher CPU usage and more HTTP requests.", 
				WiseChatAdvancedTab::getRefreshTimes()
			),
			array('enabled_debug', 'Enable Debug Mode', 'booleanFieldCallback', 'boolean', "Displays extended error log. It is useful when reporting issues."),
			array('enabled_errors', 'Enable Errors Reporting', 'booleanFieldCallback', 'boolean', "Determines if all run-time errors should be displayed to chat users. It is useful when detecting issues."),
			array(
				'ajax_validity_time', 'AJAX Validity Time', 'stringFieldCallback', 'integer',
				'Determines how many minutes AJAX requests are considered as valid. It is useful to prevent indexing internal API calls by search engines and Web crawlers.<br />
				<strong>Warning:</strong> Too low value may cause errors on mobile devices. The default value is: 1 day (1440 minutes). '
			),
			array(
				'enabled_xhr_check', 'Enable XHR Request Check', 'booleanFieldCallback', 'boolean',
				'Enabled check for "X-Requested-With" header in AJAX requests'
			),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'ajax_engine' => 'ultralightweight',
			'messages_refresh_time' => 3000,
			'enabled_debug' => 0,
			'enabled_errors' => 0,
			'ajax_validity_time' => 1440,
			'enabled_xhr_check' => 1,
			'user_auth_expiration_days' => 14,
			'user_auth_keep_logged_in' => 1,
		);
	}
	
	public static function getAllEngines() {
		return array(
			'' => 'Default',
			'lightweight' => 'Lightweight AJAX',
			'ultralightweight' => 'Ultra Lightweight AJAX'
		);
	}
	
	public static function getRefreshTimes() {
		return array(
			1000 => '1s',
			2000 => '2s',
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
}