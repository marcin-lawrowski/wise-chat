<?php 

/**
 * Wise Chat admin permissions settings tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatPermissionsTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Private Messaging Permissions', 'Rules of private messaging.', array('hideSubmitButton' => true)),

			array('permissions_pm_new_rule', 'New Rule', 'ruleAddCallback', 'void'),
			array('permissions_pm_rules', 'Rules', 'rulesCallback', 'void'),
		);
	}
	
	public function getDefaultValues() {
		return array(

		);
	}
	
	public function getParentFields() {
		return array(

		);
	}

	public function ruleAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addPMRule");

		$roles = $this->getRoles();
		$rolesOptions = array();
		foreach ($roles as $symbol => $name) {
			$rolesOptions[] = "<option value='{$symbol}'>{$name}</option>";
		}
		$rolesSelectSource = "<select name='newPmRuleSource' disabled>".implode('', $rolesOptions)."</select>";
		$rolesSelectTarget = "<select name='newPmRuleTarget' disabled>".implode('', $rolesOptions)."</select>";

		printf(
			'%s is allowed to send private messages to %s'.
			' | '.
			'<a class="button-primary new-pm-rule-submit" disabled href="%s">Add Rule</a>',
			$rolesSelectSource, $rolesSelectTarget, wp_nonce_url($url)
		);

		$this->printProFeatureNotice();
	}

	public function rulesCallback() {
		$html = "<table class='wp-list-table widefat emotstable'>";
		$html .= '<tr><td>No rules created. There are no restrictions to private messaging.</td></tr>';
		$html .= "</table>";
		$html .= "<p class='description'><strong>Notice:</strong> A first matching rule is applied and no further rules are processed.</p>";

		print($html);

		$this->printProFeatureNotice();
	}

	public function getRoles() {
		$rolesSpecialFirst = array(
			'_any' => 'Any User',
			'_anonymous' => 'Anonymous'
		);
		$rolesSpecialLast = array(
			'_fb' => 'Facebook User',
			'_go' => 'Google User',
			'_tw' => 'Twitter User',
		);
		return array_merge($rolesSpecialFirst, $this->getWPRoles(), $rolesSpecialLast);
	}
	
	public function getWPRoles() {
		$editableRoles = array_reverse(get_editable_roles());
		$rolesOptions = array();

		foreach ($editableRoles as $role => $details) {
			$name = translate_user_role($details['name']);
			$rolesOptions[esc_attr($role)] = $name;
		}
	
		return $rolesOptions;
	}
}