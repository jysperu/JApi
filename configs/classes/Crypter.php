<?php

class Crypter
{
	private static $instance;
	public static function instance ()
	{
		isset(self::$instance) or self::$instance = new self();
		
		return self::$instance;
	}
	
	/**
	 * Encrypt a message
	 * 
	 * @param string $message - message to encrypt
	 * @param string $key - encryption key
	 * @return string
	 */
	function encrypt(string $message, string $key): string
	{
		$iv = random_bytes (16);
		$key = $this -> getKey($key);

		$encrypted = $this -> sign(openssl_encrypt($message, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv), $key);
		return bin2hex($iv) . bin2hex($encrypted);
	}

	/**
	 * Decrypt a message
	 * 
	 * @param string $encrypted - message encrypted with safeEncrypt()
	 * @param string $key - encryption key
	 * @return string
	 */
	function decrypt(string $encrypted, string $key): string
	{
		$iv = hex2bin(substr($encrypted, 0, 32));
		$data = hex2bin(substr($encrypted, 32));

		$key = $this -> getKey($key);

		if ( ! $this -> verify($data, $key))
		{
			return '';
		}

		return openssl_decrypt(mb_substr($data, 64, null, '8bit'), 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv);
	}

	
	private function sign ($message, $key) {
		return hash_hmac('sha256', $message, $key) . $message;
	}

	private function verify($bundle, $key) {
		return hash_equals(
		  hash_hmac('sha256', mb_substr($bundle, 64, null, '8bit'), $key),
		  mb_substr($bundle, 0, 64, '8bit')
		);
	}

	private function getKey($key, $keysize = 16) {
		return hash_pbkdf2('sha256', $key, 'some_token', 100000, $keysize, true);
	}
}
