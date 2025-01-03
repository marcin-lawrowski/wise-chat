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
	 * @return array Returns an array of PRO fields.
	 */
	public function getProFields() {
		return array();
	}

	/**
	 * @return array Returns an array of PRO fields.
	 */
	public function getProAiFields() {
		return array();
	}

	/**
	* @return array
	*/
	public function getRadioGroups() {
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

			$type = isset($field[3]) ? $field[3] : '';
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

	protected function printProAiFeatureNotice() {
		$button = '<a class="button-secondary wcAdminButtonProAI" target="_blank" href="https://kainex.pl/projects/wp-plugins/wise-chat-pro-ai?utm_source=wisechat&utm_medium=banner&utm_campaign=pro_ai_feature" title="Check Wise Chat Pro with AI">
						Check Wise Chat <strong>Pro with AI</strong>
					</a>';
		printf('<p class="description wcProDescription">%s</p>', 'Notice: This feature is available after upgrading to Wise Chat Pro with AI. '.$button);
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
		$isProAiFeature = in_array($id, $this->getProAiFields());

		printf(
			'<input type="text" id="%s" name="'.WiseChatOptions::OPTIONS_NAME.'[%s]" value="%s" %s data-parent-field="%s" />',
			$id, $id,
			$this->fixImunify360Rule($id, $this->options->getEncodedOption($id, $defaultValue)),
			$isProFeature || $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? ' disabled="1" ' : '',
			$parentId != null ? $parentId : ''
		);
		if ($hint) {
			printf('<p class="description">%s</p>', $hint);
		}
		if ($isProFeature) {
			$this->printProFeatureNotice();
		}
		if ($isProAiFeature) {
			$this->printProAiFeatureNotice();
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
		if ($hint) {
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
		if ($hint) {
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
		if ($hint) {
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
		if ($hint) {
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
		$groups = $this->getRadioGroups();
		$groupDef = array();
		if (array_key_exists($id, $groups)) {
			$groupDef = $groups[$id];
		}
		$isProFeature = in_array($id, $this->getProFields());

		$optionHints = array();
		foreach ($options as $optionValue => $optionDisplay) {
			$optionLabel = is_array($optionDisplay) ? $optionDisplay[0] : $optionDisplay;
			$radioId = $id.'_'.$optionValue;

			printf(
				"<label><input id='%s' class='wc-radio-option' data-radio-group-id='%s' type='radio' name='%s[%s]' value='%s' %s %s data-parent-field='%s' data-group-def='%s'/>%s&nbsp;&nbsp;&nbsp;&nbsp;</label>",
				$radioId, $id, WiseChatOptions::OPTIONS_NAME, $id, $optionValue,
				$optionValue == $value ? ' checked' : '',
				$isProFeature || $parentId != null && !$this->options->isOptionEnabled($parentId, false) ? ' disabled="1" ' : '',
				$parentId != null ? $parentId : '',
				array_key_exists($optionValue, $groupDef) ? implode(',', $groupDef[$optionValue]) : '',
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

		if ($hint) {
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
		
		if ($hint) {
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

	public function deleteUserFromListAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteUserFromList')) {
			return;
		}
		if (!isset($_GET['index']) || !isset($_GET['source'])) {
			return;
		}

		$index = $_GET['index'];
		$source = $_GET['source'];
		$index = intval($index);
		$accessUsers = (array) $this->options->getOption($source, array());
		if ($index < count($accessUsers)) {
			unset($accessUsers[$index]);
			$this->options->setOption($source, array_values($accessUsers));
			$this->options->saveOptions();
			$this->addMessage('User has been removed from the list');
		}
	}

	public function addUserToListAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'addUserToList')) {
			return;
		}
		if (!isset($_GET['userLogin']) || !isset($_GET['source'])) {
			return;
		}

		$source = $_GET['source'];
		$userLogin = trim($_GET['userLogin']);
		$wpUser = $this->usersDAO->getWpUserByLogin($userLogin);
		if ($wpUser === null) {
			$this->addErrorMessage('User login is not correct');
		} else {
			$accessUsers = (array) $this->options->getOption($source, array());
			$accessUsers[] = $wpUser->ID;
			$this->options->setOption($source, $accessUsers);
			$this->options->saveOptions();

			$this->addMessage("User has been added to the list");
		}
	}

	protected function usersCallback($optionName, $hintHtml) {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		$users = (array) $this->options->getOption($optionName, array());

		$html = "<div style='height: 150px; overflow-y: auto; border: 1px solid #aaa; padding: 5px;'>";
		if (count($users) == 0) {
			$html .= '<small>No users were added yet</small>';
		} else {
			$html .= '<table class="wp-list-table widefat fixed striped users wcCondensedTable">';
			$html .= '<tr><th style="width:100px">ID</th><th>Login</th><th>Display Name</th><th></th></tr>';
			foreach ($users as $userKey => $userID) {
				$deleteURL = $url . '&wc_action=deleteUserFromList&index=' . $userKey.'&source='.$optionName.'&nonce='.wp_create_nonce('deleteUserFromList');
				$deleteLink = "<a href='{$deleteURL}' onclick='return confirm(\"Are you sure?\")'>Delete</a><br />";
				$user = $this->usersDAO->getWpUserByID(intval($userID));
				if ($user !== null) {
					$html .= sprintf("<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>", $userID, $user->user_login, $user->display_name, $deleteLink);
				} else {
					$html .= sprintf("<tr><td>%d</td><td colspan='2'>Unknown user</td><td>%s</td></tr>", $userID, $deleteLink);
				}
			}
			$html .= '</table>';
		}
		$html .= "</div>";
		$html .= '<p class="description">'.$hintHtml.'</p>';
		print($html);
		$this->printProFeatureNotice();
	}

	public function userAddCallback($optionName, $disabled = true) {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addUserToList&source=".$optionName.'&nonce='.wp_create_nonce('addUserToList'));

		printf(
			'<input type="text" value="" placeholder="User login" id="userSelector%s" %s class="wcUserLoginHint" />'.
			'<a class="button-secondary" href="" disabled title="Adds user to the list" onclick="%s">Add User</a>',
			$optionName, $disabled ? 'disabled' : '',
			'this.href += \'&userLogin=\' + encodeURIComponent(jQuery(\'#userSelector'.$optionName.'\').val());'
		);
		$this->printProFeatureNotice();
	}

}