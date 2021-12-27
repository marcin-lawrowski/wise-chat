<?php

/**
 * WiseChat encryption support.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatCrypt {
	const PUBLIC_KEY = 'Ci0tLS0tQkVHSU4gUFVCTElDIEtFWS0tLS0tCk1Gd3dEUVlKS29aSWh2Y05BUUVCQlFBRFN3QXdTQUpCQU4rc1p5VkVMOURDWkRQNks3Nml3dnBJb2J5NWQrUDkKUmtZRzRDQkJaQ3Q5dnh3N1dqLzhlbjk3RU9ENW53bDJqVFAzalRPNmFhRHdGRFc0dDU5U3Baa0NBd0VBQVE9PQotLS0tLUVORCBQVUJMSUMgS0VZLS0tLS0K';

	/**
	 * @param string $string
	 * @return string
	 */
	public static function encryptToString($string) {
		$secretKey = self::PUBLIC_KEY;
		$secretIv = self::getSalt();

		$encryptMethod = "AES-256-CBC";
		$key = hash('sha256', $secretKey);
		$iv = substr(hash('sha256', $secretIv), 0, 16);

		return base64_encode(openssl_encrypt($string, $encryptMethod, $key, 0, $iv));
	}

	/**
	 * @param string $string
	 * @return string|null
	 */
	public static function decryptFromString($string) {
		$secretKey = self::PUBLIC_KEY;
		$secretIv = self::getSalt();

		$encryptMethod = "AES-256-CBC";
		$key = hash('sha256', $secretKey);
		$iv = substr(hash('sha256', $secretIv), 0, 16);

		$output = openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $iv);

		return is_string($output) ? $output : null;
	}
	
	/**
	* Returns unique salt for WordPress installation.
	*
	* @return string
	*/
	private static function getSalt() {
		return wp_hash('MVBTY09CMHY0a0Z0a2NkcjlsZnZLcDZiSkZpYUhUL3VoWVh0OQotLS0tL');
	}
}