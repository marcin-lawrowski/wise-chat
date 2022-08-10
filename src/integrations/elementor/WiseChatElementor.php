<?php

/**
 * WiseChat Elementor integration class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatElementor {

	public function register($widgetsManager) {
		$widgetsManager->register(WiseChatContainer::get('integrations/elementor/addons/WiseChatAddon'));
	}

}