<?php

use Nette\Application;
use Nette\Application\UI;
use Nette\Utils\Html;
use Nette\Diagnostics\Debugger;

/**
 * Image gallery presenter
 */
class GalleryPresenter extends DiscussionPresenter
{

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
			
		$this->template->setParameters(array(
			'user' => $user,
			'expositions' => $expositions
		));
	}



	public function renderEditExposition($expositionId)
	{
		
	}



	public function renderExposition($expositionId)
	{
		$database = $this->context->database;

		$exposition = $database->table("ImageExpositions")->where("Id", $expositionId)->fetch();

		$this->template->setParameters(array(
			'user' => $exposition->ref("Owner"),
			'exposition' => $exposition
		));
	}



	public function renderDeleteExposition($expositionId)
	{
		// Check permissions (general)
		if (! $this->user->isLoggedIn() || ! $this->user->isInRole('approved'))
		{
			throw new ForbiddenRequestException("Nemáte oprávnění");
		}
	
		// Check params
		if ($expositionId == null)
		{
			throw new BadRequestException("Zadana expozice neexistuje");
		}

		// Check data
		$database = $this->context->database;
		$expo = $database->table("ImageExpositions")->where("Id", $expositionId)->fetch();
		if ($expo === false)
		{
			throw new BadRequestException("Zadana expozice neexistuje");
		}
		
		// Check permissions (specific)
		if ($expo["Owner"] != $this->user->id)
		{
			throw new ForbiddenRequestException("Nemáte oprávnění");
		}

		// Prepare form
		$form = $this["deleteExpositionForm"];
		$expoList = $this->composeExpositionSelectList();
		unset($expoList[$expositionId]); // Exclude the expo we're about to remove.
		$form["TargetExpo"]->setItems($expoList);

		// Setup template
		$this->template->setParameters(array(
			"imageCount" => $expo->related("Images", "Exposition")->count(),
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
	
	
	
	public function createComponentGalleryThumbnails()
	{
		if ($this->action == 'user')
		{
			return new Fcz\GalleryThumbnails($this, $this->getParameter('userId'), null);
		}
		else
		{
			return new Fcz\GalleryThumbnails($this, null, $this->getParameter('id'));
		}
	}
	
	
	
	public function createComponentMainExpositionThumbnails()
	{
		$userId = $this->getParameter('userId');
		if ($userId == null)
		{
			$userId = $this->user->id;
		}
		return new Fcz\GalleryThumbnails($this, $userId, null);
	}
	
	
	
	public function createComponentExpositionThumbnails()
	{
		$expoId = $this->getParameter('expositionId');
		if ($expoId == null)
		{
			throw new BadRequestException();
		}
		return new Fcz\GalleryThumbnails($this, null, $expoId);
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
					"Name" => "Exposition (user {$this->user->identity->nickname})",
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
			$database->table("UploadedFiles")->where("Id", $thumbId)->update(array(
				"SourceId" => $exposition["Id"]
			));

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



	public function processValidatedEditExpositionForm(UI\Form $form)
	{
		$values = $form->getValues();
		$database = $this->context->database;
		
		$exposition = $database->table("ImageExpositions")->where("Id", $this->getParameter("expositionId"))->fetch();

		// CMS
		$cmsId = null;
		if ($exposition["Presentation"] == null)
		{
			if ($values["PresentationText"] != "")
			{
				$cms = $database->table("CmsPages")->insert(array(
					"Name" => "{$this->user->identity->nickname} Gallery",
					"Text" => $values["PresentationText"]
				));
				$cmsId = $cms["Id"];
				$this->flashMessage("Byla vytvořena prezentace");
			}
		}
		elseif ($values["PresentationText"] == "")
		{
			if ($exposition["Presentation"] != null)
			{
				$exposition->ref("Presentation")->remove();
				$this->flashMessage("Prezentace byla smazána");
			}
		}
		else
		{
			$cmsPage = $exposition->ref("Presentation");
			$cmsPage->update(array(
				"Text" => $values["PresentationText"]
			));
			$cmsId = $cmsPage["Id"];
			$this->flashMessage("Prezentace byla upravena");
		}
		
		// Thumbnail
		$thumbnailId = null;
		
		$deleteThumb = ($values["DeleteThumbnail"] == true) || $values["NewThumbnail"]->isOk(); 
		$thumbDeleted = false;
		$thumbAdded = false;
		if ($exposition["Thumbnail"] != null && $deleteThumb)
		{
			$this->getUploadHandler()->deleteUploadedFile($exposition["Thumbnail"]);
			$thumbDeleted = true;
			$thumbnailId = null;
		}
		
		if ($values["NewThumbnail"]->isOk())
		{
			$uploadComponent = $form->getComponent('NewThumbnail', true);
			if ($uploadComponent->isFilled() == true) // If anything was uploaded...
			{
				list($thumbnailId, $uploadKey) = $this->getUploadHandler()->handleUpload(
					$values["NewThumbnail"], 
					'ExpositionThumbnail', 
					$exposition['Id']
				);
				$thumbAdded = true;
			}
		}
		
		if ($thumbDeleted != $thumbAdded)
		{
			if ($thumbDeleted)
			{
				$this->flashMessage("Ikona smazána");
			}
			else
			{
				$this->flashMessage("Ikona přidána");
			}
		}

		// Exposition
		$database->table("ImageExpositions")->where("Id", $exposition["Id"])->update(array(
				"Name" => $values["Name"],
				"Description" => $values["Description"],
				"Presentation" => $cmsId,
				"Thumbnail" => $thumbnailId
			));

		$this->flashMessage("Expozice byla upravena");
		$this->redirect("Gallery:exposition", $exposition["Id"]);

	}
	
	
	
	public function validateEditExpositionForm(UI\Form $form)
	{
		$uploadHandler = new Fcz\FileUploadHandler($this);

		// Validate thumbnail
		$uploadComponent = $form->getComponent('NewThumbnail', true);
		if ($uploadComponent->isFilled() == true) // If anything was uploaded...
		{
			list($result, $errMsg) = $uploadHandler->validateUpload($uploadComponent->getValue(), 'ExpositionThumbnail');
			if ($result == false)
			{
				$form->addError('Ikona: ' . $errMsg);
			}
		}
	}



	public function createComponentEditExpositionForm()
	{
		$database = $this->context->database;

		$exposition = $database->table("ImageExpositions")->where("Id", $this->getParameter("expositionId"))->fetch();
		
		if ($exposition == null)
		{
			throw new Nette\FileNotFoundException();
		}
	
		$form = new UI\Form;

		$form->addText("Name", "Název")->setValue($exposition["Name"]);
		$form->addText("Description", "Popis")->setValue($exposition["Description"]);
		$form->addCheckbox("DeleteThumbnail", "Smazat ikonu");
		$form->addUpload("NewThumbnail", "Nová ikona");
		$presentation = $form->addTextArea("PresentationText", "Prezentace");
		if ($exposition["Presentation"] != null)
		{
			$presentation->setValue($exposition->ref("CmsPages", "Presentation")["Text"]);
		}

		$form->addSubmit("SubmitUpdatedExposition", "Uložit");
		$form->onValidate[] = $this->validateEditExpositionForm;
		$form->onSuccess[] = $this->processValidatedEditExpositionForm;

		return $form;
	}



	public function composeExpositionSelectList()
	{
		$database = $this->context->database;
	
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
		
		return $expoSelectList;
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
		$form->AddTextArea("Description", "Popis", 2, 5); // Small dimensions to allow CSS scaling

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
		$database = $this->context->database;
		$values = $form->getValues();

		// Check if exposition exists
		if ($values["ExpositionId"] != 0)
		{
			$expoResult = $database->table("ImageExpositions")->select("Id", $values["ExpositionId"])->count();
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

		$form->addRadioList("ImageOperation", "Akce:", array(
			"Delete" => "Vymazat",
			"Move" => "Presunout do jine expozice:"
		));

		$form->addSelect("TargetExpo", "Kam přesunout: ");

		$form->addCheckbox("KeepThumbnail", "Ponechat si ikonu");

		$form->addCheckbox("KeepPresentation", "Ponechat si prezentaci");

		$form->addSubmit("SubmitDeleteExpo", "Smazat");
		$form->onSuccess[] = $this->processValidatedDeleteExpositionForm;
		
		return $form;
	}



	public function processValidatedDeleteExpositionForm($form)
	{
		$values = $form->getValues();
		$database = $this->context->database;
		$expoId = $this->getParameter("expositionId");

		/*try
		{*/
			$database->beginTransaction();
		
			$expo = $database->table("ImageExpositions")->where("Id", $expoId)->fetch();
			$expoThumbnail = $expo->ref("UploadedFiles", "Thumbnail");
			$expoCmsPage = $expo->ref("CmsPages", "Presentation");
			
			$expo->delete();

			// Handle thumbnail
			if ($expoThumbnail != null)
			{
				if ($values["KeepThumbnail"] == true)
				{
					$expoThumbnail->update(array(
						"SourceType" => null,
						"SourceId" => null
					));
				}
				else
				{
					// Delete file
					$this->getUploadHandler()->deleteUploadedFile($expoThumbnail);
				}
			}

			// Handle presentation
			if ($expoCmsPage != null)
			{
				if ($values["KeepPresentation"] == true)
				{
					$expoCmsPage->update(array(
						"Name" => "(orphaned) " . $cmsPage["Name"],
						"Description" => "(orphaned) " . $cmsPage["Description"]
					));
				}
				else
				{
					$expoCmsPage->delete();
				}
			}

			// Handle images
			if ($values["ImageOperation"] == "Delete")
			{
				foreach ($expo->related("Images") as $image)
				{
					$this->getContentManager()->deleteImage($image);
				}
			}
			else if ($values["ImageOperation"] == "Move")
			{
				$newExpo = $values["TargetExpo"] == 0 ? null : (int) $values["TargetExpo"];
				foreach ($expo->related("Images") as $image)
				{
					$image->update("ExpositionId", $newExpo);
				}
			}

			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

		$this->redirect("Gallery:user");
	}

}
