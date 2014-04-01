<?php

use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;
use Nette\Application\UI;
use Nette\Utils\Html;
use Nette\Diagnostics\Debugger;

/**
 * Literature gallery presenter
 */
class WritingsPresenter extends BasePresenter
{


	/**
	 * Action: Shows main page.
	 * Contains a list of authors & recent additions and changes.
	 */
	public function renderDefault()
	{
		$database = $this->context->database;

		// CATEGORY LIST

		$categoriesDb = $database->table("WritingCategories");
		if (! $this->user->isInRole("adult"))
		{
			$categoriesDb = $categoriesDb->where("isForAdultsOnly = ?", false);
		}

		// AUTHOR LIST

		$authorsDB = $database->table("Users")->select("Id, Nickname, AvatarFilename");

		$authors = array();
		foreach ($authorsDB as $authorUser)
		{
			$allWritings = $authorUser->related("Ownership")->where("Content.Type", "Writing");
			$totalWritings = $allWritings->count();

			if ($totalWritings > 0)
			{
				$authors[] = array(
					"user" => $authorUser,
					"numImagesTotal" => $totalWritings,
					"numWritingsNotVisited" => 1 // TODO
				);
			}
		}

		// RECENTLY POSTED TEXTS

		// Fetch data
		$since = new DateTime();
		$since = $since->sub(new DateInterval("P10D")); // Today minus 10 days
		$recentPostsDB = $database
			->table("Content")
			->where(array(
				"Type" => "Writing",
				"LastModifiedTime > ?" => $since
			))
			->order("LastModifiedTime DESC");

		// Prepare listing
		$recentPosts = array();
		$numNotVisitedPosts = 0;
		foreach ($recentPostsDB as $content)
		{
			$writing = $content->related("Writings")->fetch();
			$author = $content->related("Ownership")->fetch()->ref("User");
			$lastVisit = $content->related("LastVisits")->where("UserId", $this->user->id)->fetch();
			$whenPostedText = Fcz\CmsUtilities::getTimeElapsedString(strtotime($content["TimeCreated"]));

			if ($this->user->isInRole("approved"))
			{
				$notVisited = ($lastVisit === false || $lastVisit["Time"] < $content["LastModifiedTime"]);
				if ($notVisited)
				{
					$numNotVisitedPosts++;
				}
			}
			else
			{
				$notVisited = false;
			}

			$recentPosts[] = array(
				"content" => $content,
				"author" => $author,
				"writing" => $writing,
				"whenPostedText" => $whenPostedText,
				"notVisited" => $notVisited,
			);
		}

		// SETUP TEMPLATE

		$this->template->setParameters(array(
			"authors" => $authors,
			"recentPosts" => $recentPosts,
			"categories" => $categoriesDb,
			"numNotVisitedPosts" => $numNotVisitedPosts,
		));
	}



	/**
	 * Action: Shows main page.
	 * Contains a list of authors & recent additions and changes.
	 */
	public function renderManageCategories()
	{
		// Check access
		if (! $this->user->isInRole('admin'))
		{
			throw new ForbiddenRequestException('Nemáte oprávnění');
		}
		$database = $this->context->database;
		$this->template->categories = $database->table("WritingCategories");
	}



	public function renderAddCategory()
	{
		// Check access
		if (! $this->user->isInRole('admin'))
		{
			throw new ForbiddenRequestException('Nemáte oprávnění');
		}
	}



	public function renderUser($userId, $pageNumber)
	{
		$database = $this->context->database;
		if ($userId == null)
		{
			if ($this->user->isInRole("approved"))
			{
				$userId = $this->user->id;
			}
			else
			{
				throw new ForbiddenRequestException("Váš uživatelský účet není schválen");
			}
		}

		$user = $database->table("Users")->where(array("Id"=> $userId))->fetch();

		$contentWritings = $database->table("Content")->where(array(
			"Ownership:UserId" => $userId,
			"Type" => "Writing",
		));

		$this->template->setParameters(array(
			"user" => $user,
			"contentWritings" => $contentWritings,
		));
	}



	public function renderEditCategory($categoryId)
	{
		// Check access
		if (! $this->user->isInRole('admin'))
		{
			throw new ForbiddenRequestException('Nemáte oprávnění');
		}
	}



	public function renderDeleteCategory($categoryId)
	{
		// Check permissions (general)
		if (! $this->user->isInRole('admin'))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}
	
		// Check params
		if ($categoryId == null)
		{
			throw new BadRequestException("Zadaná expozice neexistuje");
		}

		// Check data
		$database = $this->context->database;
		$category = $database->table("WritingCategories")->where("Id", $categoryId)->fetch();
		if ($category == null)
		{
			throw new BadRequestException();
		}

		// Setup template
		$this->template->setParameters(array(
			"writingCount" => $category->related("Writings", "CategoryId")->count(),
			"category" => $category
		));
	}



	public function renderShowWriting($writingId, $page)
	{
		list($writing, $content, $access) = $this->checkWritingAccess($writingId, $this->user);
		if (! $access["CanViewContent"])
		{
			throw new ForbiddenRequestException("K tomuto textu nemáte přístup");
		}

		if ($this->user->isLoggedIn())
		{
			$this->getContentManager()->updateLastVisit($content, $this->user->id);
		}

		$author = $writing->ref("ContentId")->related("Ownership", "ContentId")->fetch()->ref("UserId");
		$this->template->setParameters(array(
			"writing" => $writing,
			"author" => $author,
			"access" => $access
		));
	}



	public function renderAddWriting()
	{
		// Check access
		if (! $this->user->isInRole('approved'))
		{
			throw new ForbiddenRequestException('Pouze schválení uživatelé mohou vkládat texty');
		}
	}



	public function renderEditWriting($writingId)
	{
		list($writing, $content, $access) = $this->checkWritingAccess($writingId, $this->user);
		if (! $access["CanEditContentAndAttributes"])
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) manipulovat s touto položkou');
		}
	}



	public function renderDeleteWriting($writingId)
	{
		list($writing, $content, $access) = $this->checkWritingAccess($writingId, $this->user);
		if (! $access["CanDeleteContent"])
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) manipulovat s touto položkou');
		}
	}



	public function createComponentNewCategoryForm()
	{
		$form = new UI\Form;

		$form->addText("Name", "Název");

		$form->addText("Description", "Popis");

		$form->addCheckbox("IsForAdultsOnly", "Pouze pro dospělé");

		$form->addSubmit("SubmitNewCategory", "Vytvořit");
		$form->onSuccess[] = $this->processValidatedNewCategoryForm;

		return $form;
	}



	public function processValidatedNewCategoryForm(UI\Form $form)
	{
		// Check permissions
		if (! $this->user->isInRole('admin'))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

		$values = $form->getValues();
		$database = $this->context->database;

		$database->table("WritingCategories")->insert(array(
			"Name" => $values["Name"],
			"Description" => $values["Description"],
			"IsForAdultsOnly" => $values["IsForAdultsOnly"],
		));
		if ($database === false)
		{
			throw new ApplicationException("Failed to save new DB/WritingCategories entry");
		}
		$this->flashMessage("Nová kategorie vytvořena","ok");

		$this->redirect("Writings:manageCategories");

	}



	public function processValidatedEditCategoryForm(UI\Form $form)
	{
		// Check permissions
		if (! $this->user->isInRole('admin'))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

		$database = $this->context->database;
		$category = $database->table("WritingCategories")->where("Id", $this->getParameter("categoryId"))->fetch();
		if ($category === false)
		{
			throw new BadRequestException();
		}

		$values = $form->getValues();
		$result = $category->update(array(
			"Name" => $values["Name"],
			"Description" => $values["Description"],
			"IsForAdultsOnly" => $values["IsForAdultsOnly"],
		));
		if ($result === false)
		{
			throw new ApplicationException("Failed to update DB/WritingCategories entry");
		}
		$this->flashMessage("Kategorie upravena","ok");

		$this->redirect("Writings:manageCategories");

	}



	public function createComponentEditCategoryForm()
	{
		$database = $this->context->database;
		$category = $database->table("WritingCategories")->where("Id", $this->getParameter("categoryId"))->fetch();
		if ($category === false)
		{
			throw new BadRequestException("Zvolená kategorie neexistuje");
		}

		$form = new UI\Form;

		$form->addText("Name", "Název")->setValue($category["Name"]);

		$form->addText("Description", "Popis")->setValue($category["Description"]);

		$form->addCheckbox("IsForAdultsOnly", "Pouze pro dospělé")->setValue($category["IsForAdultsOnly"]);

		$form->addSubmit("SubmitUpdateCategory", "Upravit");
		$form->onSuccess[] = $this->processValidatedEditCategoryForm;

		return $form;
	}



	private function composeCategorySelectList()
	{
		$database = $this->context->database;
		$items = $database->table("WritingCategories");
		$list = array(0 => "~ Nezařazeno ~");
		foreach ($items as $item)
		{
			$name = $item["Name"];
			if ($item["IsForAdultsOnly"])
			{
				$name .= " (18+)";
			}
			$list[$item["Id"]] = $name;
		}
		return $list;
	}



	public function createComponentAddWritingForm()
	{
		$database = $this->context->database;
		$form = new UI\Form;

		$form->addTextArea("Text", "Text:")
			->setAttribute("class", "tinimce WritingText");

		// Artwork title
		$form->addText("Title", "Název * :")
			->setRequired("Je nutné zadat název dila")
			->getControlPrototype()->class = "Wide";

		// Description text
		$form->AddTextArea("Description", "Popis", 2, 5); // Small dimensions to allow CSS scaling

		// Category
		$categorySelect = $form->AddSelect("Category", "Kategorie:", $this->composeCategorySelectList());

		// Flags
		$form->addCheckbox("IsForRegisteredOnly", "Jen pro registrované")->setValue(false);
		$form->addCheckbox("IsForAdultsOnly", "18+")->setValue(false);
		$form->addCheckbox("IsDiscussionAllowed", "Povolit diskuzi")->setValue(true);
		$form->addCheckbox("IsRatingAllowed", "Povolit hodnoceni")->setValue(true);

		// Permissions
		$form->addCheckbox("CanListContent", "Vidí téma")->setValue(true);
		$form->addCheckbox("CanViewContent", "Může téma navštívit")->setValue(true);
		$form->addCheckbox("CanEditContentAndAttributes", "Může měnit název a atributy")->setValue(false);
		$form->addCheckbox("CanEditHeader", "Může měnit hlavičku")->setValue(false);
		$form->addCheckbox("CanEditOwnPosts", "Může upravovat vlastní příspěvky")->setValue(true);
		$form->addCheckbox("CanDeleteOwnPosts", "Může mazat vlastní příspěvky")->setValue(true);
		$form->addCheckbox("CanDeletePosts", "Může mazat jakékoli příspěvky")->setValue(false);
		$form->addCheckbox("CanWritePosts", "Může psát příspěvky")->setValue(true);
		$form->addCheckbox("CanEditPermissions", "Může spravovat oprávnění")->setValue(false);
		$form->addCheckbox("CanEditPolls", "Může spravovat ankety")->setValue(false);

		// Submit
		$form->onValidate[] = $this->validateAddWritingForm;
		$form->onSuccess[] = $this->processValidatedAddWritingForm;
		$form->addSubmit("SubmitWriting", "Uložit");

		return $form;
	}



	public function validateAddWritingForm(UI\Form $form)
	{
		$database = $this->context->database;
		$values = $form->getValues();

		// Check if category exists
		if ($values["Category"] != 0)
		{
			$expoResult = $database->table("WritingCategories")->select("Id", $values["Category"])->count();
			if ($expoResult == 0)
			{
				$form->addError("Zvolená kategorie neexistuje");
			}
		}
	}



	public function processValidatedAddWritingForm(UI\Form $form)
	{
		// Check permissions
		if (! $this->user->isInRole("approved"))
		{
			throw new ForbiddenRequestException("Nejste oprávněn(a) k této operaci");
		}

		$values = $form->getValues();
		$database = $this->context->database;
		$database->beginTransaction();

		/*try
		{*/
			// Create default permission
			$defaultPermission = $database->table("Permissions")->insert(array(
				"CanListContent"     => $values["CanListContent"],
				"CanViewContent"     => $values["CanViewContent"],
				"CanEditHeader"      => $values["CanEditHeader"],
				"CanEditOwnPosts"    => $values["CanEditOwnPosts"],
				"CanDeleteOwnPosts"  => $values["CanDeleteOwnPosts"],
				"CanDeletePosts"     => $values["CanDeletePosts"],
				"CanWritePosts"      => $values["CanWritePosts"],
				"CanEditPermissions" => $values["CanEditPermissions"],
				"CanEditPolls"       => $values["CanEditPolls"],
				"CanEditContentAndAttributes" => $values["CanEditContentAndAttributes"],
			));

			// Create content
			$accessApprovedOnly = $values['IsForRegisteredOnly'];
			$accessAdultsOnly = $values['IsForAdultsOnly'];
			if ($values["Category"] != 0)
			{
				$category = $database->table("WritingCategories")->where("Id", $values["Category"])->fetch();
				if ($category->IsForAdultsOnly)
				{
					$accessApprovedOnly = true;
					$accessAdultsOnly = true;
				}
			}
			$now = new DateTime;
			$content = $database->table("Content")->insert(array(
				"Type"                => "Writing",
				"TimeCreated"         => $now,
				"LastModifiedTime"    => $now,
				"IsForRegisteredOnly" => $accessApprovedOnly,
				"IsForAdultsOnly"     => $accessAdultsOnly,
				"IsDiscussionAllowed" => $values["IsDiscussionAllowed"],
				"IsRatingAllowed"     => $values["IsRatingAllowed"],
				"DefaultPermissions"  => $defaultPermission["Id"],
			));

			// Create permission for owner
			$database->table("Ownership")->insert(array(
				"ContentId" => $content["Id"],
				"UserId"    => $this->user->id,
			));

			// Create writng entry
			$categoryId = ($values["Category"] == 0) ? null: $values["Category"];
			$database->table("Writings")->insert(array(
				"ContentId"      => $content["Id"],
				"Name"           => $values["Title"],
				"Description"    => $values["Description"],
				"Text"           => $values["Text"],
				"CategoryId"     => $categoryId,
			));

			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

		$this->flashMessage("Text byl vložen", "ok");
		$this->redirect("Writings:user");
	}



	public function createComponentDeleteCategoryForm()
	{
		$form = new UI\Form();

		$form->addSubmit("SubmitDeleteCategory", "Smazat");
		$form->onSuccess[] = $this->processValidatedDeleteCategoryForm;
		
		return $form;
	}



	public function processValidatedDeleteCategoryForm(UI\Form $form)
	{
		// Check permissions
		if (! $this->user->isInRole('admin'))
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) k této operaci');
		}

		$values = $form->getValues();
		$database = $this->context->database;
		$id = $this->getParameter("categoryId");

		$category = $database->table("WritingCategories")->where("Id", $id)->fetch();
		if ($category === false)
		{
			throw new BadRequestException("Tato kategorie neexistuje");
		}
		$category->delete();
		$this->flashMessage("Kategorie byla odstraněna", "ok");
		$this->redirect("Writings:manageCategories");
	}



	public function createComponentEditWritingForm()
	{
		// Get data
		$database = $this->context->database;
		$writing = $database->table("Writings")->where("Id", $this->getParameter("writingId"))->fetch();
		if ($writing === false)
		{
			throw new BadRequestException("Zadaný objekt neexistuje");
		}

		// Create form
		$form = new UI\Form;

		$form->addTextArea("Text", "Text:")
			->setValue($writing["Text"])
			->setAttribute("class", "tinimce WritingText");

		// Artwork title
		$form->addText("Title", "Název * :")
			->setValue($writing["Name"])
			->setRequired("Je nutné zadat název dila")
			->getControlPrototype()->class = "Wide";

		// Description text
		$form->AddTextArea("Description", "Popis", 2, 5) // Small dimensions to allow CSS scaling
			->setValue($writing["Description"]);

		// Category
		$categorySelect = $form->AddSelect("Category", "Kategorie:", $this->composeCategorySelectList())
			->setValue($writing["CategoryId"]);

		// Flags
		$content = $writing->ref("ContentId");
		$form->addCheckbox("IsForRegisteredOnly", "Jen pro registrované")
			->setValue($content["IsForRegisteredOnly"]);
		$form->addCheckbox("IsForAdultsOnly", "18+")
			->setValue($content["IsForAdultsOnly"]);
		$form->addCheckbox("IsDiscussionAllowed", "Povolit diskuzi")
			->setValue($content["IsDiscussionAllowed"]);
		$form->addCheckbox("IsRatingAllowed", "Povolit hodnoceni")
			->setValue($content["IsRatingAllowed"]);

		$form->onSuccess[]  = $this->processValidatedEditWritingForm;
		$form->addSubmit("SubmitUpdatedWriting", "Uložit změny");

		return $form;
	}



	public function validateEditWritingForm(UI\Form $form)
	{
		$database = $this->context->database;
		$values = $form->getValues();

		// Check if category exists
		if ($values["Category"] != 0)
		{
			$expoResult = $database->table("WritingCategories")->select("Id", $values["Category"])->count();
			if ($expoResult === false)
			{
				$form->addError("Zadaná kategorie neexistuje");
			}
		}
	}



	/**
	* Fetches item from DB and checkes permissions.
	* @return array $writing, $content, $access
	* @throws BadRequestException If the item isn't found.
	*/
	private function checkWritingAccess($writingId, $user)
	{
		$database = $this->context->database;
		// Fetch
		$item = $database->table("Writings")->where("Id", $writingId)->fetch();
		if ($item === false)
		{
			throw new BadRequestException("Text nenalezen");
		}

		$content = $item->ref("Content");
		if ($content === false)
		{
			throw new ApplicationException("Database/Writing (Id: {$itemId}) has no asociated Database/Content");
		}

		$access = $this->getAuthorizator()->authorize($content, $user);
		return array($item, $content, $access);
	}



	public function processValidatedEditWritingForm(UI\Form $form)
	{
		// Access control
		list($writing, $content, $access) = $this->checkWritingAccess($this->getParameter("writingId"), $this->user);
		if (! $access["CanEditContentAndAttributes"])
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) manipulovat s touto položkou');
		}

		// Update database
		$database = $this->context->database;
		$database->beginTransaction();
		/*try
		{*/
			$values = $form->getValues();

			$accessApprovedOnly = $values['IsForRegisteredOnly'];
			$accessAdultsOnly = $values['IsForAdultsOnly'];
			if ($values["Category"] != 0)
			{
				$category = $database->table("WritingCategories")->where("Id", $values["Category"])->fetch();
				if ($category["IsForAdultsOnly"] == true)
				{
					$accessApprovedOnly = true;
					$accessAdultsOnly = true;
				}
			}

			$content->update(array(
				'IsForRegisteredOnly' => $accessApprovedOnly,
				'IsForAdultsOnly' => $accessAdultsOnly,
				"IsDiscussionAllowed" => $values["IsDiscussionAllowed"],
				"IsRatingAllowed" => $values["IsRatingAllowed"],
				"LastModifiedTime" => new DateTime(),
				"LastModifiedByUser" => $this->user->id
			));

			// Update writing entry
			$writing->update(array(
				'Name' => $values['Title'],
				"Description" => $values["Description"],
				"CategoryId" => ($values["Category"] == 0) ? null : $values["Category"]
			));

			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

		$this->flashMessage('Text byl upraven', 'ok');
		$this->redirect('Writings:user');
	}



	public function createComponentDeleteWritingForm()
	{
		// Get data
		$database = $this->context->database;
		$writingCount = $database->table("Writings")->where("Id", $this->getParameter("writingId"))->count();
		if ($writingCount === 0)
		{
			throw new BadRequestException("Zadaný text neexistuje");
		}

		// Create form
		$form = new UI\Form;

		// Submit
		$form->onSuccess[]  = $this->processValidatedDeleteWritingForm;
		$form->addSubmit('SubmitDeleteWriting', 'Smazat');

		return $form;
	}



	public function processValidatedDeleteWritingForm(UI\Form $form)
	{
		// Access control
		list($writing, $content, $access) = $this->checkWritingAccess($this->getParameter("writingId"), $this->user);
		if (! $access["CanDeleteContent"])
		{
			throw new ForbiddenRequestException('Nejste oprávněn(a) manipulovat s touto položkou');
		}
		$database = $this->context->database;
		$database->beginTransaction();
		//try
		//{
			$writing->delete();
			$content = $writing->ref("Content");
			$content->related("Ownership")->delete();
			$access = $content->related("Access");
			foreach ($access as $item)
			{
				$item->related("Permissions")->delete();
			}
			$access->delete();
			$content->related("LastVisits")->delete();
			$content->delete();

			$database->commit();
			$this->flashMessage("Text byl smazán", "ok");
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
			$this->flashMessage("Obrázek se nepodařilo smazat", "error");
		}*/

		$this->redirect('Writings:user');
	}



	public function createComponentDiscussion()
	{
		$database = $this->context->database;
		$id = $this->getParameter("writingId");
		$item = $database->table("Writings")->where("Id", $id)->fetch();
		if ($item === false)
		{
			throw new BadRequestException("Text neexistuje");
		}
		$content = $item->ref("Content");
		$access = $this->getAuthorizator()->authorize($content, $this->user);
		$baseUrl = $this->presenter->getHttpRequest()->url->baseUrl;

		return new Fcz\Discussion($this, $content, $id, $baseUrl, $access, $this->getParameter("page"), null);
	}



	/** Nette signal handler.
	*/
	public function handleMarkAllRead()
	{
		$database = $this->context->database;
		$this->getContentManager()->bulkUpdateLastVisit("Writing", $this->user->id);
		//$this->redirect($this->backlink());
	}
}
