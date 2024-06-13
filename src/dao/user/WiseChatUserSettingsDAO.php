<?php

/**
 * Wise Chat users settings DAO.
 * User settings are stored in a cookie and should be accessed only by the client side code.
 */
class WiseChatUserSettingsDAO {
	const USER_SETTINGS_COOKIE_NAME = 'wcUserSettings';

	/**
	 * @var WiseChatOptions
	 */
	protected $options;
	
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

	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}

	public function getDefaultSetting() {
		return array_merge(
			$this->defaultSettings, array(
				'muteSounds' => $this->options->isOptionEnabled('sounds_default_muted', false)
			)
		);
	}
	
	/**
	* Sets a key-value setting.
	*
	* @param string $settingName
	* @param string $settingValue
	* @param WiseChatUser $user
	*
	* @throws Exception If an error occurs
	*/
	public function setSetting($settingName, $settingValue, $user) {
		if (!in_array($settingName, array_keys($this->getDefaultSetting()))) {
			throw new Exception('Unsupported property');
		}

		$settings = $this->getUserCookieSettings();
		if (is_array($settings)) {
			$propertyType = $this->settingsTypes[$settingName];
			if ($propertyType == 'boolean') {
				$settingValue = $settingValue == 'true';
			}
			$settings[$settingName] = $settingValue;
			$this->setUserCookie(json_encode($settings));

			/**
			 * Fires once user setting has been set. User settings are stored in cookie only.
			 *
			 * @since 2.3.2
			 *
			 * @param string $settingName Setting name
			 * @param mixed $settingValue Setting value
			 * @param WiseChatUser $user The user object
			 */
			do_action("wc_usersetting_set", $settingName, $settingValue, $user);
		}
	}
	
	/**
	* Returns all user settings.
	*
	* @return array
	*/
	public function getAll() {
		if ($this->isUserCookieAvailable()) {
			return array_merge($this->getDefaultSetting(), $this->getUserCookieSettings());
		} else {
			return $this->getDefaultSetting();
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
		if (headers_sent()) {
			return;
		}
		setcookie(self::USER_SETTINGS_COOKIE_NAME, $value, strtotime('+60 days'), '/');
		$_COOKIE[self::USER_SETTINGS_COOKIE_NAME] = $value;
	}
	
	private function isUserCookieAvailable() {
		return array_key_exists(self::USER_SETTINGS_COOKIE_NAME, $_COOKIE);
	}
}