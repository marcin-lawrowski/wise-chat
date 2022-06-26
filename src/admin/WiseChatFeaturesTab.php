<?php

/**
 * Wise Chat features.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatFeaturesTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Messages Reactions', 'Configure reactions to messages.'),
			array('reactions_mode', 'Mode', 'radioCallback', 'string', '', self::getReactionModes()),
			array('reactions_custom', 'Custom Reactions', 'customReactionsCallback', 'json'),
			array('reactions_buttons_mode', 'Buttons Mode', 'selectCallback', 'string', 'Select the appearance of reaction activate / deactivate buttons', self::getReactionsButtonsMode()),
			array('reactions_buttons_group', 'Group Buttons', 'booleanFieldCallback', 'boolean', 'Group reaction buttons in popup'),
			array('reactions_actions', '', 'reactionsActionsCallback', 'void'),
		);
	}

	public function getProFields() {
        return array(
        	'reactions_mode', 'reactions_custom', 'reactions_buttons_mode', 'reactions_buttons_group', 'reactions_actions'
        );
    }

	public static function getReactionsButtonsMode() {
		return array(
			'text' => 'Text Only',
			'icon_text' => 'Icon and text',
			'icon' => 'Icon Only',
		);
	}

	public static function getReactionModes() {
		return array(
			'' => 'Disabled',
			'like' => array('Like', 'Display Like button only'),
			'like_love' => array('Like | Love', 'Display Like and Love buttons'),
			'like_love_sad' => array('Like | Love | Sad', 'Display Like, Love and Sad buttons'),
			'custom' => array('Custom', 'Define a custom set of reactions. Please configure up to 7 reactions below.'),
		);
	}

	public function getDefaultValues() {
		return array(
			'reactions_mode' => 'like',
			'reactions_buttons_group' => 0,
			'reactions_buttons_mode' => 'icon_text',
		);
	}

	public function customReactionsCallback() {
		$custom = array();

		$html = "<table class='wp-list-table widefat'>";
		$html .= '<thead><tr><td width="30">No.</td><td>Action Name</td><td>Active Reaction</td><td>Image</td><td>Counter Image</td></tr></thead>';

		for ($i = 1; $i <= 7; $i++) {
			$classes = $i % 2 == 0 ? 'alternate' : '';

			if (!array_key_exists($i, $custom)) {
				$custom[$i] = array(
					'action' => '', 'active' => '', 'image' => '', 'imageSm' => '',
				);
			}

			$key = $i - 1;
			$idInput = sprintf(
				'<input type="hidden" name="%s[reactions_custom][%d][id]" value="%d">',
				WiseChatOptions::OPTIONS_NAME, $key, $i
			);
			$actionInput = sprintf(
				'<input type="text" name="%s[reactions_custom][%d][action]" disabled value="%s" maxlength="100" style="max-width: 100px;">%s',
				WiseChatOptions::OPTIONS_NAME, $key, htmlspecialchars($custom[$key]['action']), $i === 1 ? '<p class="description">e.g. Like</p>' : ''
			);
			$activeInput = sprintf(
				'<input type="text" name="%s[reactions_custom][%d][active]" disabled value="%s" maxlength="100" style="max-width: 100px;">%s',
				WiseChatOptions::OPTIONS_NAME, $key, htmlspecialchars($custom[$key]['active']), $i === 1 ? '<p class="description">e.g. I like it</p>' : ''
			);

			$imageTag = '';
			if ($custom[$key]['image'] > 0) {
				$imageUrl = wp_get_attachment_url($custom[$key]['image']);
				if ($imageUrl) {
					$imageTag = '<img src="'.$imageUrl.'" style="max-width: 100px;">';
				}
			}

			$imageInput = sprintf(
				'<input type="hidden" value="%d" id="reactions_custom_%d_image" name="%s[reactions_custom][%d][image]" />'.
				'<div id="reactions_custom_%d_image_container">%s</div>'.
				'<button class="wc-image-picker button-secondary" disabled data-target-id="reactions_custom_%d_image" data-image-container-id="reactions_custom_%d_image_container">Browse</button>%s',
				htmlspecialchars($custom[$key]['image']), $i, WiseChatOptions::OPTIONS_NAME, $key, $i, $imageTag, $i, $i, $i === 1 ? '<p class="description">max. 48x48</p>' : ''
			);

			$imageSmTag = '';
			if ($custom[$key]['imageSm'] > 0) {
				$imageUrl = wp_get_attachment_url($custom[$key]['imageSm']);
				if ($imageUrl) {
					$imageSmTag = '<img src="'.$imageUrl.'" style="max-width: 100px;">';
				}
			}

			$imageSmInput = sprintf(
				'<input type="hidden" value="%d" id="reactions_custom_%d_image_sm" name="%s[reactions_custom][%d][imageSm]" />'.
				'<div id="reactions_custom_%d_image_sm_container">%s</div>'.
				'<button class="wc-image-picker button-secondary" disabled data-target-id="reactions_custom_%d_image_sm" data-image-container-id="reactions_custom_%d_image_sm_container">Browse</button>%s',
				htmlspecialchars($custom[$key]['imageSm']), $i, WiseChatOptions::OPTIONS_NAME, $key, $i, $imageSmTag, $i, $i, $i === 1 ? '<p class="description">max. 48x48</p>' : ''
			);

			$html .= sprintf(
				'<tr class="%s">
					<td>%s.</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>
				</tr>',
				$classes, $i, $idInput.$actionInput, $activeInput, $imageInput, $imageSmInput
			);
		}
		$html .= "</table><p class=\"description\"><strong>Notice:</strong> you need to specify all columns in a row to enable a reaction</p>";

		print($html);

		$this->printProFeatureNotice();
	}

	public function reactionsActionsCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=clearAllReactions");

		printf(
			'<a class="button-secondary" href="%s" disabled title="Deletes reactions sent to all messages" onclick="return confirm(\'Are you sure? All reactions will be lost.\')">Clear All Reactions</a>',
			wp_nonce_url($url)
		);

		$this->printProFeatureNotice();
	}

}