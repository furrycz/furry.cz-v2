<?php

use Nette\Diagnostics\Debugger;

/**
* Authorizes users to perform tasks.
*
* Permissions are represented by following array of booleans:
	'CanListContent'              => Specified in Database/Permissions
	'CanViewContent'              => Specified in Database/Permissions
	'CanDeleteContent'            => Only owner (given in Database/Ownership) and admins can delete content.
	'CanEditContentAndAttributes' => Specified in Database/Permissions
	'CanEditHeader'               => Specified in Database/Permissions
	'CanEditOwnPosts'             => Specified in Database/Permissions
	'CanDeleteOwnPosts'           => Specified in Database/Permissions
	'CanReadPosts'                => Specified in Database/Permissions
	'CanDeletePosts'              => Specified in Database/Permissions
	'CanWritePosts'               => Specified in Database/Permissions
	'CanEditPermissions'          => Specified in Database/Permissions
	'CanEditPolls'                => Specified in Database/Permissions
	'IsOwner'                     => Is user owner? (given in Database/Ownership)
*/
class Authorizator extends \Nette\Object
{
	private $database;



	public function __construct($database)
	{
		$this->database = $database;
	}



	private function cascadeDeny(&$access, $field)
	{
		if ($field == 'CanViewContent')
		{
			$access['CanViewContent'] = false;
			$access['CanEditContentAndAttributes'] = false;
			$access['CanEditHeader'] = false;
			$access['CanDeletePosts'] = false;
			$access['CanEditPermissions'] = false;
			$access['CanEditPolls'] = false;
		}
		if ($field == 'CanReadPosts' || $field == 'CanViewContent')
		{
			$access['CanReadPosts'] = false;
			$access['CanWritePosts'] = false;
			$access['CanEditOwnPosts'] = false;
			$access['CanDeleteOwnPosts'] = false;
		}
	}



	/** Checks all permission of given user to given content.
	* @return array A table with permissions (matching SQL table Permissions)
	*/
	public function authorize($content, $user)
	{
		// CHECK FULL ACCESS

		if (! $user->isLoggedIn())
		{
			if ($content["IsForRegisteredOnly"] || $content["IsForAdultsOnly"])
			{
				return array(
					"CanListContent" => false,
					"CanViewContent" => false,
					"CanDeleteContent" => false,
					"CanEditContentAndAttributes" => false,
					"CanEditHeader" => false,
					"CanEditOwnPosts" => false,
					"CanDeleteOwnPosts" => false,
					"CanReadPosts" => false,
					"CanDeletePosts" => false,
					"CanWritePosts" => false,
					"CanEditPermissions" => false,
					"CanEditPolls" => false,
					"IsOwner" => false
				);
			}
			else
			{
				$perms = $content->ref('DefaultPermissions')->toArray();
				$perms["IsOwner"] = false;
				return $perms;
			}
		}

		$overlord = $user->isInRole('admin');
		if (!$overlord)
		{
			// Check isOwner (owner is an overlord)
			$isOwner = $this->database->table('Ownership')->where(array(
				'ContentId' => $content['Id'],
				'UserId' => $user->id
			))->count() > 0;
			$overlord = $isOwner;
		}

		if ($overlord)
		{
			return array(
				'CanListContent' => true,
				'CanViewContent' => true,
				'CanDeleteContent' => true,
				'CanEditContentAndAttributes' => true,
				'CanEditHeader' => true,
				'CanEditOwnPosts' => true,
				'CanDeleteOwnPosts' => true,
				'CanReadPosts' => true,
				'CanDeletePosts' => true,
				'CanWritePosts' => true,
				'CanEditPermissions' => true,
				'CanEditPolls' => true,
				'IsOwner' => $isOwner
			);
		}

		// CHECK USER-SPECIFIC RIGHTS

		$access = $this->database->table('Access')->where(array(
			'ContentId' => $content['Id'],
			'UserId' => $user->id
		))->fetch();

		if ($access !== false)
		{
			$perms = $access->ref('PermissionId')->toArray();

		}
		else // No specific permissions => defaults are in effect
		{
			$perms = $content->ref('DefaultPermissions')->toArray();
		}
		unset($perms['Id']);

		// CHECK CONTENT FLAGS
	
		if (!$content['IsDiscussionAllowed'])
		{
			$this->cascadeDeny($perms, 'CanReadPosts');
		}

		if ($content['IsForRegisteredOnly'] && !$user->isInRole('approved'))
		{
			$this->cascadeDeny($perms, 'CanViewContent');
		}

		if ($content['IsForAdultsOnly'] && !$user->isInRole('adult'))
		{
			$this->cascadeDeny($perms, 'CanViewContent');
		}	
		
		$perms["IsOwner"] = false;

		return $perms;
	}
}
