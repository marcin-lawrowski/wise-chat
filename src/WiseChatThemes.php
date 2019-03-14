<?php

/**
 * WiseChat themes description.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatThemes {
    /**
     * @var array Themes list
     */
	private static $themes = array(
		'' => 'Default',
		'lightgray' => 'Light Gray',
		'colddark' => 'Cold Dark',
		'airflow' => 'Air Flow'
	);

    /**
     * @var array Files definition
     */
	private static $themesSettings = array(
		'' => array(
			'mainTemplate' => '/themes/default/main.tpl',
			'messageTemplate' => '/themes/default/message.tpl',
			'passwordAuthorization' => '/themes/default/password-authorization.tpl',
			'userName' => '/themes/default/user-name.tpl',
			'accessDenied' => '/themes/default/access-denied.tpl',
			'channelUsersWidgetTemplate' => '/themes/default/channel-users-widget.tpl',
			'css' => '/themes/default/theme.css',
		),
		'colddark' => array(
			'mainTemplate' => '/themes/default/main.tpl',
			'messageTemplate' => '/themes/colddark/message.tpl',
			'passwordAuthorization' => '/themes/default/password-authorization.tpl',
			'userName' => '/themes/default/user-name.tpl',
			'accessDenied' => '/themes/default/access-denied.tpl',
			'channelUsersWidgetTemplate' => '/themes/default/channel-users-widget.tpl',
			'css' => '/themes/colddark/theme.css',
		),
		'lightgray' => array(
			'mainTemplate' => '/themes/default/main.tpl',
			'messageTemplate' => '/themes/lightgray/message.tpl',
			'passwordAuthorization' => '/themes/default/password-authorization.tpl',
			'userName' => '/themes/default/user-name.tpl',
			'accessDenied' => '/themes/default/access-denied.tpl',
			'channelUsersWidgetTemplate' => '/themes/default/channel-users-widget.tpl',
			'css' => '/themes/lightgray/theme.css',
		),
		'airflow' => array(
			'mainTemplate' => '/themes/default/main.tpl',
			'messageTemplate' => '/themes/airflow/message.tpl',
			'passwordAuthorization' => '/themes/default/password-authorization.tpl',
			'userName' => '/themes/default/user-name.tpl',
			'accessDenied' => '/themes/default/access-denied.tpl',
			'channelUsersWidgetTemplate' => '/themes/default/channel-users-widget.tpl',
			'css' => '/themes/airflow/theme.css',
		)
	);
	
	/**
	* @var WiseChatThemes
	*/
	private static $instance;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	private function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new WiseChatThemes();
		}
		
		return self::$instance;
	}
 
	public static function getAllThemes() {
		return self::$themes;
	}

	public function getMainTemplate() {
		return $this->getThemeProperty('mainTemplate');
	}
	
	public function getMessageTemplate() {
		return $this->getThemeProperty('messageTemplate');
	}
	
	public function getPasswordAuthorizationTemplate() {
		return $this->getThemeProperty('passwordAuthorization');
	}
	
	public function getAccessDeniedTemplate() {
		return $this->getThemeProperty('accessDenied');
	}

	public function getChannelUsersWidgetTemplate() {
		return $this->getThemeProperty('channelUsersWidgetTemplate');
	}

	public function getUserNameFormTemplate() {
		return $this->getThemeProperty('userName');
	}
	
	public function getCss() {
		return $this->getThemeProperty('css');
	}
	
	private function getThemeProperty($property) {
		$theme = $this->options->getEncodedOption('theme', 'lightgray');
		
		return self::$themesSettings[$theme][$property];
	}
}