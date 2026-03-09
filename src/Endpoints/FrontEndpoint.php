<?php

namespace Kainex\WiseChat\Endpoints;

use Kainex\WiseChat\Endpoints\Utils\EndpointUtils;
use Kainex\WiseChat\Traits\HttpUtils;
use Kainex\WiseChat\Crypt;

/**
 * Wise Chat core endpoint class.
 *
 * @author Kainex <contact@kainex.pl>
 */
abstract class FrontEndpoint {
	use HttpUtils;

	protected EndpointUtils $utils;

	/**
	 * @param EndpointUtils $utils
	 */
	public function __construct(EndpointUtils $utils) {
		$this->utils = $utils;
	}

	protected function verifyCheckSum() {
		$checksum = $this->getParam('checksum');

		if ($checksum !== null) {
			$decoded = unserialize(Crypt::decryptFromString(base64_decode($checksum)));
			if (is_array($decoded)) {
				$timestamp = array_key_exists('ts', $decoded) ? $decoded['ts'] : time();
				$validityTime = $this->utils->getOptions()->getIntegerOption('ajax_validity_time', 1440) * 60;
				if ($timestamp + $validityTime < time()) {
					$this->sendNotFoundStatus();
					die();
				}

				$this->utils->getOptions()->replaceOptions($decoded);
			}
		}
	}

	protected function generateCheckSum(): ?string {
		$checksum = $this->getParam('checksum');
		if ($checksum !== null) {
			$decoded = unserialize(Crypt::decryptFromString(base64_decode($checksum)));
			if (is_array($decoded)) {
				$decoded['ts'] = time();

				return base64_encode(Crypt::encryptToString(serialize($decoded)));
			}
		}
		return null;
	}

}