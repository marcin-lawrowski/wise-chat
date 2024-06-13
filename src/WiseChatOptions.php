<?php

/**
 * WiseChat class for accessing plugin options.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatOptions {
	const OPTIONS_NAME = 'wise_chat_options_name';
	const LAST_NAME_ID_OPTION = 'wise_chat_last_name_id';
    const EMOTICONS_BASE_DIR = "gfx/emoticons";
    const FLAGS_BASE_DIR = "gfx/flags";
    const ICONS_BASE_DIR = "gfx/icons";
    const ENGINE_CONFIG_FRESH_FLAG_TRANSIENT_KEY = 'wise_chat_engine_config_fresh';

	/**
	* @var WiseChatOptions
	*/
	private static $instance;
	
	/**
	* @var string Plugin's base directory (WWW relative)
	*/
	private $baseDir;
	
	/**
	* @var string Plugin's base directory
	*/
	private $pluginBaseDir;
	
	/**
	* @var array Raw options array
	*/
	private $options;
	
	private function __construct() {
		$this->options = get_option(WiseChatOptions::OPTIONS_NAME);
		if ($this->options === false) {
			$this->options = array();
		}
		add_action('update_option_'.WiseChatOptions::OPTIONS_NAME, array($this, 'onOptionsUpdated'), 10, 3);
	}
	
	public static function getInstance() {
		if (self::$instance === null) {
			$instance = new WiseChatOptions();
			$instance->baseDir = plugin_dir_url(dirname(__FILE__));
			$instance->pluginBaseDir = dirname(dirname(__FILE__));
			
			self::$instance = $instance;
		}
		
		return self::$instance;
	}

	/**
	 * Stores engines configuration file.
	 */
	public static function storeEngineConfig() {
		if (getenv('WC_ENV') === 'DEV') {
			return;
		}

		if (false === ($value = get_transient(self::ENGINE_CONFIG_FRESH_FLAG_TRANSIENT_KEY))) {
			$filePath = dirname(__FILE__).DIRECTORY_SEPARATOR.'endpoints'.DIRECTORY_SEPARATOR.'engines.json';
			if (file_exists($filePath) && is_writable($filePath)) {
				file_put_contents($filePath, json_encode([ 'abspath' => ABSPATH ]));
			}
			set_transient(self::ENGINE_CONFIG_FRESH_FLAG_TRANSIENT_KEY, '1', 24 * HOUR_IN_SECONDS);
		}
	}

	public function getBaseDir() {
		return $this->baseDir;
	}
	
	public function getPluginBaseDir() {
		return $this->pluginBaseDir;
	}

    public function getEmoticonsBaseURL() {
        return $this->getBaseDir().self::EMOTICONS_BASE_DIR.'/';
    }

	public function getIconsURL() {
		return $this->getBaseDir().self::ICONS_BASE_DIR.'/';
	}

    /**
     * @param string $countryIsoCode
     * @return string
     */
    public function getFlagURL($countryIsoCode) {
        return $this->getBaseDir().self::FLAGS_BASE_DIR.'/'.$countryIsoCode.'.png';
    }
	
	/**
	* Returns value of the boolean option.
	*
	* @param string $property Boolean property
	* @param boolean $default Default value if the property is not found
	*
	* @return boolean
	*/
	public function isOptionEnabled($property, $default = false) {
		if (!is_array($this->options) || !array_key_exists($property, $this->options)) {
			return $default;
		} else if ($this->options[$property] == '1') {
			return true;
		}
		
		return false;
	}
	
	/**
	* Returns text value of the given option.
	*
	* @param string $property String property
	* @param string $default Default value if the property is not found
	*
	* @return string
	*/
	public function getOption($property, $default = '') {
		$excludeFromI18n = array('message_max_length');

		if (!in_array($property, $excludeFromI18n)) {
			if (preg_match('/^message_/', $property) || in_array($property, array('hint_message', 'user_name_prefix', 'window_title', 'users_list_search_hint'))) {
				// translate the string using WordPress i18n:
				if (!array_key_exists('custom_i18n', $this->options) || $this->options['custom_i18n'] !== 1) {
					return $default;
				}
			}
		}

		return is_array($this->options) && array_key_exists($property, $this->options) ? $this->options[$property] : $default;
	}
	
	/**
	* Checks if the option is not empty.
	*
	* @param string $property String property
	*
	* @return boolean
	*/
	public function isOptionNotEmpty($property) {
		if (is_array($this->options) && array_key_exists($property, $this->options)) {
			if (is_array($this->options[$property])) {
				return count($this->options[$property]) > 0;
			} else if (strlen($this->options[$property]) > 0) {
				return true;
			}
		}

		return false;
	}
	
	/**
	* Returns HTML-encoded text value of the given option.
	*
	* @param string $property String property
	* @param string $default Default value if the property is not found
	*
	* @return string
	*/
	public function getEncodedOption($property, $default = '') {
		return htmlentities($this->getOption($property, $default), ENT_QUOTES, 'UTF-8');
	}
	
	/**
	* Returns integer value of the given option.
	*
	* @param string $property Integer value
	* @param integer $default Default value if the property is not found
	*
	* @return integer
	*/
	public function getIntegerOption($property, $default = 0) {
		return intval(is_array($this->options) && array_key_exists($property, $this->options) ? $this->options[$property] : $default);
	}
	
	/**
	* Replaces current options with given.
	*
	* @param array $options New options
	*
	* @return null
	*/
	public function replaceOptions($options) {
		// detect arrays in the following format: {element1, element2, ..., elementN} or {key1: element1, key2: element2, ..., keyN: elementN}
		foreach ($options as $key => $value) {
			if (is_string($value) && strlen($value) > 1 && $value[0] == '{' && $value[strlen($value) - 1] == '}') {
				$value = trim($value, '{}');

				$elements = array();
				$currentValue = null;
				for($i = 0; $i < strlen($value); $i++) {
					if ($value[$i] == ',') {
						if ($i > 0 && $value[$i - 1] != "\\") {
							if ($currentValue !== null) {
								$elements[] = trim($currentValue);
							}
							$currentValue = null;
						} else if ($i > 0 && $value[$i - 1] == "\\") {
							$currentValue = rtrim($currentValue, "\\");
							$currentValue .= ',';
						}
					} else {
						$currentValue .= $value[$i];
					}
				}
				if ($currentValue !== null) {
					$elements[] = trim($currentValue);
				}

				$transformedValues = array();
				foreach ($elements as $element) {
					$split = preg_split('/:/', $element);

					// detect "key: value" format:
					if (is_array($split)) {
						if (count($split) > 1) {
							$transformedValues[trim($split[0])] = trim(str_replace($split[0].':', '', $element));
						} else {
							$transformedValues[] = $element;
						}
					} else {
						$transformedValues[] = $element;
					}
				}
				$options[$key] = $transformedValues;
			}
 		}

		$this->options = array_merge(is_array($this->options) ? $this->options : array(), $options);
	}
	
	/**
	* Sets option's value.
	*
	* @param string $name
	* @param mixed $value
	*
	* @return null
	*/
	public function setOption($name, $value) {
		$this->options[$name] = $value;
	}
	
	/**
	* Saves all options.
	*
	* @return null
	*/
	public function saveOptions() {
		update_option(WiseChatOptions::OPTIONS_NAME, $this->options);
	}

	/**
	 * Fired when the options are saved.
	 *
	 * @param mixed $oldValue
	 * @param mixed $value
	 * @param string $option
	 */
	public function onOptionsUpdated($oldValue, $value, $option) {
		delete_transient('wise_chat_wp_users_cache');
	}
	
	/**
	* Deletes all options from WordPress DB.
	*
	* @return null
	*/
	public function dropAllOptions() {
		delete_option(WiseChatOptions::OPTIONS_NAME);
		delete_option(WiseChatOptions::LAST_NAME_ID_OPTION);
	}

	/**
	 * Returns suffix of the anonymous username.
	 *
	 * @return integer
	 */
	public function getUserNameSuffix() {
		return intval(get_option(self::LAST_NAME_ID_OPTION, 1));
	}

	/**
	 * Sets suffix of the anonymous username.
	 *
	 * @param integer $suffix
	 *
	 * @return null
	 */
	public function setUserNameSuffix($suffix) {
		update_option(self::LAST_NAME_ID_OPTION, $suffix);
	}

	/**
	 * Resets username suffix.
	 *
	 * @return null
	 */
	public function resetUserNameSuffix() {
		update_option(self::LAST_NAME_ID_OPTION, 0);
	}
	
	/**
	* Dumps all options to stdout.
	*
	* @return null
	*/
	public function dump() {
		foreach ($this->options as $key => $value) {
			echo "$key=\"$value\"\n";
		}
	}
}