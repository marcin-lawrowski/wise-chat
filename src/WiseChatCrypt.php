<?php

/**
 * WiseChat encryption support.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatCrypt {
	const KEY_BITS = 512;
	const PUBLIC_KEY = 'Ci0tLS0tQkVHSU4gUFVCTElDIEtFWS0tLS0tCk1Gd3dEUVlKS29aSWh2Y05BUUVCQlFBRFN3QXdTQUpCQU4rc1p5VkVMOURDWkRQNks3Nml3dnBJb2J5NWQrUDkKUmtZRzRDQkJaQ3Q5dnh3N1dqLzhlbjk3RU9ENW53bDJqVFAzalRPNmFhRHdGRFc0dDU5U3Baa0NBd0VBQVE9PQotLS0tLUVORCBQVUJMSUMgS0VZLS0tLS0K';

	const PRIVATE_KEY = 'Ci0tLS0tQkVHSU4gUlNBIFBSSVZBVEUgS0VZLS0tLS0KTUlJQk9nSUJBQUpCQU4rc1p5VkVMOURDWkRQNks3Nml3dnBJb2J5NWQrUDlSa1lHNENCQlpDdDl2eHc3V2ovOAplbjk3RU9ENW53bDJqVFAzalRPNmFhRHdGRFc0dDU5U3Baa0NBd0VBQVFKQkFObmcxMnl1dWlFUmFvaFRGZytSCi9ubk5ESGVJOXVkSURROGpuV2p1S2NSTjIvVTRIN1lBRHBZUUdwbzBHcWJjSFVncDM1cXpKendpSnExcCtrR3MKOUFFQ0lRRDBNZStBalpMMWZGaU9PcHQySnBDRjVwSDlrcXQ2NnJXb2FyZUhVcWZaR1FJaEFPcDhmL0FNZEtRbQpiTUVrVUZabnJHby9BZ1lKWmRUU1VUdzNETUZOT0VDQkFoOXExVzN5ei8xN2FPdFZUazYxWWluWWF3ZHo2TGNkCkQ1SnFIRVl1N2ZxQkFpRUFvMXR5NGZBN2ZuUktoYy9mckNKenlsejA4dkd2SUxJWTZBTk4vb2ptYklFQ0lITVoKMm1iMVBTY09CMHY0a0Z0a2NkcjlsZnZLcDZiSkZpYUhUL3VoWVh0OQotLS0tLUVORCBSU0EgUFJJVkFURSBLRVktLS0tLQkK';
	
	/**
	* Encrypts given data.
	*
	* @param string $data
	*
	* @return string
	*/
	public static function encrypt($data) {
		// add unique salt to the data:
		$data = self::getSalt().'|'.$data;
	
		$pubKey = openssl_pkey_get_public(base64_decode(self::PUBLIC_KEY));
		$chunkSize = ceil(self::KEY_BITS / 8) - 11;
		$encryptedOutput = '';

		while (strlen($data) > 0) {
			$chunk = substr($data, 0, $chunkSize);
			$data = substr($data, $chunkSize);
			$encrypted = '';
			openssl_public_encrypt($chunk, $encrypted, $pubKey);
			$encryptedOutput .= $encrypted;
		}
		openssl_free_key($pubKey);
		
		return $encryptedOutput;
	}
	
	/**
	* Decrypts given data.
	*
	* @param string $data
	*
	* @return string
	*/
	public static function decrypt($data) {
		$privKey = openssl_pkey_get_private(base64_decode(self::PRIVATE_KEY));
		
		
		$chunkSize = ceil(self::KEY_BITS / 8);
		$decryptedOutput = '';
		while (strlen($data) > 0) {
			$chunk = substr($data, 0, $chunkSize);
			$data = substr($data, $chunkSize);
			$decrypted = '';
			openssl_private_decrypt($chunk, $decrypted, $privKey);
			$decryptedOutput .= $decrypted;
		}
		openssl_free_key($privKey);
		
		// verify salt at the begining of the decrypted data:
		$pipePosition = strpos($decryptedOutput, '|');
		$actualSalt = substr($decryptedOutput, 0, $pipePosition);
		if ($actualSalt === self::getSalt()) {
			return substr($decryptedOutput, $pipePosition + 1);
		}
		
		return null;
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