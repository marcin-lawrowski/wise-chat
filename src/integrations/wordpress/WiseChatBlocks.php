<?php

/**
 * WiseChat Wordpress blocks support class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatBlocks {

	public function register() {
		include(WISE_CHAT_ROOT.'blocks/wise-chat/build/index.asset.php');

		register_block_type(WISE_CHAT_ROOT.'blocks/wise-chat/build', array(
			'render_callback' => array($this, 'renderWiseChatBlock')
		));
	}

	public function renderWiseChatBlock($attributes, $content) {
		if ($attributes['show_image_upload_button']) {
			$attributes['allow_post_images'] = '1';
			$attributes['enable_images_uploader'] = '1';
		} else {
			$attributes['allow_post_images'] = '';
			$attributes['enable_images_uploader'] = '';
		}
		if ($attributes['show_file_upload_button']) {
			$attributes['enable_attachments_uploader'] = '1';
		} else {
			$attributes['enable_attachments_uploader'] = '';
		}

	    return wise_chat_shortcode($attributes);
	}

}