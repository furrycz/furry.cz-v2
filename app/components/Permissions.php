<?php

namespace Fcz
{

	class Permissions extends \Nette\Application\UI\Control
	{
	
		private $presenter = null;
		private $data = null;
		private $content = null;
		private $prava = array(
			"CanListContent","CanViewContent","CanEditContentAndAttributes",
			"CanEditHeader","CanEditOwnPosts","CanDeleteOwnPosts","CanReadPosts",
			"CanDeletePosts","CanWritePosts","CanEditPermissions","CanEditPolls"
		);
	

		/**
		* @param array $data array(
		*	"Permissions": array,
		*	"Description" : string,
		*	"Visibility" : array
		*	"DefaultShow": bool // Show default permissions?
		*	)
		*
		*/
		public function __construct(
			\Nette\Application\UI\Presenter $presenter,
			\Nette\Database\Table\ActiveRow $content,
			$data = null
			)
		{
			$this->presenter = $presenter;
			$this->content = $content;
			if($data==null)
			{
				$data = array(
					"Permisions" => array(  //Permision data
						"CanListContent" => array("L","Uvidí ve výpisu","","","Context",1), //$Zkratka 1 písmeno(""==Nezobrazí), $Popis, $BarvaPozadí, $Parent(""!=Nezobrazí), $Zařazení práv, $default check
						"CanViewContent" => array("V","Může vidět","","","Context",1),		//$Parent > Přiváže k jinému oprávnění a bude nabývat stejného stavu
						"CanEditContentAndAttributes" => array("E","Může upravit","","","Context - Správce",0),
						"CanEditHeader" => array("H","Může upravit popis","","","Context",0),
						"CanEditOwnPosts" => array("WE","Může upravit vlastní příspěvky","007AFF","","Téma",1),
						"CanDeleteOwnPosts" => array("WD","Může smazat vlastní příspěvky","007AFF","","Téma",1),
						"CanReadPosts" => array("WR","Může číst příspěvky","007AFF","","Téma",1),
						"CanDeletePosts" => array("PD","Může mazat příspěvky","03F","","Téma - Správce",0),
						"CanWritePosts" => array("WW","Může psát příspěvky","007AFF","","Téma",1),
						"CanEditPermissions" => array("S","Může upravit práva","","","Context - Správce",0),
						"CanEditPolls" => array("P","Může upravit ankety","","","Context - Správce",0)
						),
					"Description" => "!",
					"Visiblity" => array(
						"Public" => "Vidí všichni",
						"Private" => "Nevidí nikdo je třeba přidelit práva",
						"Hidden" => "Nezobrazí se, je třeba přidelit práva"
						),
					"DefaultShow" => true
				);
			}
			$this->data = $data;
		}
	
		public function render()
		{
			$database = $this->presenter->context->database;
			
			$access = $this->presenter->getAuthorizator()->authorize($this->content, $this->presenter->user);
		
			if ($access['CanEditPermissions'] == true)
			{			
				$owner = $database->table('Ownership')->where('ContentId', $this->content["Id"])->fetch();
				
				$accessAll = array();
				$userExists = NULL;
				$allUsers = array();
				
				$acce = $database->table('Access')->where('ContentId', $this->content["Id"]);
				foreach($acce as $ac)
				{
					$user = $database->table('Users')->where('Id', $ac["UserId"])->fetch();
					$perm = $database->table('Permissions')->where('Id', $ac["PermissionId"])->fetch();
					$accessAll[] = array(
						"PermisionId" => $ac["PermissionId"],
						// User
						"Id" => $ac["UserId"],
						"Name" => $user["Nickname"],
						// Permissions
						"CanListContent" => $perm["CanListContent"],
						"CanViewContent" => $perm["CanViewContent"],
						"CanEditContentAndAttributes" => $perm["CanEditContentAndAttributes"],
						"CanEditHeader" => $perm["CanEditHeader"],
						"CanEditOwnPosts" => $perm["CanEditOwnPosts"],
						"CanDeleteOwnPosts" => $perm["CanDeleteOwnPosts"],
						"CanReadPosts" => $perm["CanReadPosts"],
						"CanDeletePosts" => $perm["CanDeletePosts"],
						"CanWritePosts" => $perm["CanWritePosts"],
						"CanEditPermissions" => $perm["CanEditPermissions"],
						"CanEditPolls" => $perm["CanEditPolls"]
						);
					$userExists[$ac["UserId"]] = true;
				}
				
				$users = $database->table('Users')->order('Nickname');
				foreach($users as $user)
				{
					if(!isset($userExists[$user["Id"]]) and $owner["UserId"]!=$user["Id"])
					{
						$allUsers[] = array($user["Id"],$user["Nickname"]);
					}
				}
				
				$template = $this->presenter->template;
				$template->setFile(__DIR__ . '/../templates/components/permissions.latte');
				$template->accessAll = $accessAll;
				$template->allUsers = $allUsers;		

				$template->Permisions = $this->data["Permisions"];	
				$template->Description =  $this->data["Description"];
				$template->Visiblity = $this->data["Visiblity"];
				
				$this['permisionForm']->setDefaults(array("ContentId"=>$this->content["Id"]));
			
				$defaultPermision = $database->table('Permissions')->where('Id', $this->content["DefaultPermissions"])->fetch();
				
				if($this->data["DefaultShow"])
				{
					$template->DefaultShow = true;
					$template->DefaultId = $this->content["DefaultPermissions"];
					$template->DefaultPermision = array(
						"CanListContent" => $defaultPermision["CanListContent"],
						"CanViewContent" => $defaultPermision["CanViewContent"],
						"CanEditContentAndAttributes" => $defaultPermision["CanEditContentAndAttributes"],
						"CanEditHeader" => $defaultPermision["CanEditHeader"],
						"CanEditOwnPosts" => $defaultPermision["CanEditOwnPosts"],
						"CanDeleteOwnPosts" => $defaultPermision["CanDeleteOwnPosts"],
						"CanReadPosts" => $defaultPermision["CanReadPosts"],
						"CanDeletePosts" => $defaultPermision["CanDeletePosts"],
						"CanWritePosts" => $defaultPermision["CanWritePosts"],
						"CanEditPermissions" => $defaultPermision["CanEditPermissions"],
						"CanEditPolls" => $defaultPermision["CanEditPolls"]
					);
				}
				else
				{
					$template->DefaultShow = false;
				}
				
				if($defaultPermision["CanListContent"]==0)
				{
					$visible=3;
				}
				elseif($defaultPermision["CanViewContent"]==1)
				{
					$visible=1;
				}
				else
				{
					$visible=2;
				}
				$this['visibleForm']->setDefaults(array(
					"PermisionId"=>$this->content["DefaultPermissions"],
					"visible"=>$visible)
					);
				
				$template->render();
			}	
			
		}		
		
		public function createComponentPermisionForm()
		{
			$form = new \Nette\Application\UI\Form;
			$form->addSubmit('Change', 'Upravit přístupová práva');
			$form->addHidden('ContentId');
			$form->onSuccess[] = $this->processValidatedPermisionForm;
			return $form;
		}
		
		public function createComponentVisibleForm()
		{
			$form = new \Nette\Application\UI\Form;
			$form->addRadioList('visible', 'Nastavit viditelnost: ', array(
				"1" => "Veřejné (".$this->data["Visiblity"]["Public"].")",
				"2" => "Soukromé (".$this->data["Visiblity"]["Private"].")",
				"3" => "Skryto (".$this->data["Visiblity"]["Hidden"].")"
			));
			$form->addSubmit('Change', 'Změnit');
			$form->addHidden('PermisionId');
			$form->onSuccess[] = $this->processValidatedVisibleForm;
			return $form;
		}
		
		public function processValidatedVisibleForm($form)
		{
			$values = $form->getValues();
			$database = $this->presenter->context->database;
			if($values["visible"]==1)
			{
				$a=1;
				$b=1;
			}
			elseif($values["visible"]==2)
			{
				$a=1;
				$b=0;
			}
			else
			{
				$a=0;
				$b=0;
			}
			$database->table('Permissions')->where('Id', $values["PermisionId"])->update(array(
				"CanListContent" => $a,
				"CanViewContent" => $b
			));		
			$this->redirect('this');		
		}	
		
		public function processValidatedPermisionForm($form)
		{
			$values = $form->getValues();
			$database = $this->presenter->context->database;
			$i=0;
			$delete=0;
		
			for($i=0; $i<count($_POST["user"]); $i++)
			{
				$User = $_POST["user"][$i];
				if(!isset($_POST["delete"][$i]))
				{
					$_POST["delete"][$i]=0;
				}
				
				foreach($this->prava as $pra)
				{
					if(!isset($_POST[$pra][$i]))
					{
						$_POST[$pra][$i]=0;
					}
				}
				foreach($this->prava as $pra)
				{
					if($this->data["Permisions"][$pra][3]!="")
					{
						$_POST[$pra][$i] = $_POST[$this->data["Permisions"][$pra][3]][$i];
					}
				}
					
				if($_POST["permisionId"][$i]=="" and $User!="" and $_POST["delete"][$i]!=1)
				{
					$defaultPermission = $database->table('Permissions')->insert(array(
						'CanListContent' => $_POST["CanListContent"][$i],
						'CanViewContent' => $_POST["CanViewContent"][$i],
						'CanEditContentAndAttributes' => $_POST["CanEditContentAndAttributes"][$i],
						'CanEditHeader' => $_POST["CanEditHeader"][$i],
						'CanEditOwnPosts' => $_POST["CanEditOwnPosts"][$i],
						'CanDeleteOwnPosts' => $_POST["CanDeleteOwnPosts"][$i],
						'CanReadPosts' => $_POST["CanReadPosts"][$i],
						'CanDeletePosts' => $_POST["CanDeletePosts"][$i],
						'CanWritePosts' => $_POST["CanWritePosts"][$i],
						'CanEditPermissions' => $_POST["CanEditPermissions"][$i],
						'CanEditPolls' => $_POST["CanEditPolls"][$i]
					));
					$database->table('Access')->insert(array(
						'ContentId' => $values["ContentId"],
						'UserId' => $User,
						"PermissionId" => $defaultPermission["Id"]
					));
				}
				elseif($_POST["delete"][$i]!=1 and $_POST["permisionId"][$i]!="")
				{
					$database->table('Permissions')->where('Id', $_POST["permisionId"][$i])->update(array(
						'CanListContent' => $_POST["CanListContent"][$i],
						'CanViewContent' => $_POST["CanViewContent"][$i],
						'CanEditContentAndAttributes' => $_POST["CanEditContentAndAttributes"][$i],
						'CanEditHeader' => $_POST["CanEditHeader"][$i],
						'CanEditOwnPosts' => $_POST["CanEditOwnPosts"][$i],
						'CanDeleteOwnPosts' => $_POST["CanDeleteOwnPosts"][$i],
						'CanReadPosts' => $_POST["CanReadPosts"][$i],
						'CanDeletePosts' => $_POST["CanDeletePosts"][$i],
						'CanWritePosts' => $_POST["CanWritePosts"][$i],
						'CanEditPermissions' => $_POST["CanEditPermissions"][$i],
						'CanEditPolls' => $_POST["CanEditPolls"][$i]
					));
				}
				elseif($_POST["delete"][$i]==1)
				{
					$database->table('Permissions')->where('Id', $_POST["permisionId"][$i])->delete();
					$database->table('Access')->where('PermissionId', $_POST["permisionId"][$i])->delete();
					$delete++;				
				}
			}
			$this->redirect('this');		
		}
	}
}
