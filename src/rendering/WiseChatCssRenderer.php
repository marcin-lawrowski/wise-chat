<?php

/**
 * Wise Chat CSS styles rendering.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatCssRenderer {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var string
	*/
	private $containerId;
	
	/**
	* @var array
	*/
	private $definitions;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	/**
	* Returns CSS styles definition for the plugin.
	*
	* @param string $containerId ID of the chat HTML container
	*
	* @return string HTML source
	*/
	public function getCssDefinition($containerId) {
		$this->containerId = $containerId;
		$this->definitions = array();
		
		$this->addDefinition('*', 'text_color_chat', 'color');
		
		if ($this->options->isOptionNotEmpty('text_size_chat')) {
			$this->addDefinition('', 'text_size_chat', 'font-size');
			$this->addRawDefinition('*', 'font-size', 'inherit');
		}
		
		$this->addDefinition('', 'background_color_chat', 'background-color');
		$this->addDefinition('.wcControls', 'background_color_chat', 'background-color');
		if ($this->options->isOptionNotEmpty('background_color_chat')) {
			$this->addRawDefinition('.wcControls', 'background', 'none');
			$this->addDefinition('.wcControls', 'background_color_chat', 'background-color');
		}
		
		$this->addDefinition('.wcMessages', 'background_color', 'background-color');
		
		$this->addDefinition('.wcMessages .wcMessage *', 'text_color', 'color');
		
		$this->addDefinition('.wcMessages .wcMessageUser', 'text_color_user', 'color');
		$this->addDefinition('.wcMessages .wcMessageUser a', 'text_color_user', 'color');
		
		$this->addDefinition('.wcWpMessage .wcMessageUser', 'text_color_logged_user', 'color');
		$this->addDefinition('.wcWpMessage .wcMessageUser a', 'text_color_logged_user', 'color');
		
		if ($this->options->isOptionNotEmpty('text_size')) {
			$this->addDefinition('.wcMessages', 'text_size', 'font-size');
			$this->addRawDefinition('.wcMessages *', 'font-size', 'inherit');
		}
		
		$this->addDefinition('.wcControls', 'background_color_input', 'background-color');
		$this->addDefinition('.wcControls span', 'background_color_input', 'background-color');
		$this->addDefinition('.wcControls a', 'background_color_input', 'background-color');
		$this->addDefinition('.wcControls label', 'background_color_input', 'background-color');
		
		$this->addDefinition('.wcControls', 'text_color_input_field', 'color');
		$this->addDefinition('.wcControls span', 'text_color_input_field', 'color');
		$this->addDefinition('.wcControls a', 'text_color_input_field', 'color');
		$this->addDefinition('.wcControls label', 'text_color_input_field', 'color');
		
		$this->addDefinition('.wcUsersList', 'background_color_users_list', 'background-color');
		$this->addDefinition('.wcUsersList', 'text_color_users_list', 'color');
		$this->addDefinition('.wcUsersList *', 'text_color_users_list', 'color');
		
		if ($this->options->isOptionNotEmpty('text_size_users_list')) {
			$this->addDefinition('.wcUsersList', 'text_size_users_list', 'font-size');
			$this->addRawDefinition('.wcUsersList *', 'font-size', 'inherit');
		}
		
		$this->addLengthDefinition('', 'chat_width', 'width');
		$this->addLengthDefinition('.wcMessages', 'chat_height', 'height');
		$this->addLengthDefinition('.wcUsersList', 'chat_height', 'height');

		$this->addUsersListWidthDefinition();
		
		return $this->getDefinitions();
	}
	
	/**
	* Returns custom CSS styles definition for the plugin.
	*
	* @return string HTML source
	*/
	public function getCustomCssDefinition() {
		if ($this->options->isOptionNotEmpty('custom_styles')) {
			return sprintf("<style type='text/css'>\n%s\n</style>", $this->options->getOption('custom_styles'));
		}
		
		return '';
	}
	
	/**
	* Adds a single style definition.
	*
	* @param string $cssSelector
	* @param string $property
	* @param string $cssProperty
	*
	* @return null
	*/
	private function addDefinition($cssSelector, $property, $cssProperty) {
		if ($this->options->isOptionNotEmpty($property)) {
			$this->addRawDefinition($cssSelector, $cssProperty, $this->options->getOption($property));
		}
	}
	
	/**
	* Adds a raw style definition.
	*
	* @param string $cssSelector
	* @param string $property
	* @param string $value
	*
	* @return null
	*/
	private function addRawDefinition($cssSelector, $property, $value) {
		$fullCssSelector = sprintf("#%s %s", $this->containerId, $cssSelector);
		$this->definitions[$fullCssSelector][] = sprintf("%s: %s;", $property, $value);
	}
	
	/**
	* Adds single length style definition.
	*
	* @param string $cssSelector
	* @param string $lengthProperty
	* @param string $cssProperty
	*
	* @return null
	*/
	private function addLengthDefinition($cssSelector, $lengthProperty, $cssProperty) {
		if ($this->options->isOptionNotEmpty($lengthProperty)) {
			$value = $this->options->getOption($lengthProperty);
			if (preg_match('/^\d+$/', $value)) {
				$value .= 'px';
			}
			if (preg_match('/^\d+((px)|%)$/', $value)) {
				$this->addRawDefinition($cssSelector, $cssProperty, $value);
			}
		}
	}

	private function addUsersListWidthDefinition() {
		if ($this->options->isOptionNotEmpty('users_list_width')) {
			$width = $this->options->getIntegerOption('users_list_width');
			if ($width > 1 && $width < 99) {
				$this->addRawDefinition('.wcMessages', 'width', (100 - $width).'%');
			}
		}
	}
	
	/**
	* Returns rendered styles definition. 
	*
	* @return string HTML source
	*/
	private function getDefinitions() {
		$html = '';
		foreach ($this->definitions as $cssSelector => $stylesList) {
			$html .= "$cssSelector { ".implode(" ", $stylesList)." }\n";
		}
		
		return sprintf('<style type="text/css">%s</style>', $html);
	}
}