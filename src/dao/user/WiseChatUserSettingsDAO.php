<?php

/**
 * Wise Chat users settings DAO.
 * User settings are stored in a cookie and should be accessed only by the client side code.
 */
class WiseChatUserSettingsDAO {
	const USER_SETTINGS_COOKIE_NAME = 'wcUserSettings';
	
	/**
	* @var array Array of default values
	*/
	private $defaultSettings = array(
		'muteSounds' => false
	);
	
	/**
	* @var array Array of settings' types
	*/
	private $settingsTypes = array(
		'muteSounds' => 'boolean'
	);
	
	/**
	* Initialization of the settings. The method should be invoked before sending the headers
	* because it sets cookies.
	*/
	public function initialize() {
		if (!$this->isUserCookieAvailable()) {
			$this->setUserCookie('{}');
		}
	}
	
	/**
	* Sets a key-value setting.
	*
	* @param string $settingName
	* @param string $settingValue
	*
	* @throws Exception If an error occurs
	*/
	public function setSetting($settingName, $settingValue) {
		if (!in_array($settingName, array_keys($this->defaultSettings))) {
			throw new Exception('Unsupported property');
		}
		
		if ($this->isUserCookieAvailable()) {
			$settings = $this->getUserCookieSettings();
			if (is_array($settings)) {
				$propertyType = $this->settingsTypes[$settingName];
				if ($propertyType == 'boolean') {
					$settingValue = $settingValue == 'true';
				}
				$settings[$settingName] = $settingValue;
				$this->setUserCookie(json_encode($settings));
			}
		}
	}
	
	/**
	* Returns all user settings.
	*
	* @return array
	*/
	public function getAll() {
		if ($this->isUserCookieAvailable()) {
			$cookieValue = stripslashes_deep($_COOKIE[self::USER_SETTINGS_COOKIE_NAME]);
			return array_merge($this->defaultSettings, json_decode($cookieValue, true));
		} else {
			return array();
		}
	}
	
	/**
	* Returns all settings from the cookie.
	*
	* @return array
	*/
	private function getUserCookieSettings() {
		if ($this->isUserCookieAvailable()) {
			$cookieValue = stripslashes_deep($_COOKIE[self::USER_SETTINGS_COOKIE_NAME]);
			return json_decode($cookieValue, true);
		} else {
			return array();
		}
	}
	
	private function setUserCookie($value) {
		setcookie(self::USER_SETTINGS_COOKIE_NAME, $value, strtotime('+60 days'), '/');
		$_COOKIE[self::USER_SETTINGS_COOKIE_NAME] = $value;
	}
	
	private function isUserCookieAvailable() {
		return array_key_exists(self::USER_SETTINGS_COOKIE_NAME, $_COOKIE);
	}
}