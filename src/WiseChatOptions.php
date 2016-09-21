<?php

/**
 * WiseChat class for accessing plugin options.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatOptions {
	const OPTIONS_NAME = 'wise_chat_options_name';
	const LAST_NAME_ID_OPTION = 'wise_chat_last_name_id';
    const EMOTICONS_BASE_DIR = "gfx/emoticons";
    const FLAGS_BASE_DIR = "gfx/flags";

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
	
	public function getBaseDir() {
		return $this->baseDir;
	}
	
	public function getPluginBaseDir() {
		return $this->pluginBaseDir;
	}

    public function getEmoticonsBaseURL() {
        return $this->getBaseDir().self::EMOTICONS_BASE_DIR.'/';
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
		return is_array($this->options) && array_key_exists($property, $this->options) && strlen($this->options[$property]) > 0;
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
		// detect arrays in the following format: {element1,element2,...,elementN}
		foreach ($options as $key => $value) {
			if (strlen($value) > 1 && $value[0] == '{' && $value[strlen($value) - 1] == '}') {
				$value = trim($value, '{}');
				$split = preg_split('/,/', $value);
				$options[$key] = $split;
			}
		}

		$this->options = array_merge(is_array($this->options) ? $this->options : array(), $options);
	}
	
	/**
	* Sets option's value.
	*
	* @param string $name
	* @param string $value
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