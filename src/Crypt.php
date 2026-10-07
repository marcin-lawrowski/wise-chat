<?php

namespace Kainex\WiseChat;

/**
 * WiseChat encryption support.
 *
 * @author Kainex <contact@kaine.pl>
 */
class Crypt {

	const METHOD = 'aes-256-cbc';

	/**
	 * @param string $string
	 * @return string
	 */
	public static function encryptToString($string) {
		$firstKey = get_option('wise_chat_key_1');
		$secondKey = get_option('wise_chat_key_2');
		$iv = base64_decode(get_option('wise_chat_iv'));

		$firstEncrypted = openssl_encrypt($string, self::METHOD, $firstKey, OPENSSL_RAW_DATA, $iv);
		$secondEncrypted = hash_hmac('sha3-512', $firstEncrypted, $secondKey, TRUE);

		return base64_encode($secondEncrypted.$firstEncrypted);
	}

	/**
	 * @param string $string
	 * @return string|null
	 */
	public static function decryptFromString($string) {
		$firstKey = get_option('wise_chat_key_1');
		$secondKey = get_option('wise_chat_key_2');
		$iv = base64_decode(get_option('wise_chat_iv'));
		$mix = base64_decode($string);

		$secondEncrypted = substr($mix, 0, 64);
		$firstEncrypted = substr($mix, 64);

		$data = openssl_decrypt($firstEncrypted, self::METHOD, $firstKey, OPENSSL_RAW_DATA, $iv);
		$secondEncryptedNew = hash_hmac('sha3-512', $firstEncrypted, $secondKey, TRUE);

		if (hash_equals($secondEncrypted, $secondEncryptedNew)) {
			return $data;
		}

		return null;
	}

}