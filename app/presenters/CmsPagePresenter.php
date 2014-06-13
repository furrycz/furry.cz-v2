<?php

use Nette\Application;
use Nette\Application\UI;
use Nette\Database;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;

/**
 * CMS pages presenter
 */
class CmsPagePresenter extends BasePresenter
{

	/**
	* Default page; provides tools to approved users.
	*/
	public function renderDefault()
	{
		if (! $this->user->isInRole('approved'))
		{
			$this->redirect("Homepage:default");
		}

		$database = $this->context->database;
		$myPages = $database->table("Content")->where(array(
			"Type" => "CMS",
			"Ownership:UserId" => $this->user->id
		));

		$this->template->setParameters(array(
			"myPages" => $myPages
		));

	}



	/**
	 * Presents a single CMS page
	 */
	public function renderShowPage($idOrAlias)
	{
		// Get id/alias of the page
		if (!isset($idOrAlias)) {
			$this->redirect('Homepage:default');
		}

		list($cmsPage, $content, $access) = $this->checkCmsPageAccess($idOrAlias, $this->user);

		// Display the pages
		$this->template->setParameters(array(
			"cmsPage" => $cmsPage,
			"content" => $content,
			"access" => $access
		));

	}



	public function renderNewPage()
	{
		if (! $this->user->isInRole("approved"))
		{
			throw new ForbiddenRequestException("Pouze schválení uživatelé mohou vytvářet CMS stránky");
		}
	}



	public function renderEditPage($idOrAlias)
	{
		list($cmsPage, $content, $access) = $this->checkCmsPageAccess($idOrAlias, $this->user);

		if (! $access["CanEditContentAndAttributes"])
		{
			throw new ForbiddenRequestException("Nejste oprávněni upravovat tuto CMS stránku");
		}

		// Display the pages
		$this->template->setParameters(array(
			"cmsPage" => $cmsPage,
			"content" => $content,
			"access" => $access
		));
	}



	public function renderDeletePage($idOrAlias)
	{
		list($cmsPage, $content, $access) = $this->checkCmsPageAccess($idOrAlias, $this->user);

		if (! $access["CanDeleteContent"])
		{
			throw new ForbiddenRequestException("Nejste oprávněni smazat tuto CMS stránku");
		}

		// Display the pages
		$this->template->setParameters(array(
			"cmsPage" => $cmsPage,
			"content" => $content,
			"access" => $access
		));
	}



	public function createComponentNewCmsPageForm()
	{
		// Permissions
		if (! $this->user->isInRole('approved'))
		{
			throw new ForbiddenRequestException("Nejste oprávněni vytvářet CMS stránky");
		}

		$form = new UI\Form();

		// CMS page
		$form->addText('Name', 'Název * :')
			->setRequired('Je nutné zadat název')
			->getControlPrototype()->class = 'Wide';

		$form->addTextArea('Description', 'Popisek:', 2, 10); // Small rows/cols values to allow css scaling

		$form->addTextArea("Text", "Text: ")
			->setAttribute('class', 'tinimce CmsPageText');

		// Flags
		$form->addCheckbox('IsDiscussionAllowed', 'Povolit diskuzi')->setValue(true);
		$form->addCheckbox('IsRatingAllowed', 'Povolit hodnoceni')->setValue(true);

		// Restriction
		$form->addSelect("Restriction", "Přístupnost", array(
			1 => "Všichni",
			2 => "Pouze schválení",
			3 => "Pouze schválení 18+",
		));
		$form->addCheckbox('CanViewContent', 'Můžou stránku navštívit')->setValue(true);

		// Default permissions (discussion)
		$form->addCheckbox('CanWritePosts', 'Může psát příspěvky')->setValue(true);
		$form->addCheckbox('CanEditOwnPosts', 'Může upravovat vlastní příspěvky')->setValue(true);
		$form->addCheckbox('CanDeleteOwnPosts', 'Může mazat vlastní příspěvky')->setValue(true);
		$form->addCheckbox('CanDeletePosts', 'Může moderovat (mazat a upravovat jakékoli příspěvky)')->setValue(false);

		// Default permissions (administration)
		$form->addCheckbox('CanEditContentAndAttributes', 'Může měnit název a atributy')->setValue(false);
		$form->addCheckbox('CanEditHeader', 'Může měnit hlavičku')->setValue(false);
		$form->addCheckbox('CanEditPermissions', 'Může spravovat oprávnění')->setValue(false);
		$form->addCheckbox('CanEditPolls', 'Může spravovat ankety')->setValue(false);

		// Submit
		$form->onValidate[] = $this->validateNewCmsPageForm;
		$form->onSuccess[] = $this->processValidatedNewCmsPageForm;
		$form->addSubmit("Save", "Uložit");

		return $form;
	}



	public function validateNewCmsPageForm(UI\Form $form)
	{
		$values = $form->getValues();
		if (trim($values["Text"]) == "")
		{
			$form->addError("Nelze uložit prázdnou CMS stránku");
		}

		// TODO: Parse and sanitize HTML
	}



	public function processValidatedNewCmsPageForm(UI\Form $form)
	{
		// Permissions
		if (! $this->user->isInRole('approved'))
		{
			throw new ForbiddenRequestException("Nejste oprávněni vytvářet CMS stránky");
		}

		$values = $form->getValues();
		$database = $this->context->database;
		$database->beginTransaction();

		$resultCms = null;
		/*try
		{*/
			// Create default permission
			$defaultPermission = $database->table('Permissions')->insert(array(
				'CanListContent' => true, // This flag has no meaning for CMS: there are no listings.
				'CanViewContent' => $values['CanViewContent'],
				'CanEditContentAndAttributes' => $values['CanEditContentAndAttributes'],
				'CanEditHeader' => $values['CanEditHeader'],
				'CanEditOwnPosts' => $values['CanEditOwnPosts'],
				'CanDeleteOwnPosts' => $values['CanDeleteOwnPosts'],
				'CanDeletePosts' => $values['CanDeletePosts'],
				'CanWritePosts' => $values['CanWritePosts'],
				'CanEditPermissions' => $values['CanEditPermissions'],
				'CanEditPolls' => $values['CanEditPolls'],
				'CanReadPosts' => $values['CanViewContent']
			));

			// Create content
			$content = $database->table('Content')->insert(array(
				'Type' => 'CMS',
				'TimeCreated' => new DateTime,
				'LastModifiedTime' => new DateTime,
				'IsForRegisteredOnly' => $values["Restriction"] >= 2,
				'IsForAdultsOnly' => $values["Restriction"] == 3,
				'DefaultPermissions' => $defaultPermission['Id']
			));

			// Create permission for owner
			$database->table('Ownership')->insert(array(
				'ContentId' => $content['Id'],
				'UserId' => $this->user->id
			));

			$resultCms = $database->table("CmsPages")->insert(array(
				"Name" => $values["Name"],
				"Description" => $values["Description"],
				"Text" => $values["Text"],
				"ContentId" => $content["Id"]
		));
			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

		$this->flashMessage('CMS stránka byla vytvořena', 'ok');
		$this->redirect('CmsPage:showPage', $resultCms["Id"]);
	}



	/**
	* Fetches item from DB
	* @return \Nette\Database\Table\ActiveRow CMS page entry.
	* @throws BadRequestException If the CMS-page isn't found.
	*/
	private function fetchCmsPage($idOrAlias)
	{
		$database = $this->context->database;

		if (ctype_digit($idOrAlias[0])) // Aliases must not start with number
		{
			$where = array('Id' => (int) $idOrAlias);
		}
		else
		{
			$where = array('Alias' => $idOrAlias);
		}
		$cmsPage = $database->table("CmsPages")->where($where)->fetch();

		if ($cmsPage === false or $cmsPage['Content']['Deleted'] === true)
		{
			throw new BadRequestException("CMS stránka nenalezena");
		}
		return $cmsPage;
	}



	/**
	* Fetches item from DB and checkes permissions.
	* @return array $cmsPage, $content, $access
	* @throws BadRequestException If the CMS-page isn't found.
	*/
	private function checkCmsPageAccess($idOrAlias, $user)
	{
		$database = $this->context->database;

		$cmsPage = $this->fetchCmsPage($idOrAlias);

		$content = $cmsPage->ref("ContentId");
		if ($content === false or $content === null)
		{
			throw new ApplicationException("Database/CmsPage (idOrAlias: {$idOrAlias}) has no asociated Database/Content");
		}

		$access = $this->getAuthorizator()->authorize($content, $user);
		return array($cmsPage, $content, $access);
	}



	public function createComponentCmsPageEditForm()
	{
		// Permissions
		list($cmsPage, $content, $access) = $this->checkCmsPageAccess($this->getParameter("idOrAlias"), $this->user);

		$form = new UI\Form();

		// CMS page
		$form->addText("Name", "Název * :")
			->setRequired("Je nutné zadat název")
			->setValue($cmsPage["Name"])
			->getControlPrototype()->class = "Wide";

		$form->addTextArea("Description", "Popisek:", 2, 10) // Small rows/cols values to allow css scaling
			->setValue($cmsPage["Description"]);

		$form->addTextArea("Text", "Text: ")
			->setAttribute("class", "tinimce CmsPageText")
			->setValue($cmsPage["Text"]);

		// Flags
		$form->addCheckbox("IsForRegisteredOnly", "Jen pro registrované")->setValue($content["IsForRegisteredOnly"]);
		$form->addCheckbox("IsForAdultsOnly", "18+")->setValue($content["IsForAdultsOnly"]);
		$form->addCheckbox('IsDiscussionAllowed', 'Povolit diskuzi')->setValue($content["IsDiscussionAllowed"]);
		$form->addCheckbox('IsRatingAllowed', 'Povolit hodnoceni')->setValue($content["IsRatingAllowed"]);

		// Submit
		$form->onValidate[] = $this->validateCmsPageEditForm;
		$form->onSuccess[] = $this->processValidatedCmsPageEditForm;
		$form->addSubmit("Save", "Uložit");

		return $form;
	}



	public function validateCmsPageEditForm(UI\Form $form)
	{
		$values = $form->getValues();
		if (trim($values["Text"]) == "")
		{
			$form->addError("Nelze uložit prázdnou CMS stránku");
		}

		// TODO: Parse and sanitize HTML
	}



	public function processValidatedCmsPageEditForm(UI\Form $form)
	{
		// Permissions
		list($cmsPage, $content, $access) = $this->checkCmsPageAccess($this->getParameter("idOrAlias"), $this->user);

		$values = $form->getValues();
		$database = $this->context->database;
		$database->beginTransaction();

		/*try
		{*/
			// Update content
			$content->update(array(
				"IsForRegisteredOnly" => $values["IsForRegisteredOnly"],
				"IsForAdultsOnly" => $values["IsForAdultsOnly"],
				"IsDiscussionAllowed" => $values["IsDiscussionAllowed"],
				"IsRatingAllowed" => $values["IsRatingAllowed"],
				"LastModifiedTime" => new DateTime(),
				"LastModifiedByUser" => $this->user->id,
			));

			// Update CMS pages
			$cmsPage->update(array(
				"Name" => $values["Name"],
				"Description" => $values["Description"],
				"Text" => $values["Text"],
			));

			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

		$this->flashMessage("CMS stránka byla upravena", "ok");
		$this->redirect("CmsPage:showPage", $cmsPage["Id"]);
	}



	public function createComponentDeleteCmsPageForm()
	{
		// Get data
		list($cmsPage, $content, $access) = $this->checkCmsPageAccess($this->getParameter("idOrAlias"), $this->user);

		// Access
		if (! $access["CanDeleteContent"])
		{
			throw new ForbiddenRequestException("Nejste oprávněni smazat tuto CMS stránku");
		}

		// Create form
		$form = new UI\Form;

		// Submit
		$form->onSuccess[]  = $this->processValidatedDeleteCmsPageForm;
		$form->addSubmit('SubmitDelete', 'Smazat');

		return $form;
	}



	public function processValidatedDeleteCmsPageForm(UI\Form $form)
	{
		// Get data
		list($cmsPage, $content, $access) = $this->checkCmsPageAccess($this->getParameter("idOrAlias"), $this->user);

		// Access
		if (! $access["CanDeleteContent"])
		{
			throw new ForbiddenRequestException("Nejste oprávněni smazat tuto CMS stránku");
		}

		$database = $this->context->database;
		$database->beginTransaction();
		//try
		//{
			$cmsPage->delete();

			$content = $cmsPage->ref("Content");

			$content->related("Ownership")->delete();
			$database->table("Permissions")->where(":Access.ContentId", $content["Id"]);
			$content->related("Access")->delete();
			$content->related("LastVisits")->delete();
			$content->delete();

			$database->commit();

			$this->flashMessage("CMS stránka byla smazána", "ok");
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
			$this->flashMessage("Obrázek se nepodařilo smazat", "error");
		}*/

		$this->redirect('CmsPage:default');
	}



	public function createComponentPermissions()
	{
		$cmsPage = $this->fetchCmsPage($this->getParameter("idOrAlias"));

		$data = array(
			"Permisions" => array(  //Permision data
				//$Zkratka 1 písmeno(""==Nezobrazí), $Popis, $BarvaPozadí, $Parent(""!=Nezobrazí), $Zařazení práv, $default check
				"CanListContent"              => array("","","","CanViewContent","",1),
				"CanReadPosts"                => array("R","Může stránku číst","","","",1),
				"CanViewContent"              => array("","","","CanReadPosts","Context",1),
				"CanEditContentAndAttributes" => array("E","Může stránku upravit","D80093","","Context - Správce",0),
				"CanEditHeader"               => array("","","","","",0),
				"CanEditPermissions"          => array("S","Může upravit práva","D80093","","Context - Správce - NEBEZEPEČNÉ",0),
				"CanDeleteOwnPosts"           => array("","","","CanEditOwnPosts","",1),
				"CanWritePosts"               => array("P","Může psát příspěvky","61ADFF","","Context",1),
				"CanDeletePosts"              => array("D","Může mazat a editovat všechny příspěvky","007AFF","","Moderátor",0),
				"CanEditPolls"                => array("EP","Muže upravit ankety","007AFF","","Moderátor",0),
				"CanEditOwnPosts"             => array("U","Může editovat a mazat vlastní příspěvky.","F00","","",1)
				),
			"Description" => "!", // "!" means NULL here
			"Visiblity" => NULL,
			"DefaultShow" => true
		);
		return new Fcz\Permissions($this, $cmsPage->ref("ContentId"), $data);
	}



	public function renderManagePermissions($idOrAlias)
	{
		// Check access
		list($cmsPage, $content, $access) = $this->checkCmsPageAccess($idOrAlias, $this->user);
		if (! $access["CanEditPermissions"])
		{
			throw new BadRequestException("Nemáte oprávnění upravovat přístupová práva");
		}

		// Setup template
		$this->template->setParameters(array(
			"name" => $cmsPage["Name"],
			"cmsPageId" => $cmsPage["Id"],
		));
	}

}
