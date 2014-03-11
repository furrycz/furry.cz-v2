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

		$where = NULL;
		if (ctype_digit($idOrAlias[0])) { // Aliases must not start with number
			$where = array('Id' => (int) $idOrAlias);
		} else {
			$where = array('Alias' => $idOrAlias);
		}

		// Load the page
		$cmsPage = $this->context->database->table('CmsPages')->where($where)->fetch();

		if ($cmsPage === false) {
			throw new Nette\Application\BadRequestException();
		}

		// Check access
		if ($cmsPage['Content']['Deleted'] === true) {
			throw new Nette\Application\BadRequestException();
		}

		// Display the pages
		$this->template->cmsPage = $cmsPage;

	}



	public function renderNewPage()
	{

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
		$form->addCheckbox('IsForRegisteredOnly', 'Jen pro registrované')->setValue(false);
		$form->addCheckbox('IsForAdultsOnly', '18+')->setValue(false);
		$form->addCheckbox('IsFlame', 'Flamewar')->setValue(false);

		// Default permissions
		$form->addCheckbox('CanListContent', 'Vidí stránku')->setValue(true);
		$form->addCheckbox('CanViewContent', 'Může stránku navštívit')->setValue(true);
		$form->addCheckbox('CanEditContentAndAttributes', 'Může měnit název a atributy')->setValue(false);
		$form->addCheckbox('CanEditHeader', 'Může měnit hlavičku')->setValue(false);
		$form->addCheckbox('CanEditOwnPosts', 'Může upravovat vlastní příspěvky')->setValue(true);
		$form->addCheckbox('CanDeleteOwnPosts', 'Může mazat vlastní příspěvky')->setValue(true);
		$form->addCheckbox('CanDeletePosts', 'Může mazat a upravovat jakékoli příspěvky')->setValue(false);
		$form->addCheckbox('CanWritePosts', 'Může psát příspěvky')->setValue(true);
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
				'CanListContent' => $values['CanListContent'],
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
				'IsForRegisteredOnly' => $values['IsForRegisteredOnly'],
				'IsForAdultsOnly' => $values['IsForAdultsOnly'],
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

}
