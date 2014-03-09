<?php

use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\UI;
use Nette\Utils\Html;
use Nette\Diagnostics\Debugger;

/**
 * Image gallery presenter
 */
class GalleryPresenter extends DiscussionPresenter
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
			->where(array(
				"Type" => "Image",
				"LastModifiedTime > ?" => $since
			))
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
		if ($userId == null)
		{
			if ($this->user->isInRole('approved'))
			{
				$userId = $this->user->id;
			}
			else
			{
				throw new ForbiddenRequestException("Váš uživatelský účet není schválen");
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
		// Check access
		if (! $this->user->isInRole('approved'))
		{
			throw new ForbiddenRequestException('Pouze schválení uživatelé mohou upravovat expozice');
		}
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
		if (! $this->user->isInRole('approved'))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}
	
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



	public function renderShowImage($imageId)
	{
		$database = $this->context->database;

		// Fetch image
		$image = $database->table("Images")->where("Id", $imageId)->fetch();
		if ($image === false)
		{
			throw new BadRequestException("Obrázek nenalezen");
		}
		$content = $image->ref("ContentId");

		// Check access
		$permissions = $this->getAuthorizator()->authorize($content, $this->user);
		if (! $permissions["CanViewContent"])
		{
			throw new ForbiddenRequestException("K tomuto obrázku nemáte přístup");
		}

		// Fill last visit
		if ($this->user->isInRole('approved'))
		{
			$lastVisit = $database
				->table("LastVisits")
				->where("ContentId = ? AND UserId = ?", $image["ContentId"], $this->user->id)
				->fetch();
			if ($lastVisit !== false)
			{
				$lastVisit->update(array("Time" => new DateTime()));
			}
			else
			{
				$database
					->table("LastVisits")
					->insert(array(
						"ContentId" => $image["ContentId"],
						"UserId" => $this->user->id,
						"Time" => new DateTime()
					));
			}
		}

		// Setup discussion
		if ($permissions["CanReadPosts"])
		{
			$this->setupDiscussion($permissions, $content, $imageId, null, null);
		}

		$this->template->setParameters(array(
			'image' => $image,
			'author' => $content->related("Ownership", "ContentId")->fetch()->ref("UserId"),
			"access" => $permissions
		));
	}



	public function renderAddImage($expositionId)
	{
		// Check access
		if (! $this->user->isInRole('approved'))
		{
			throw new ForbiddenRequestException('Pouze schválení uživatelé mohou vkladat obrazky do galerie');
		}
	}



	public function renderEditImage($imageId)
	{
		// Check access
		if (! $this->user->isInRole('approved'))
		{
			throw new ForbiddenRequestException('Pouze schválení uživatelé mohou vkladat obrazky do galerie');
		}

		// Check data
		$database = $this->context->database;
		$item = $database->table("Images")->where("Id", $imageId)->fetch();
		if ($item === false)
		{
			throw new BadRequestException("Obrázek neexistuje");
		}

		// Check authority
		if (! $this->user->isInRole('admin'))
		{
			$ownerId = $item->ref("ContentId")->related("Ownership", "ContentId")->fetch()->UserId;
			if ($ownerId != $this->user->id)
			{
				throw new ForbiddenRequestException('Nejste oprávněn(a) manipulovat s touto položkou');
			}
		}
	}



	public function renderDeleteImage($imageId)
	{
		// Check access
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Pouze schválení uživatelé mohou vkladat obrazky do galerie');
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
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

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
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

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
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

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
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

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
		// Check permissions
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

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
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

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
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

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
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

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



	public function processValidatedEditImageForm(UI\Form $form)
	{
		// Check permissions
		if (!($this->user->isInRole('approved') || $this->user->isInRole('admin')))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

		$values = $form->getValues();
		$database = $this->context->database;

		// Fetch & check data
		$imageId = $this->getParameter("imageId");
		$image = $database->table("Images")->where("Id", $imageId)->fetch();
		if ($image === false)
		{
			throw new BadRequestException("Zadaná položka neexistuje");
		}
		$content = $image->ref("Content");
		if ($content === false)
		{
			throw new ApplicationException(500, "Database/Image (Id: {$imageId}) has no asociated Database/Content");
		}

		// Update database
		$database->beginTransaction();
		/*try
		{*/

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
		$database = $this->context->database;
		$imageId = $this->getParameter("imageId");
		$image = $database->table("Images")->where("Id", $imageId)->fetch();
		if ($image === false)
		{
			throw new BadRequestException("Zadaný obrázek neexistuje", 404);
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

}
