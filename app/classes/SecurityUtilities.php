<?php

namespace Fcz;

class SecurityUtilities extends \Nette\Object
{

	/**
	 * Generates salt for CRYPT_BLOWFISH
	 * @return string
	 */
	public static function generateSalt()
	{
		return '$2y$07$' . \Nette\Utils\Strings::random(22);
	}



	/**
	 * @return string
	 */
	public static function calculateHash($password, $salt)
	{
		// Validate

		if (!is_string($password))
			throw new \Nette\InvalidArgumentException('$password argument: string expected, found' . gettype($password));

		if (!is_string($salt))
			throw new \Nette\InvalidArgumentException('$salt argument: string expected, found' . gettype($salt));

		// Process

		return crypt($password, $salt);
	}



	public static function processCmsHtml($inHtml)
	{
		return $inHtml; // TODO: strip all JavaScript!
	}

}
