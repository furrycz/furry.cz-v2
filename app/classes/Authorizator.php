<?php

use Nette\Diagnostics\Debugger;

class Authorizator
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
		$isOwner = false;
		if (!$overlord)
		{
			// Check ownership (owner is an overlord)
			$ownership = $this->database->table('Ownership')->where(array(
				'ContentId' => $content['Id'],
				'UserId' => $user->id
			))->count();
			$overlord = $ownership > 0;
		}

		if ($overlord)
		{
			return array(
				'CanListContent' => true,
				'CanViewContent' => true,
				'CanEditContentAndAttributes' => true,
				'CanEditHeader' => true,
				'CanEditOwnPosts' => true,
				'CanDeleteOwnPosts' => true,
				'CanReadPosts' => true,
				'CanDeletePosts' => true,
				'CanWritePosts' => true,
				'CanEditPermissions' => true,
				'CanEditPolls' => true,
				'Owner' => true
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
		
		$perms["Owner"] = false;

		return $perms;
	}
}
