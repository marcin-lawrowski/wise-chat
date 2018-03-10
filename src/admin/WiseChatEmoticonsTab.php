<?php

/**
 * Wise Chat admin emoticons settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatEmoticonsTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Custom Emoticons', 'Below you can compose and enable your own set of emoticons.'),
			array('custom_emoticons_enabled', 'Enable Custom Emoticons', 'booleanFieldCallback', 'boolean', 'Enable custom set of emoticons. Below you can specify width of the emoticons layer and the list of emoticons.'),
			array('custom_emoticons_popup_width', 'Popup Width', 'stringFieldCallback', 'integer', 'Width of the emoticons popup (<strong>px</strong> unit). If the value is empty a default width is used.'),
			array('custom_emoticons_popup_height', 'Popup Height', 'stringFieldCallback', 'integer', 'Height of the emoticons popup (<strong>px</strong> unit). If the value is empty the height is set to contain all emoticons.'),
			array('custom_emoticons_emoticon_max_width_in_popup', 'Emoticon Width In Popup', 'stringFieldCallback', 'integer', 'Maximum width of a single emoticon in the popup (<strong>px</strong> unit). If the value is empty a default width is used.'),
			array('custom_emoticons_emoticon_width', 'Emoticon Width In Chat', 'selectCallback', 'string', 'Width of a single emoticon in the chat window.', WiseChatEmoticonsTab::getImageSizes()),
			array('custom_emoticon_add', 'New Emoticon', 'emoticonAddCallback', 'void'),
			array('custom_emoticons', 'Emoticons', 'emoticonsCallback', 'void'),
		);
	}

	public function getProFields() {
		return array(
			'custom_emoticons_enabled', 'custom_emoticons_popup_width', 'custom_emoticons_popup_height', 'custom_emoticons_emoticon_max_width_in_popup',
			'custom_emoticons_emoticon_width', 'custom_emoticon_add', 'custom_emoticons',
		);
	}

	public function getDefaultValues() {
		return array(
			'custom_emoticons_enabled' => 0,
			'custom_emoticons_popup_width' => '',
			'custom_emoticons_popup_height' => '',
			'custom_emoticons_emoticon_max_width_in_popup' => '',
			'custom_emoticons_emoticon_width' => ''
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
			'<button class="wc-image-picker button-secondary" disabled data-parent-field="custom_emoticons_enabled" data-target-id="newEmoticonId" data-image-container-id="newEmoticonImageContainerId">Select Image</button>'.
			'<input type="text" value="" id="newEmoticonAlias" disabled data-parent-field="custom_emoticons_enabled" name="newEmoticonAlias" placeholder="Shortcut" autocomplete="false" />'.
			' | '.
			'<a class="button-primary new-emoticon-submit" href="%s" disabled data-parent-field="custom_emoticons_enabled">Add Emoticon</a>'.
			'<p class="description">Select the image and click Add Emoticon button. Optionally you can choose a shortcut for the emoticon. For example - for smiley you might want to put the shortcut: <strong>:)</strong></p>'.
			'<p class="description wcProDescription">Notice: This feature is available after upgrading to Wise Chat Pro.
				<a class="button-secondary wcAdminButtonPro" target="_blank" href="https://kaine.pl/projects/wp-plugins/wise-chat-pro?source=pro-field" title="Check Wise Chat Pro">Check Wise Chat <strong>Pro</strong></a>
			</p>',
			wp_nonce_url($url)
		);
	}

	public function emoticonsCallback() {
		$emoticons = array();

		$html = "<table class='wp-list-table widefat emotstable'>";
		$html .= '<tr><td>No custom emoticons added yet. Use the form above in order to add you own emoticons.</td></tr>';
		$html .= "</table>";
		$html .= '<p class="description wcProDescription">Notice: This feature is available after upgrading to Wise Chat Pro.
			<a class="button-secondary wcAdminButtonPro" target="_blank" href="https://kaine.pl/projects/wp-plugins/wise-chat-pro?source=pro-field" title="Check Wise Chat Pro">Check Wise Chat <strong>Pro</strong></a>
		</p>';

		print($html);
	}
}