<?php

namespace Kainex\WiseChat\Integrations\Elementor;

use Kainex\WiseChat\Container;
use Kainex\WiseChat\Integrations\Elementor\Addons\WiseChatAddon;

/**
 * WiseChat Elementor integration class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatElementor {

	public function register($widgetsManager) {
		$widgetsManager->register(Container::getInstance()->get(WiseChatAddon::class));
	}

}