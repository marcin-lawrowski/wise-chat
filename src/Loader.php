<?php

namespace Kainex\WiseChat;

class Loader {
	const PREFIX = 'Kainex\WiseChat';

	/**
	 * A plain class loader.
	 */
	public static function install() {
		spl_autoload_register(function (string $class) {
			if (strpos($class, self::PREFIX) !== 0) {
				return;
			}
			$class = str_replace(self::PREFIX, '', $class);

			$filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
			include($filename);
		});
	}

}