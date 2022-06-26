<?php 

/**
 * Wise Chat admin emoticons settings tab class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatEmoticonsTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'General Settings'),
			array(
				'emoticons_enabled', 'Emoticons Set', 'selectCallback', 'integer',
				'A collection of emoticons ready to insert into a message via Emoticon button (see Appearance settings).<br />
				<strong>Notice:</strong> This setting has no effect if Custom Emoticons option is enabled (see below).',
				self::getEmoticonSets()
			),
			array(
				'emoticons_size', 'Emoticon Size', 'selectCallback', 'integer', '', self::getEmoticonSize()
			),
			array('_section', 'Custom Emoticons', 'Compose and enable your own collection of emoticons.'),
			array('custom_emoticons_enabled', 'Enable Custom Emoticons', 'booleanFieldCallback', 'boolean', 'Enable custom set of emoticons. Below you can specify width of the emoticons layer and the list of emoticons.'),
			array('custom_emoticons_popup_width', 'Popup Width', 'stringFieldCallback', 'integer', 'Width of the emoticons popup (<strong>px</strong> unit). If the value is empty a default width is used.'),
			array('custom_emoticons_popup_height', 'Popup Height', 'stringFieldCallback', 'integer', 'Height of the emoticons popup (<strong>px</strong> unit). If the value is empty the height is set to contain all emoticons.'),
			array('custom_emoticons_emoticon_max_width_in_popup', 'Emoticon Width In Popup', 'stringFieldCallback', 'integer', 'Maximum width of a single emoticon in the popup (<strong>px</strong> unit). If the value is empty a default width is used.'),
			array('custom_emoticons_emoticon_width', 'Emoticon Width In Chat', 'selectCallback', 'string', 'Width of a single emoticon in the chat window.', WiseChatEmoticonsTab::getImageSizes()),
			array('custom_emoticon_add', 'New Emoticon', 'emoticonAddCallback', 'void'),
			array('custom_emoticons', 'Emoticons', 'emoticonsCallback', 'void'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'emoticons_enabled' => 1,
			'emoticons_size' => 32,
			'custom_emoticons_enabled' => 0,
			'custom_emoticons_popup_width' => '',
			'custom_emoticons_popup_height' => '',
			'custom_emoticons_emoticon_max_width_in_popup' => '',
			'custom_emoticons_emoticon_width' => ''
		);
	}

	public function getParentFields() {
		return array(
			'custom_emoticons_popup_width' => 'custom_emoticons_enabled',
			'custom_emoticons_popup_height' => 'custom_emoticons_enabled',
			'custom_emoticons_emoticon_max_width_in_popup' => 'custom_emoticons_enabled',
			'custom_emoticons_emoticon_width' => 'custom_emoticons_enabled',
		);
	}

	public function getProFields() {
		return array(
			'custom_emoticons_enabled', 'custom_emoticons_popup_width', 'custom_emoticons_popup_height', 'custom_emoticons_emoticon_max_width_in_popup',
			'custom_emoticons_emoticon_width'
		);
	}

	public static function getEmoticonSets() {
		return array(
			0 => '-- No emoticons --',
			1 => 'Set 1',
			'_DISABLED_pro_2' => 'Set 2 (available in Wise Chat Pro)',
			'_DISABLED_pro_3' => 'Set 3 (available in Wise Chat Pro)',
			'_DISABLED_pro_4' => 'Set 4 (available in Wise Chat Pro)',
		);
	}

	public static function getEmoticonSize() {
		return array(
			32 => '32',
			64 => '64',
			128 => '128',
		);
	}

	public static function getImageSizes() {
		$defaultNames = array(
			'thumbnail' => __('Thumbnail'),
			'medium' => __('Medium'),
			'medium_large' => __('Medium Large'),
			'large' => __('Large'),
			'full' => __('Full Size')
		);
		$sizes = get_intermediate_image_sizes();

		$sizesOut = array(
			'' => ''
		);
		foreach ($sizes as $size) {
			if (array_key_exists($size, $defaultNames)) {
				$sizesOut[$size] = $defaultNames[$size];
			} else {
				$sizesOut[$size] = $size;
			}
		}

		return $sizesOut;
	}

	public function emoticonAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addEmoticon");

		printf(
			'<input type="hidden" value="" id="newEmoticonId" name="newEmoticonId" />'.
			'<div id="newEmoticonImageContainerId"></div>'.
			'<button class="wc-image-picker button-secondary" data-parent-field="custom_emoticons_enabled" data-target-id="newEmoticonId" data-image-container-id="newEmoticonImageContainerId">Select Image</button>'.
			' | '.
			'<a class="button-primary new-emoticon-submit" href="%s" data-parent-field="custom_emoticons_enabled">Add Emoticon</a>'.
			'<p class="description">Select the image and click Add Emoticon button. Optionally you can choose a shortcut for the emoticon. For example - for smiley you might want to put the shortcut: <strong>:)</strong></p>',
			wp_nonce_url($url)
		);

		$this->printProFeatureNotice();
	}

	public function emoticonsCallback() {

		$html = "<table class='wp-list-table widefat emotstable'>";
		$html .= '<tr><td>No custom emoticons added yet. Use the form above in order to add you own emoticons.</td></tr>';
		$html .= "</table>";

		print($html);

		$this->printProFeatureNotice();
	}
}