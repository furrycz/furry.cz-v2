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



	/**
	* Checks if Uploaded file key is valid. Throws exception if not.
	*
	* @throws Nette\Application\ApplicationException
	* @return null
	*/
	public static function checkUploadedFileKey($key)
	{
		if(! preg_match("/^[A-Za-z0-9]*$/", $key))
		{
			throw new ApplicationException("Uploaded file key contained illegal characters: [{$key}]");
		}
	}



	/**
	* Checks if Preview Image Profile Name is valid. Throws exception if not.
	*
	* @throws Nette\Application\ApplicationException
	* @return null
	*/
	public static function checkPreviewImageProfile($profile)
	{
		if(! preg_match("/^[a-z0-9-]*$/", $profile))
		{
			throw new ApplicationException("PreviewImageProfile contained illegal characters: [{$profile}]");
		}
	}

}
