<?php 

/**
 * Wise Chat admin advanced settings tab class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatAdvancedTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Advanced Settings'),
			array(
				'ajax_engine', 'AJAX Engine', 'selectCallback', 'string', 
				"Engine for AJAX requests generated by the chat. <br />The Default engine is the most compatible but it has a poor performance. The Lightweight AJAX engine is a lot faster and consumes less CPU, however, it is slightly possible that it could be unstable in future versions of WordPress.", 
				WiseChatAdvancedTab::getAllEngines()
			),
			array(
				'messages_refresh_time', 'Refresh Time', 'selectCallback', 'string', 
				"Determines how often the chat should check for new messages. Lower value means higher CPU usage and more HTTP requests.", 
				WiseChatAdvancedTab::getRefreshTimes()
			),
			array('show_powered_by', 'Show "Powered By" Footer', 'booleanFieldCallback', 'boolean'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'ajax_engine' => 'lightweight',
			'messages_refresh_time' => 3000,
			'show_powered_by' => 1,
		);
	}
	
	public static function getAllEngines() {
		return array(
			'' => 'Default',
			'lightweight' => 'Lightweight AJAX'
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