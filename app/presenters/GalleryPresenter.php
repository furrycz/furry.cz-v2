<?php

use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;
use Nette\Application\UI;
use Nette\Utils\Html;
use Nette\Diagnostics\Debugger;

/**
 * Image gallery presenter
 */
class GalleryPresenter extends BasePresenter
{


	/**
	 * Action: Shows a gallery main page.
	 * Gallery main page contains a list of authors & recent additions and changes.
	 */
	public function renderDefault()
	{
		$database = $this->context->database;

		// AUTHOR LIST

		$authorsDB = $database->table("Users")->select("Id, Nickname, AvatarFilename");

		$authors = array();
		foreach ($authorsDB as $authorUser)
		{
			$allImages = $authorUser->related("Ownership")->where("Content.Type", "Image");
			$totalImages = $allImages->count();

			if ($totalImages > 0)
			{
				$authors[] = array(
					'user' => $authorUser,
					'numImagesTotal' => $totalImages,
					'numImagesNotVisited' => 1 // TODO
				);
			}
		}

		// RECENTLY POSTED IMAGES

		// Fetch data
		$since = new DateTime();
		$since = $since->sub(new DateInterval('P10D')); // Today minus 10 days
		$recentPostsDB = $database
			->table("Content")
			->where('Type = "Image" AND (LastModifiedTime > ? OR LastModifiedTime = "0000-00-00")', $since)
			->order("LastModifiedTime DESC");

		// Prepare listing
		$recentPosts = array();
		foreach ($recentPostsDB as $content)
		{
			$image = $content->related("Images")->fetch();
			$author = $content->related("Ownership")->fetch()->ref("User");
			$lastVisit = $content->related("LastVisits")->where("UserId", $this->user->id)->fetch();
			$whenPostedText = Fcz\CmsUtilities::getTimeElapsedString(strtotime($content["TimeCreated"]));

			if ($this->user->isInRole('approved'))
			{
				$notVisited = ($lastVisit === false || $lastVisit["Time"] < $content["LastModifiedTime"]);
			}
			else
			{
				$notVisited = false;
			}

			$recentPosts[] = array(
				'content' => $content,
				'author' => $author,
				'image' => $image,
				'whenPostedText' => $whenPostedText,
				'notVisited' => $notVisited
			);
		}

		// SETUP TEMPLATE

		$this->template->setParameters(array(
			'authors' => $authors,
			'recentPosts' => $recentPosts
		));
	}



	public function renderUser($userId, $pageNumber)
	{
		$database = $this->context->database;
		if ($userId == null) /* Display current user's page */
		{
			$this->getAuthorizator()->verifyAccountApproved($this->user);
			$userId = $this->user->id;
		}

		$user = $database->table("Users")->where(array("Id"=> $userId))->fetch();

		$expositions = $database->table("ImageExpositions")->where("Owner", $userId);
			
		$this->template->setParameters(array(
			'user' => $user,
			'expositions' => $expositions
		));
	}



	/**
	* @return \Nette\Database\ActiveRow The exposition data.
	*/
	public function verifyExpositionOwnership($expositionId, \Nette\Security\User $user)
	{
		// Check permissions (general)
		$this->getAuthorizator()->verifyAccountApproved($user);

		// Check params
		if ($expositionId == null)
		{
			throw new BadRequestException("Zadaná expozice neexistuje");
		}

		// Check data
		$database = $this->context->database;
		$expo = $database->table("ImageExpositions")->where("Id", $expositionId)->fetch();
		if ($expo === false)
		{
			throw new BadRequestException("Zadaná expozice neexistuje");
		}

		// Check permissions (specific)
		if ($expo["Owner"] != $this->user->id)
		{
			throw new ForbiddenRequestException("Nejste vlastníkem této expozice");
		}

		return $expo;
	}



	public function renderCreateExposition()
	{
		$this->getAuthorizator()->verifyAccountApproved($this->user);
	}




	public function renderEditExposition($expositionId)
	{
		$this->verifyExpositionOwnership($expositionId, $this->user);
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
		$expo = $this->verifyExpositionOwnership($expositionId, $this->user);

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



	public function renderShowImage($imageId, $page)
	{
		list($image, $content, $access) = $this->checkImageAccess($imageId, $this->user);
		if (! $access["CanViewContent"])
		{
			throw new ForbiddenRequestException("K tomuto obrázku nemáte přístup");
		}

		$this->getContentManager()->updateLastVisit($content, $this->user);

		$author = $image->ref("ContentId")->related("Ownership", "ContentId")->fetch()->ref("UserId");

		$this->template->setParameters(array(
			'image' => $image,
			'author' => $author,
			"access" => $access
		));
	}



	public function renderAddImage($exposition)
	{
		if ($exposition !== null) // NULL = Default exposition
		{
			$this->verifyExpositionOwnership($exposition, $this->user);
		}
	}



	public function renderEditImage($imageId)
	{
		// Check access
		list($image, $content, $access) = $this->checkImageAccess($imageId, $this->user);
		if (! $access["CanEditContentAndAttributes"])
		{
			throw new ForbiddenRequestException("K tomuto obrázku nemáte oprávnění");
		}
	}



	public function renderDeleteImage($imageId)
	{
		// Check access
		list($image, $content, $access) = $this->checkImageAccess($imageId, $this->user);
		if (! $access["CanDeleteContent"])
		{
			throw new ForbiddenRequestException("K tomuto obrázku nemáte oprávnění");
		}
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
		// Check permissions
		$this->getAuthorizator()->verifyAccountApproved($this->user);

		$thumbUpload = $form->getComponent("Thumbnail", true);

		if ($thumbUpload->isFilled())
		{
			$handler = new Fcz\FileUploadHandler($this);
			$handler->validateFormUpload($form, "Ikona", $form->values["Thumbnail"], "expositionThumbnail");
		}
	}



	public function processValidatedNewExpositionForm($form)
	{
		// Check permissions
		$this->getAuthorizator()->verifyAccountApproved($this->user);

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
			$uploadControl = $form->getComponent("Thumbnail", true);
			if ($uploadControl->isFilled())
			{
				list ($thumbId, $thumbKey) = $this->getUploadHandler()->handleUpload($uploadControl->getValue(), "ExpositionThumbnail", null);
			}

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
		// Check permissions
		$this->getAuthorizator()->verifyAccountApproved($this->user);

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
		// Check permissions
		$this->getAuthorizator()->verifyAccountApproved($this->user);

		// Validate thumbnail
		$uploadComponent = $form->getComponent('NewThumbnail', true);
		if ($uploadComponent->isFilled() == true) // If anything was uploaded...
		{
			list($result, $errMsg) = $this->getUploadHandler()->validateUpload($uploadComponent->getValue(), 'ExpositionThumbnail');
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

		$targetExpo = $this->getParameter("exposition");
		if ($targetExpo != null)
		{
			$expoSelect->setValue($targetExpo);
		}

		// Flags
		$form->addCheckbox('IsDiscussionAllowed', 'Povolit diskuzi')->setValue(true);
		$form->addCheckbox('IsRatingAllowed', 'Povolit hodnoceni')->setValue(true);

		// Restriction
		$form->addSelect("Restriction", "Přístupnost", array(
			1 => "Všichni",
			2 => "Pouze schválení",
			3 => "Pouze schválení 18+",
		));
		$form->addCheckbox('CanListContent', 'Vidí stránku')->setValue(true);
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
		$form->onValidate[] = $this->validateAddImageForm;
		$form->onSuccess[] = $this->processValidatedAddImageForm;
		$form->addSubmit('SubmitNewArtwork', 'Vlozit obrazek');

		return $form;
	}



	public function validateAddImageForm($form)
	{
		// Check permissions
		$this->getAuthorizator()->verifyAccountApproved($this->user);

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
		// Check permissions
		$this->getAuthorizator()->verifyAccountApproved($this->user);

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
				'Type'                => 'Image',
				'TimeCreated'         => new DateTime,
				'IsForRegisteredOnly' => $values["Restriction"] >= 2,
				'IsForAdultsOnly'     => $values["Restriction"] == 3,
				"IsDiscussionAllowed" => $values["IsDiscussionAllowed"],
				"IsRatingAllowed"     => $values["IsRatingAllowed"],
				'DefaultPermissions'  => $defaultPermission['Id']
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

		$this->flashMessage('Obrázek byl nahrán', 'ok');
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
		// Check permissions
		$expo = $this->verifyExpositionOwnership($this->getParameter("expositionId"), $this->user);

		$values = $form->getValues();
		$database = $this->context->database;
		$expoId = $this->getParameter("expositionId");

		/*try
		{*/
			$database->beginTransaction();

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
					$this->getUploadHandler()->deleteUploadedFile($expoThumbnail, "expositionThumbnail");
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



	public function createComponentEditImageForm()
	{
		// Get data
		$database = $this->context->database;
		$image = $database->table("Images")->where("Id", $this->getParameter("imageId"))->fetch();
		if ($image === false)
		{
			throw new BadRequestException("Zadaný obrázek neexistuje", 404);
		}

		// Create form
		$form = new UI\Form;

		$form->addUpload("ArtworkUpload", "Změnit obrázek");
			// NOTE: Can't use rule "Form::IMAGE" => makes field "required"

		// Artwork title
		$form->addText('Title', 'Název * :')
			->setValue($image["Name"])
			->setRequired('Je nutné zadat název dila')
			->getControlPrototype()->class = 'Wide';

		// Description text
		$form->AddTextArea("Description", "Popis", 2, 5) // Small dimensions to allow CSS scaling
			->setValue($image["Description"]);

		// Exposition

		$expoSelect = $form->AddSelect("ExpositionId", "Expozice:", $this->composeExpositionSelectList());
		if ($image["Exposition"] != null)
		{
			$expoSelect->setValue($image["Exposition"]);
		}

		// Content flags
		$content = $image->ref("Content");
		$form->addCheckbox('IsForRegisteredOnly', 'Jen pro registrované')
			->setValue($content["IsForRegisteredOnly"]);
		$form->addCheckbox('IsForAdultsOnly', '18+')
			->setValue($content["IsForAdultsOnly"]);
		$form->addCheckbox('IsDiscussionAllowed', 'Povolit diskuzi')
			->setValue($content["IsDiscussionAllowed"]);
		$form->addCheckbox('IsRatingAllowed', 'Povolit hodnoceni')
			->setValue($content["IsRatingAllowed"]);


		// Submit
		$form->onValidate[] = $this->validateEditImageForm;
		$form->onSuccess[]  = $this->processValidatedEditImageForm;
		$form->addSubmit('SubmitUpdatedArtwork', 'Uložit změny');

		return $form;
	}



	public function validateEditImageForm(UI\Form $form)
	{

		// Check permissions
		$this->getAuthorizator()->verifyAccountApproved($this->user);

		$database = $this->context->database;
		$values = $form->getValues();

		// Validate image upload
		if ($form->getComponent("ArtworkUpload")->isFilled() == true) // If anything was uploaded...
		{
			list($result, $errMsg) = $this->getUploadHandler()->validateUpload($values["ArtworkUpload"], 'genericFile');
			if ($result == false)
			{
				$form->addError('Upload obrázku: ' . $errMsg);
			}
		}

		// Check if exposition exists
		if ($values["ExpositionId"] != 0)
		{
			$expoResult = $database->table("ImageExpositions")->select("Id", $values["ExpositionId"])->count();
			if ($expoResult == 0)
			{
				$form->addError("Zadaná expozice neexistuje");
			}
		}

		// Check image
		$image = $database->table("Images")->where("Id", $this->getParameter("imageId"))->fetch();
		if ($image === false)
		{
			throw new BadRequestException("Obrázek nenalezen");
		}
	}



	/**
	* Fetches item from DB and checkes permissions.
	* @return array $image, $content, $access
	* @throws BadRequestException If the image isn't found.
	*/
	private function checkImageAccess($imageId, $user)
	{
		$image = $this->fetchImage($imageId);

		$content = $image->ref("Content");
		if ($content === false)
		{
			throw new ApplicationException("Database/Image (Id: {$imageId}) has no asociated Database/Content");
		}

		$access = $this->getAuthorizator()->authorize($content, $user);
		return array($image, $content, $access);
	}



	/**
	* Fetches item from DB
	* @return \Nette\Database\Table\ActiveRow Image entry.
	* @throws BadRequestException If the image isn't found.
	*/
	private function fetchImage($imageId)
	{
		$database = $this->context->database;
		// Fetch image
		$image = $database->table("Images")->where("Id", $imageId)->fetch();
		if ($image === false)
		{
			throw new BadRequestException("Obrázek nenalezen");
		}
		return $image;
	}



	public function processValidatedEditImageForm(UI\Form $form)
	{
		// Fetch & check data
		$imageId = $this->getParameter("imageId");
		list($image, $content, $access) = $this->checkImageAccess($imageId, $this->user);

		// Check permissions
		if (! $access["CanEditContentAndAttributes"])
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

		// Update database
		$database = $this->context->database;
		$database->beginTransaction();
		/*try
		{*/
			$values = $form->getValues();

			$content->update(array(
				'IsForRegisteredOnly' => $values['IsForRegisteredOnly'],
				'IsForAdultsOnly' => $values['IsForAdultsOnly'],
				"IsDiscussionAllowed" => $values["IsDiscussionAllowed"],
				"IsRatingAllowed" => $values["IsRatingAllowed"],
				"LastModifiedTime" => new DateTime(),
				"LastModifiedByUser" => $this->user->id
			));

			$upload = $form->getComponent("ArtworkUpload");
			if ($upload->isFilled())
			{
				$this->getUploadHandler()->handleUploadUpdate($values["ArtworkUpload"], $image["UploadedFileId"]);

				// Clear thumbnail cache
				$this->getUploadHandler()->deleteImagePreviews($image["UploadedFileId"]);
			}

			// Update image entry
			$image->update(array(
				'Name' => $values['Title'],
				"Description" => $values["Description"],
				"Exposition" => $values["ExpositionId"]
			));

			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

		$this->flashMessage('Obrázek byl upraven', 'ok');
		if ($values["ExpositionId"] != 0)
		{
			$this->redirect("Gallery:exposition", $values["ExpositionId"]);
		}
		else
		{
			$this->redirect('Gallery:user');
		}
	}



	public function createComponentDeleteImageForm()
	{
		// Get data
		$database = $this->context->database;
		$imageCount = $database->table("Images")->where("Id", $this->getParameter("imageId"))->count();
		if ($imageCount === 0)
		{
			throw new BadRequestException("Zadaný obrázek neexistuje", 404);
		}

		// Create form
		$form = new UI\Form;

		// Submit
		$form->onSuccess[]  = $this->processValidatedDeleteImageForm;
		$form->addSubmit('SubmitDeleteImage', 'Smazat obrázek');

		return $form;
	}



	public function processValidatedDeleteImageForm(UI\Form $form)
	{
		// Fetch & check data
		$database = $this->context->database;
		$imageId = $this->getParameter("imageId");
		list($image, $content, $access) = $this->checkImageAccess($imageId, $this->user);

		// Check permissions
		if (! $access["CanDeleteContent"])
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}
		$expoId = $image["Exposition"];

		$database->beginTransaction();
		//try
		//{
			$image->delete();
			$this->getUploadHandler()->deleteUploadedFile($image->ref("UploadedFileId"), 'genericFile');

			$content = $image->ref("Content");

			$content->related("Ownership")->delete();
			$database->table("Permissions")->where(":Access.ContentId", $content["Id"]);
			$content->related("Access")->delete();
			$content->related("LastVisits")->delete();
			$content->delete();

			$database->commit();

			$this->flashMessage("Obrázek byl smazán", "ok");
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
			$this->flashMessage("Obrázek se nepodařilo smazat", "error");
		}*/

		if ($expoId != null)
		{
			$this->redirect("Gallery:exposition", $expoId);
		}
		else
		{
			$this->redirect('Gallery:user');
		}
	}



	public function createComponentDiscussion()
	{
		$database = $this->context->database;
		$id = $this->getParameter("imageId");
		$image = $database->table("Images")->where('Id', $id)->fetch();
		if ($image === false)
		{
			throw new BadRequestException("Obrázek neexistuje", 404);
		}
		$content = $image->ref('Content');
		$access = $this->getAuthorizator()->authorize($content, $this->user);
		$baseUrl = $this->presenter->getHttpRequest()->url->baseUrl;

		return new Fcz\Discussion($this, $content, $id, $baseUrl, $access, $this->getParameter('page'), null);
	}



	public function createComponentPermissions()
	{
		$image = $this->fetchImage($this->getParameter("imageId"));

		$data = array(
			"Permisions" => array(  //Permision data
				//$Zkratka 1 písmeno(""==Nezobrazí), $Popis, $BarvaPozadí, $Parent(""!=Nezobrazí), $Zařazení práv, $default check
				"CanListContent"              => array("L","Vidí obrázek v expozici","","CanViewContent","",1),
				"CanReadPosts"                => array("R","Může číst diskusi","","","",1),
				"CanViewContent"              => array("V","Může obrázek navštívit","","CanReadPosts","Context",1),
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
		return new Fcz\Permissions($this, $image->ref("ContentId"), $data);
	}



	public function renderManagePermissions($imageId)
	{
		// Check access
		list($image, $content, $access) = $this->checkImageAccess($imageId, $this->user);
		if (! $access["CanEditPermissions"])
		{
			throw new BadRequestException("Nemáte oprávnění upravovat přístupová práva");
		}

		// Setup template
		$this->template->image = $image;
	}

}
