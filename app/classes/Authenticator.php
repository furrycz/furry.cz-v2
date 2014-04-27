<?php

use Nette\Security,
	Nette\Utils\Strings;


/*
CREATE TABLE users (
	id int(11) NOT NULL AUTO_INCREMENT,
	username varchar(50) NOT NULL,
	password char(60) NOT NULL,
	role varchar(20) NOT NULL,
	PRIMARY KEY (id)
);
*/

/**
 * Users authenticator.
 */
class Authenticator extends Nette\Object implements Security\IAuthenticator
{
	/** @var Nette\Database\Connection */
	private $database;



	public function __construct(Nette\Database\Connection $database)
	{
		$this->database = $database;
	}



	/**
	 * Performs an authentication.
	 * @param  array array(username, password)
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
		$row = $this->database->table('Users')->where('Username', $username)->fetch();

		if (!$row || $row['Password'] !== Fcz\SecurityUtilities::calculateHash($password, $row['Salt'])) {
			throw new Security\AuthenticationException('Nesprávné uživatelské jméno nebo heslo.', self::INVALID_CREDENTIAL);
		}

		$userData = array(
			'username' => $row['Username'],
			'nickname' => $row['Nickname'],
			'postsOrdering' => $row['PostsOrdering'],
			'postsPerPage' => $row['PostsPerPage'],
			'avatarFilename' => $row['AvatarFilename']
		);

		// Enter permissions
		$roles = array();

		$roles[] = ($row['IsAdmin'] == true) ? 'admin' : 'member'; // Check admin

		if ($row['IsApproved'] == true)
		{
			$roles[] = 'approved';
		}

		if ($row['DateOfBirth']->diff(new DateTime())->y >= 18) // Check age
		{
			$roles[] = 'adult';
		}

		return new Security\Identity($row['Id'], $roles, $userData);
	}





}
