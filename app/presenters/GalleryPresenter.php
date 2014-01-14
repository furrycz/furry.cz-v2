<?php

use Nette\Application\UI;
use Nette\Utils\Html;
use Nette\Diagnostics\Debugger;

/**
 * Image gallery presenter
 */
class GalleryPresenter extends DiscussionPresenter
{
	/// @var \Nette\Database\Table\ActiveRow
	/// Temporary variable; data are loaded in render*() but form generated in createComponent*()
	private $expositionToModify = null;

	private $newImageTargetExposition = null;

	/**
	 * Action: Shows a gallery main page.
	 * Gallery main page contains a list of authors & recent additions and changes.
	 */
	public function renderDefault()
	{
		$database = $this->context->database;

		// TODO: Filter!
		$authors = $database->table("Users");

		// TODO: Filter!
		$recentPosts = null;

		$this->template->setParameters(array(
			'authors' => $authors,
			'recentPosts' => $recentPosts
		));
	}



	public function renderUser($userId, $pageNumber)
	{
		$database = $this->context->database;
		if ($userId == null)
		{
			if ($this->user->isInRole('approved'))
			{
				$userId = $this->user->id;
			}
			else
			{
				throw new Nette\Application\ForbiddenRequestException("Nejste schvalenym clenem");
			}
		}

		$user = $database->table("Users")->where(array("Id"=> $userId))->fetch();

		$expositions = $database->table("ImageExpositions")->where("Owner", $userId);

		$recentPosts = null;

		$this->template->setParameters(array(
			'user' => $user,
			'recentPosts' => $recentPosts,
			'expositions' => $expositions
		));
	}



	public function renderEditExposition($id)
	{
		$database = $this->context->database;

		$this->expositionToModify = $database->table("ImageExpositions")->where("Id", $id)->fetch();
	}



	public function renderExposition($id)
	{
		$database = $this->context->database;

		$exposition = $database->table("ImageExpositions")->where("Id", $id)->fetch();

		$this->template->setParameters(array(
			'user' => $exposition->ref("Owner"),
			'exposition' => $exposition
		));
	}



	public function renderDeleteExposition($id)
	{
		if ($id == null)
		{
			throw new BadRequestException("Zadana expozice neexistuje");
		}

		$database = $this->context->database;

		$expo = $database->table("ImageExpositions")->select("Name")->where("Id", $id);
		if (count($expo) == 0)
		{
			throw new BadRequestException("Zadana expozice neexistuje");
		}

		$imageCount = $expo->related("Images", "ExpositionId")->count();

		$form = $this["deleteExpositionForm"];
		$form["ExpositionId"] = $id;

		$this->template->setParameters(array(
			"imageCount" => $imageCount,
			"exposition" => $expo
		));
	}



	public function renderAddImage($expositionId)
	{
		// Check access
		if (!($this->user->isInRole('member') || $this->user->isInRole('admin')))
		{
			throw new Nette\Application\ForbiddenRequestException(
				'Pouze registrovaní uživatelé mohou vkladat obrazky do galerie');
		}

		$database = $this->context->database;

		$this->template->setParameters(array(

		));
	}



	public function createComponentNewExpositionForm()
	{
		$form = new UI\Form;

		$form->addUpload("Thumbnail", "Ikona");

		$form->addText("Name", "Nazev");

		$form->addText("Description", "Popis");

		$form->addTextArea("PresentationText", "Prezentace");

		$form->addSubmit("SubmitNewExposition", "Vytvorit");
		$form->onValidate[] = $this->validateNewExpositionForm;
		$form->onSuccess[] = $this->processValidatedNewExpositionForm;

		return $form;
	}



	public function validateNewExpositionForm($form)
	{
		$thumbUpload = $form->getComponent("Thumbnail", true);

		if ($thumbUpload->isFilled())
		{
			$handler = new Fcz\FileUploadHandler($this);
			$handler->validateFormUpload($form, "Ikona", $form->values["Thumbnail"], "expositionThumbnail");
		}
	}



	public function processValidatedNewExpositionForm($form)
	{
		$values = $form->getValues();
		$database = $this->context->database;
		$database->beginTransaction();

		/*try
		{*/

			// CMS
			$cmsId = null;
			if ($values["PresentationText"] != "")
			{
				$cms = $database->table("CmsPages")->insert(array(
					"Name" => "Exposition (user {$this->user->login})",
					"Text" => $values["PresentationText"]
				));
				$cmsId = $cms["Id"];
			}

			// Thumbnail
			$thumbId = null;
			$handler = new Fcz\FileUploadHandler($this);
			list ($thumbId, $thumbKey) = $handler->handleUpload($form->getComponent("Thumbnail", true)->getValue(), "ExpositionThumbnail", null);

			// Exposition
			$exposition = $database->table("ImageExpositions")->insert(array(
					"Name" => $values["Name"],
					"Description" => $values["Description"],
					"Presentation" => $cmsId,
					"Thumbnail" => $thumbId,
					"Owner" => $this->user->id
				));

			// Thumb: source id update
			$database->table("UploadedFiles")->where("Id", $thumbId)->update("SourceId", $exposition["Id"]);

			// Finish
			$this->flashMessage("Expozice vytvorena", 'ok');
			$database->commit();
		/*}
		catch ($exception)
		{
			Nette\Diagnostics\Debugger::log($exception);
			$database->rollback();
			$this->flashMessage("Nepodarilo se vytvorit expozici", "error");
		}*/

		$this->redirect("Gallery:user");

	}



	public function processValidatedEditExpositionForm($form)
	{
		$values = $form->getHttpData();
		$database = $this->context->database;

		// CMS
		$cmsId = null;
		if ($values["CmsId"] == "")
		{
			$cms = $database->table("CmsPages")->insert(array(
				"Name" => "{$this->user->login} Gallery",
				"Text" => $values["PresentationText"]
			));
			$cmsId = $cms["Id"];
			$this->flashMessage("Byla vytvorena prezentace");
		}
		elseif ($values["PresentationText"] == "")
		{
			$database->table("CmsPages")->where("Id", $values["CmsId"])->remove();
			$this->flashMessage("Prezentace byla smazana");
		}
		else
		{
			$database->table("CmsPages")->where("Id", $values["CmsId"])->update(array(
				"Text" => $values["PresentationText"]
			));
			$cmsId = $values["CmsId"];
		}

		// Exposition
		$database->table("ImageExpositions")->where("Id", $values["ExpositionId"])->update(array(
				"Name" => $values["Name"],
				"Description" => $values["Description"],
				"Presentation" => $cmsId
			));

		$this->flashMessage("Expozice byla upravena");
		$this->redirect("Gallery:exposition", $values['ExpositionId']);

	}



	public function createComponentEditExpositionForm()
	{
		$form = new UI\Form;

		$form->addText("Name", "Nazev");
		$form->addText("Description", "Popis");
		$form->addTextArea("PresentationText", "Prezentace");

		$form->addSubmit("SubmitUpdatedExposition", "Ulozit");
		$form->onSuccess[] = $this->processValidatedEditExpositionForm;

		// Set defaults
		$expo = $this->expositionToModify;
		if ($expo != null)
		{
			$form->setDefaults(array(
				"Name" => $expo["Name"],
				"Description" => $expo["Description"],
				"PresentationText" => $expo->ref("CmsPages", "Presentation")["Text"]
			));

			$form->addHidden("ExpositionId", $expo["Id"]);
			$form->addHidden("CmsId", $expo["Presentation"]);
		}

		return $form;
	}



	public function composeExpositionSelectList()
	{
		$expoSelectList = array(
			0 => "~ Centralni ~",
		);
		$expoDbList = $database
			->table("ImageExpositions")
			->where("owner", $this->user->id)
			->select("Id, Name");

		foreach ($expoDbList as $id => $values)
		{
			$expoSelectList[$id] = $values["Name"];
		}
	}



	public function createComponentAddImageForm()
	{
		$database = $this->context->database;
		$form = new UI\Form;

		$form->addUpload("ArtworkUpload", "Soubor obrazku")
			->addRule(UI\Form::IMAGE);

		// Artwork title
		$form->addText('Title', 'Název * :')
			->setRequired('Je nutné zadat název dila')
			->getControlPrototype()->class = 'Wide';

		// Description text
		$form->AddTextArea("Description", "Popis", 2, 5); // Small dimensions to allow CSS scalinh

		// Exposition

		$expoSelect = $form->AddSelect("ExpositionId", "Expozice:", $this->composeExpositionSelectList());

		if ($this->newImageTargetExposition != null)
		{
			$expoSelect->setValue($this->newImageTargetExposition);
		}

		// Flags
		$form->addCheckbox('IsForRegisteredOnly', 'Jen pro registrované')->setValue(false);
		$form->addCheckbox('IsForAdultsOnly', '18+')->setValue(false);
		$form->addCheckbox('IsDiscussionAllowed', 'Povolit diskuzi')->setValue(true);
		$form->addCheckbox('IsRatingAllowed', 'Povolit hodnoceni')->setValue(true);

		// Permissions
		$form->addCheckbox('CanListContent', 'Vidí téma')->setValue(true);
		$form->addCheckbox('CanViewContent', 'Může téma navštívit')->setValue(true);
		$form->addCheckbox('CanEditContentAndAttributes', 'Může měnit název a atributy')->setValue(false);
		$form->addCheckbox('CanEditHeader', 'Může měnit hlavičku')->setValue(false);
		$form->addCheckbox('CanEditOwnPosts', 'Může upravovat vlastní příspěvky')->setValue(true);
		$form->addCheckbox('CanDeleteOwnPosts', 'Může mazat vlastní příspěvky')->setValue(true);
		$form->addCheckbox('CanDeletePosts', 'Může mazat jakékoli příspěvky')->setValue(false);
		$form->addCheckbox('CanWritePosts', 'Může psát příspěvky')->setValue(true);
		$form->addCheckbox('CanEditPermissions', 'Může spravovat oprávnění')->setValue(false);
		$form->addCheckbox('CanEditPolls', 'Může spravovat ankety')->setValue(false);

		// Submit
		$form->onValidate[] = $this->validateAddImageForm;
		$form->onSuccess[] = $this->processValidatedAddImageForm;
		$form->addSubmit('SubmitNewArtwork', 'Vlozit obrazek');

		return $form;
	}



	public function validateAddImageForm($form)
	{
		$values = $form->getValues();

		// Check if exposition exists
		if ($values["ExpositionId"] != 0)
		{
			$expoResult = $this->database->table("ImageExpositions")->select("Id", $values["ExpositionId"])->count();
			if ($expoResult == 0)
			{
				$form->addError("Zadana expozice neexistuje");
			}
		}
	}



	public function processValidatedAddImageForm($form)
	{
		$values = $form->getValues();
		$database = $this->context->database;
		$database->beginTransaction();

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
				'CanEditPolls' => $values['CanEditPolls']
			));

			// Create content
			$content = $database->table('Content')->insert(array(
				'Type' => 'Image',
				'TimeCreated' => new DateTime,
				'IsForRegisteredOnly' => $values['IsForRegisteredOnly'],
				'IsForAdultsOnly' => $values['IsForAdultsOnly'],
				'DefaultPermissions' => $defaultPermission['Id']
			));

			// Create permission for owner
			$database->table('Ownership')->insert(array(
				'ContentId' => $content['Id'],
				'UserId' => $this->user->id
			));

			$uploadHandler = new Fcz\FileUploadHandler($this);
			list($uploadId, $_) = $uploadHandler->handleUpload($values["ArtworkUpload"], 'GalleryImage', $content['Id']);

			// Create image entry
			$database->table('Images')->insert(array(
				'ContentId' => $content['Id'],
				'Name' => $values['Title'],
				"Description" => $values["Description"],
				"UploadedFileId" => $uploadId,
				"Exposition" => $values["ExpositionId"]
			));

			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

		$this->flashMessage('Obrazek byl nahran', 'ok');
		if ($values["ExpositionId"] != 0)
		{
			$this->redirect("Gallery:exposition", $values["ExpositionId"]);
		}
		else
		{
			$this->redirect('Gallery:user');
		}
	}



	public function createComponentDeleteExpositionForm()
	{
		$form = new UI\Form();

		$form->addRadioList("ImageOperation", "Jak nalozit s obrazky:", array(
			"Delete" => "Vymazat",
			"Move" => "Presunout do jine expozice:"
		));

		$expoList = $this->composeExpositionSelectList();
		unset($expoList[$this->getParameter("id")]); // Exclude the expo we're about to remove.
		echo $this->getParameter("id"); // debug
		$form->addSelectList("TargetExpo", "Kam presunout: ", $expoList);

		$form->addCheckbox("KeepThumbnail", "Ponechat si ikonu");

		$form->addCheckbox("KeepPresentation", "Ponechat si prezentaci");

		$form->addSubmit("SubmitDeleteExpo", "Smazat");
		$form->onSuccess[] = $this->processValidatedDeleteExpositionForm;
	}



	public function processValidatedDeleteExpositionForm()
	{
		$values = $form->getValues();
		$database = $this->context->database;
		$database->beginTransaction();
		$expoId = $this->getParameter("id");

		/*try
		{*/
			$expo = $database->table("ImageExpositions")->where("Id", $expoId);

			// Handle thumbnail
			if ($values["KeepThumbnail"] == true)
			{
				$database->table("UploadedFiles")->where("ExpositionId", $expoId)->update(array(
					"SourceType" => null,
					"SourceId" => null
				));
			}
			else
			{
				// Delete file
				$handler = new Fcz\FileUploadHandler($this);
				$handler->deleteUploadedFile($id);
			}

			// Handle presentation
			$cmsPage = $expo->ref("CmsPages", "Presentation");
			if ($values["KeepPresentation"] == true)
			{
				$cmsPage->update(array(
					"Name" => "(orphaned) " . $cmsPage["Name"]
					"Description" => "(orphaned) " . $cmsPage["Description"]
				));
			}
			else
			{
				$cmsPage->delete();
			}

			// Handle images
			if ($values["ImageOperation"] == "Delete")
			{
				foreach ($id in $database->table("Images"))
			}

			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

	}

}
