<?php

/**
 * Wise Chat admin abstract tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
abstract class WiseChatAbstractTab {

	/**
	* @var WiseChatChannelsDAO
	*/
	protected $channelsDAO;

	/**
	* @var WiseChatBansDAO
	*/
	protected $bansDAO;

	/**
	* @var WiseChatKicksDAO
	*/
	protected $kicksDAO;
	
	/**
	* @var WiseChatUsersDAO
	*/
	protected $usersDAO;
	
	/**
	* @var WiseChatMessagesDAO
	*/
	protected $messagesDAO;

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	* @var WiseChatFiltersDAO
	*/
	protected $filtersDAO;

	/**
	 * @var WiseChatBansService
	 */
	protected $bansService;

	/**
	* @var WiseChatKicksService
	*/
	protected $kicksService;

	/**
	 * @var WiseChatMessagesService
	 */
	protected $messagesService;
	
	/**
	* @var WiseChatOptions
	*/
	protected $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->channelsDAO = WiseChatContainer::get('dao/WiseChatChannelsDAO');
		$this->bansDAO = WiseChatContainer::get('dao/WiseChatBansDAO');
		$this->kicksDAO = WiseChatContainer::get('dao/WiseChatKicksDAO');
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->messagesDAO = WiseChatContainer::get('dao/WiseChatMessagesDAO');
		$this->filtersDAO = WiseChatContainer::get('dao/WiseChatFiltersDAO');
		$this->actions = WiseChatContainer::getLazy('services/user/WiseChatActions');
		$this->bansService = WiseChatContainer::get('services/WiseChatBansService');
		$this->kicksService = WiseChatContainer::get('services/WiseChatKicksService');
		$this->messagesService = WiseChatContainer::get('services/WiseChatMessagesService');
		$this->messagesService = WiseChatContainer::get('services/WiseChatMessagesService');

		WiseChatContainer::load('services/WiseChatChannelsService');
	}

	/**
	 * Shows the message.
	 *
	 * @param string $message
	 */
	protected function addMessage($message) {
		set_transient("wc_admin_settings_message", $message, 10);
	}

	/**
	 * Shows error message.
	 *
	 * @param string $message
	 */
	protected function addErrorMessage($message) {
		set_transient("wc_admin_settings_error_message", $message, 10);
	}
	
	/**
	* Returns an array of fields displayed on the tab.
	*
	* @return array
	*/
	public abstract function getFields();
	
	/**
	* Returns an array of default values of fields.
	*
	* @return array
	*/
	public abstract function getDefaultValues();
	
	/**
	* Returns an array of parent fields.
	*
	* @return array
	*/
	public function getParentFields() {
		return array();
	}

	/**
	 * Returns an array of PRO fields.
	 *
	 * @return array
	 */
	public function getProFields() {
		return array();
	}

	/**
	* Filters values of fields.
	*
	* @param array $inputValue
	*
	* @return null
	*/
	public function sanitizeOptionValue($inputValue) {
		$newInputValue = array();
		
		foreach ($this->getFields() as $field) {
			$id = $field[0];
			if ($id === WiseChatSettings::SECTION_FIELD_KEY) {
				continue;
			}
			
			$type = $field[3];
			$value = array_key_exists($id, $inputValue) ? $inputValue[$id] : '';
			
			switch ($type) {
				case 'boolean':
					$newInputValue[$id] = isset($inputValue[$id]) && $value == '1' ? 1 : 0;
					break;
				case 'integer':
					if (isset($inputValue[$id])) {
						if (intval($value).'' != $value) {
							$newInputValue[$id] = '';
						} else {
							$newInputValue[$id] = absint($value);
						}
					}
					break;
				case 'string':
					if (isset($inputValue[$id])) {
						$newInputValue[$id] = sanitize_text_field($value);
					}
					break;
				case 'multilinestring':
				case 'rawString':
					if (isset($inputValue[$id])) {
						$newInputValue[$id] = $value;
					}
					break;
				case 'multivalues':
					if (isset($inputValue[$id]) && is_array($inputValue[$id])) {
						$newInputValue[$id] = $inputValue[$id];
					} else {
						$newInputValue[$id] = array();
					}
					
					break;
				case 'json':
					$newInputValue[$id] = is_array($value) ? json_encode($value) : '{}';
					break;
			}
		}
		
		return $newInputValue;
	}

	protected function printProFeatureNotice() {
		$button = '<a class="button-secondary wcAdminButtonPro" target="_blank" href="https://kainex.pl/projects/wp-plugins/wise-chat-pro?utm_source=wisechat&utm_medium=banner&utm_campaign=pro_feature" title="Check Wise Chat Pro">
						Check Wise Chat <strong>Pro</strong>
					</a>';
		printf('<p class="description wcProDescription">%s</p>', 'Notice: This feature is available after upgrading to Wise Chat Pro. '.$button);
	}

	/**
	* Callback method for displaying plain text field with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name and hint
	*/
	public function stringFieldCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$parentId = $this->getFieldParent($id);
		$isProFeature = in_array($id, $this->getProFields());

		printf(
			'<input type="text" id="%s" name="'.WiseChatOptions::OPTIONS_NAME.'[%s]" value="%s" %s data-parent-field="%s" />',
			$id, $id,
			$this->fixImunify360Rule($id, $this->options->getEncodedOption($id, $defaultValue)),
			$isProFeature || $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? ' disabled="1" ' : '',
			$parentId != null ? $parentId : ''
		);
		if (strlen($hint) > 0) {
			printf('<p class="description">%s</p>', $hint);
		}
		if ($isProFeature) {
			$this->printProFeatureNotice();
		}
	}

	/**
	 * Callback method for displaying plain text field with a hint. If the property is not defined the default value is used.
	 *
	 * @param array $args Array containing keys: id, name and hint
	 */
	public function rawStringFieldCallback($args) {
		$this->stringFieldCallback($args);
	}
	
	/**
	* Callback method for displaying multiline text field with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name and hint
	*/
	public function multilineFieldCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$parentId = $this->getFieldParent($id);
		$isProFeature = in_array($id, $this->getProFields());

		printf(
			'<textarea id="%s" name="'.WiseChatOptions::OPTIONS_NAME.'[%s]" cols="70" rows="6" %s data-parent-field="%s">%s</textarea>',
			$id, $id,
			$isProFeature || $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? ' disabled="1" ' : '',
			$parentId != null ? $parentId : '',
			$this->fixImunify360Rule($id, $this->options->getEncodedOption($id, $defaultValue))
		);
		if (strlen($hint) > 0) {
			printf('<p class="description">%s</p>', $hint);
		}
		if ($isProFeature) {
			$this->printProFeatureNotice();
		}
	}

	/**
	 * @see https://blog.imunify360.com/waf-rules-v.3.43-released  (rule #77142267)
	 * @param string $id
	 * @param string $value
	 * @return string
	 */
	protected function fixImunify360Rule($id, $value) {
		$affectedFields = array('spam_report_subject', 'spam_report_content');
		if (!in_array($id, $affectedFields)) {
			return $value;
		}

		return $this->fixImunify360RuleText($value);
	}

	protected function fixImunify360RuleText($value) {
		return str_replace('${', '{', $value);
	}
	
	/**
	* Callback method for displaying color selection text field with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name and hint
	*
	* @return null
	*/
	public function colorFieldCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$parentId = $this->getFieldParent($id);
	
		printf(
			'<input type="text" id="%s" name="'.WiseChatOptions::OPTIONS_NAME.'[%s]" value="%s" %s data-parent-field="%s" class="wc-color-picker" />',
			$id, $id,
			$this->options->getEncodedOption($id, $defaultValue),
			$parentId != null && !$this->options->isOptionEnabled($parentId, false) ? ' disabled="1" ' : '',
			$parentId != null ? $parentId : ''
		);
		if (strlen($hint) > 0) {
			printf('<p class="description">%s</p>', $hint);
		}
	}
	
	/**
	* Callback method for displaying boolean field (checkbox) with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name and hint
	*
	* @return null
	*/
	public function booleanFieldCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$parentId = $this->getFieldParent($id);
		$isProFeature = in_array($id, $this->getProFields());

		printf(
			'<input type="checkbox" id="%s" name="'.WiseChatOptions::OPTIONS_NAME.'[%s]" value="1" %s  %s data-parent-field="%s" />',
			$id, $id, 
			$this->options->isOptionEnabled($id, $defaultValue == 1) ? ' checked="1" ' : '',
			$isProFeature || $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? ' disabled="1" ' : '',
			$parentId != null ? $parentId : ''
		);
		if (strlen($hint) > 0) {
			printf('<p class="description">%s</p>', $hint);
		}
		if ($isProFeature) {
			$this->printProFeatureNotice();
		}
	}
	
	/**
	* Callback method for displaying select field with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name, hint, options
	*/
	public function selectCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$options = $args['options'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$value = $this->options->getEncodedOption($id, $defaultValue);
		$parentId = $this->getFieldParent($id);
		$isProFeature = in_array($id, $this->getProFields());

		$optionsHtml = '';
		foreach ($options as $name => $label) {
			$disabled = strpos($name, '_DISABLED_') !== false;
			$optionsHtml .= sprintf("<option value='%s'%s %s>%s</option>", $name, $name == $value ? ' selected="1"' : '', $disabled ? 'disabled' : '', $label);
		}
		
		printf(
			'<select id="%s" name="'.WiseChatOptions::OPTIONS_NAME.'[%s]" %s data-parent-field="%s">%s</select>',
			$id, $id,
			$isProFeature || $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? ' disabled="1" ' : '',
			$parentId != null ? $parentId : '',
			$optionsHtml
		);
		if (strlen($hint) > 0) {
			printf('<p class="description">%s</p>', $hint);
		}
		if ($isProFeature) {
			$this->printProFeatureNotice();
		}
	}

	/**
	* Callback method for displaying radio group with a hint. If the property is not defined the default value is used.
	*
	* @param array $args Array containing keys: id, name, hint, options
	*/
	public function radioCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$options = $args['options'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$value = $this->options->getEncodedOption($id, $defaultValue);
		$parentId = $this->getFieldParent($id);
		$isProFeature = in_array($id, $this->getProFields());

		$optionHints = array();
		foreach ($options as $optionValue => $optionDisplay) {
			$optionLabel = is_array($optionDisplay) ? $optionDisplay[0] : $optionDisplay;
			$radioId = $id.'_'.$optionValue;

			printf(
				"<label><input id='%s' class='wc-radio-option' data-radio-group-id='%s' type='radio' name='%s[%s]' value='%s' %s %s data-parent-field='%s' />%s&nbsp;&nbsp;&nbsp;&nbsp;</label>",
				$radioId, $id, WiseChatOptions::OPTIONS_NAME, $id, $optionValue,
				$optionValue == $value ? ' checked' : '',
				$isProFeature || $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? ' disabled="1" ' : '',
				$parentId != null ? $parentId : '',
				$optionLabel
			);

			if (is_array($optionDisplay) && count($optionDisplay) > 1) {
				$optionHints[] = sprintf(
					'<p class="description wc-radio-hint-group-%s wc-radio-hint-%s" %s>%s</p>',
					$id, $radioId, $optionValue == $value ? '' : 'style="display: none"', $optionDisplay[1]
				);
			}
		}

		print(implode('', $optionHints));

		if (strlen($hint) > 0) {
			printf('<p class="description">%s</p>', $hint);
		}
		if ($isProFeature) {
			$this->printProFeatureNotice();
		}
	}
	
	/**
	* Callback method for displaying list of checkboxes with a hint.
	*
	* @param array $args Array containing keys: id, name, hint, options
	*
	* @return null
	*/
	public function checkboxesCallback($args) {
		$id = $args['id'];
		$hint = $args['hint'];
		$options = $args['options'];
		$defaults = $this->getDefaultValues();
		$defaultValue = array_key_exists($id, $defaults) ? $defaults[$id] : '';
		$values = $this->options->getOption($id, $defaultValue);
		$parentId = $this->getFieldParent($id);
		$isProFeature = in_array($id, $this->getProFields());

		$html = '';
		foreach ($options as $key => $value) {
			$html .= sprintf(
				'<label><input type="checkbox" value="%s" name="%s[%s][]" %s %s data-parent-field="%s" />%s</label>&nbsp;&nbsp; ', 
				$key, WiseChatOptions::OPTIONS_NAME, $id, 
				in_array($key, (array) $values) ? 'checked="1"' : '',
				$isProFeature || $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? 'disabled="1"' : '',
				$parentId != null ? $parentId : '',
				$value
			);
		}
		
		printf($html);
		
		if (strlen($hint) > 0) {
			printf('<p class="description">%s</p>', $hint);
		}
		if ($isProFeature) {
			$this->printProFeatureNotice();
		}
	}
	
	/**
	* Callback method for displaying separator.
	*
	* @param array $args Array containing keys: name
	*
	* @return null
	*/
	public function separatorCallback($args) {
		$name = $args['name'];
		
		printf(
			'<p class="description">%s</p>',
			$name
		);
	}
	
	protected function getFieldParent($fieldId) {
		$parents = $this->getParentFields();
		if (is_array($parents) && array_key_exists($fieldId, $parents)) {
			return $parents[$fieldId];
		}
		
		return null;
	}
}