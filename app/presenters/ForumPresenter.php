<?php

use Nette\Utils\Html;
use Nette\Diagnostics\Debugger;
use Nette\Database;
use Nette\Application;
use Nette\Application\UI;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;

/**
 * Discussion forum presenter
 */
class ForumPresenter extends BasePresenter
{

	/**
	 * Action: Shows a list of forum topics
	 */
	public function renderDefault()
	{
		$database = $this->context->database;
		
		$users = $database->table('Users');
		foreach($users as $user)
		{
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"]);
		}
		
		$categories = $database->table('TopicCategories')->select('Id, Name');
		$topics = $database->table('Topics');
		$topicsAll = null;
		$i=0;
		foreach($topics as $topic)
		{
			$postCount[$topic["Id"]]["Count"] = $i; // FIXME: this is definitely wrong, fix as soon as possible.
			$i++;
		}
		
		$this->template->setParameters(array(
			'categories' => $categories,
			'topics' => $topics,
			'allUserWithInfo' => $allUserWithInfo
		));
	}



	/**
	* Action: Cretates new topic
	*/
	public function renderNewTopic()
	{
		if (! $this->user->isInRole("approved"))
		{
			throw new BadRequestException("Nemáte oprávnění");
		}
	}



	public function renderEdit($topicId)
	{
		$database = $this->context->database;
		list($topic, $content, $access) = $this->checkTopicAccess($topicId, $this->user);

		if ($access['IsOwner'] == true or $access['CanEditContentAndAttributes'] == true )
		{
			$this->template->Name    = $topic["Name"];
			$this->template->topicId = $topicId;
			$this->template->Owner   = $access['IsOwner'];
			if($topic["CategoryId"] == NULL)
			{
				$topic["CategoryId"]=0;
			}

			$this['editTopicForm']->setDefaults(array(
				"Name"                => $topic["Name"],
				"IsForRegisteredOnly" => $content["IsForRegisteredOnly"],
				"IsForAdultsOnly"     => $content["IsForAdultsOnly"],
				"IsFlame"             => $topic["IsFlame"],
				"Category"            => $topic["CategoryId"],
				"TopicId"             => $topicId,
				"ContentId"           => $topic["ContentId"]
			));

			$this['ownerTopicForm']->setDefaults(array(
				"TopicId"   => $topicId,
				"ContentId" => $content["Id"]
			));
		}
		else
		{
			throw new Nette\Application\BadRequestException('Nejsi vlastník nebo pověřený správce topicu!');
		}
	}



	public function createComponentDeleteTopicForm()
	{
		$form = new UI\Form;
		$form->addHidden('TopicId');
		$form->addHidden('ContentId');
		$form->onSuccess[] = $this->processValidatedNewTopicForm;
		$form->addSubmit('SubmitDeleteTopic', 'Smazat toto téma');
		return $form;
	}



	public function createComponentOwnerTopicForm()
	{
		$form = new UI\Form;
		$form->addHidden('OwnerId');
		$form->addHidden('TopicId');
		$form->addHidden('ContentId');
		$form->onSuccess[] = $this->processValidatedOwnerTopicForm;
		$form->addSubmit('SubmitOwnerTopic', 'Předat práva');
		return $form;
	}



	public function processValidatedOwnerTopicForm($form)
	{
		$values = $form->getValues();
		$database = $this->context->database;
		
		list($topic, $content, $access) = $this->checkTopicAccess($topicId, $this->user);

		if ($access['IsOwner'] == true )
		{
			$database->table('Ownership')->where('ContentId', $values["ContentId"])->update(array(
				"UserId" => $values["OwnerId"]
			));
			$this->redirect('Forum:topic', $values["TopicId"]);
		}
		else
		{
			throw new Nette\Application\BadRequestException('Nejsi vlastník topicu!');
		}
	}



	public function createComponentEditTopicForm()
	{
		// Check access
		if (!($this->user->isInRole('member') || $this->user->isInRole('admin')))
		{
			throw new Nette\Application\ForbiddenRequestException(
				'Pouze registrovaní uživatelé mohou editovat diskusní témata');
		}

		$form = new UI\Form;

		// Topic name
		$form->addText('Name', 'Název * :')
			->setRequired('Je nutné zadat název tématu')
			->getControlPrototype()->class = 'Wide';

		// Flags
		$form->addCheckbox('IsForRegisteredOnly', 'Jen pro registrované')->setValue(false);
		$form->addCheckbox('IsForAdultsOnly', '18+')->setValue(false);
		$form->addCheckbox('IsFlame', 'Flamewar')->setValue(false);

		// Category
		$categories = $this->context->database->table('TopicCategories');
		$radioList = array('0' => 'Žádná');
		foreach ($categories as $category)
		{
			$p = Html::el('p');
			$p->class = 'ForumCategoryRadioItem';
			$p->add(Html::el('strong')->text($category['Name']));
			$p->add($category['Description']);
			$radioList[$category['Id']] = $p;
		}
		$form->addRadioList('Category', 'Sekce:', $radioList)->setValue(0);
		// Submit
		$form->addHidden('TopicId');
		$form->addHidden('ContentId');
		$form->onSuccess[] = $this->processValidatedEditTopicForm;
		$form->addSubmit('SubmitNewTopic', 'Upravit');

		return $form;
	}


	public function renderPermision($topicId)
	{
		list($topic, $content, $access) = $this->checkTopicAccess($topicId, $this->user);
		if (! $access["CanEditPermissions"])
		{
			throw new BadRequestException("Nemáte oprávnění upravovat přístupová práva");
		}

		$this->template->setParameters(array(
			"Name" => $topic["Name"],
			"topicId" => $topicId,
		));
	}



	public function processValidatedEditTopicForm($form)
	{
		$values = $form->getValues();
		$database = $this->context->database;

		list($topic, $content, $access) = $this->checkTopicAccess($topicId, $this->user);

		if ($access['IsOwner'] == true or $access['CanEditContentAndAttributes'] == true )
		{
			$topic->update(array(
				'Name'       => $values['Name'],
				'IsFlame'    => $values['IsFlame'],
				'CategoryId' => $values['Category'] == 0 ? null : $values['Category']
			));

			$topic->ref('ContentId')->update(array(
				'IsForRegisteredOnly' => $values['IsForRegisteredOnly'],
				'IsForAdultsOnly'     => $values['IsForAdultsOnly']
			));

			$this->redirect('Forum:topic', $values["TopicId"]);
		}
		else
		{
			throw new Nette\Application\BadRequestException('Nejsi vlastník nebo pověřený správce topicu!');
		}
	}



	public function renderHeader($topicId)
	{
		$database = $this->context->database;
		list($topic, $content, $access) = $this->checkTopicAccess($topicId, $this->user);
		if ($access['IsOwner'] == true or $access['CanEditHeader'] == true )
		{
			$this->template->Name = $topic["Name"];
			$this->template->topicId = $topicId;
			$this['headerEdit']->setDefaults(array(
				"Header"                   => $topic->ref('CmsPages', 'Header')["Text"],
				"HeaderForDisallowedUsers" => $topic->ref('CmsPages', 'HeaderForDisallowedUsers')["Text"],
				"TopicId"                  => $topicId,
				"ContentId"                => $topic["ContentId"]
			));
		}
		else
		{
			throw new Nette\Application\BadRequestException('Nejsi vlastník nebo pověřený správce topicu!');
		}
	}



	public function createComponentHeaderEdit()
	{
		$form = new UI\Form;

		// Headers
		$form->addTextArea('Header', 'Hlavička:', 2, 18)
			->setAttribute('placeholder', 'Tvůj příspěvek ...')
			->setAttribute('class', 'tinimce')
			->setAttribute('style', 'height:440px;');
		$form->addTextArea('HeaderForDisallowedUsers', 'Hlavička pro nepovolený přístup:', 2, 10)
			->setAttribute('placeholder', 'Tvůj příspěvek ...')
			->setAttribute('class', 'tinimce')
			->setAttribute('style', 'height:200px;');
		$form->addHidden('TopicId');
		$form->addHidden('ContentId');
		// Submit
		$form->onSuccess[] = $this->processValidatedHeaderEdit;
		$form->addSubmit('SubmitNewTopic', 'Upravit');

		return $form;
	}



	public function processValidatedHeaderEdit($form)
	{
		$values = $form->getValues();

		list($topic, $content, $access) = $this->checkTopicAccess($topicId, $this->user);

		if ($access['IsOwner'] == true or $access['CanEditHeader'] == true )
		{
			$cmsPage = $topic["Header"];
			$cmsPage->update(array('Text' => $values['Header']));
			$cmsPage->update(array('Text' => $values['HeaderForDisallowedUsers']));

			$this->redirect('Forum:topic', $topic["Id"]);
		}
		else
		{
			throw new BadRequestException('Nejsi vlastník nebo pověřený správce topicu!');
		}
	}



	public function createComponentDiscussion()
	{
		$topicId = $this->getParameter('topicId');
		list($topic, $content, $access) = $this->checkTopicAccess($topicId, $this->user);
		$baseUrl = $this->presenter->getHttpRequest()->url->baseUrl;

		return new Fcz\Discussion($this, $content, $topicId, $baseUrl, $access, $this->getParameter('page'), null);
	}



	public function createComponentPermissions()
	{
		$database = $this->context->database;

		$topic = $database->table("Topics")->select("Id, ContentId")->where("Id", $this->getParameter("topicId"))->fetch();
		if ($topic === false)
		{
			throw new BadRequestException("Toto diskusní téma neexistuje", 404);
		}
		$data = array(
			"Permisions" => array(  //Permision data
				"CanListContent" => array("L","Může topic vidět v seznamu","","CanViewContent","",1), //$Zkratka 1 písmeno(""==Nezobrazí), $Popis, $BarvaPozadí, $Parent(""!=Nezobrazí), $Zařazení práv, $default check
				"CanReadPosts" => array("R","Může topic číst","","","",1),
				"CanViewContent" => array("","","","CanReadPosts","Context",1),
				"CanEditContentAndAttributes" => array("E","Může topic upravit","D80093","","Context - Správce",0),
				"CanEditHeader" => array("H","Může upravit hlavičku","D80093","","Context - Správce",0),
				"CanEditPermissions" => array("S","Může upravit práva","D80093","","Context - Správce - NEBEZEPEČNÉ",0),
				"CanDeleteOwnPosts" => array("","","","CanEditOwnPosts","",1),				
				"CanWritePosts" => array("P","Může psát příspěvky","61ADFF","","Context",1),
				"CanDeletePosts" => array("D","Může mazat a editovat všechny příspěvky","007AFF","","Moderátor",0),
				"CanEditPolls" => array("EP","Muže upravit ankety","007AFF","","Moderátor",0),
				"CanEditOwnPosts" => array("F","'Frozen', pokud nebude zaškrtnuto, uživatel nebude moci editovat a mazat vlastní příspěvky.","F00","","",1)
				),
			"Description" => "!", // "!" means NULL here
			"Visiblity" => array(
				"Public" => "Vidí všichni",
				"Private" => "Nevidí nikdo je třeba přidelit práva",
				"Hidden" => "Nezobrazí se v seznamu všech topiků, je třeba přidelit práva"
				),
			"DefaultShow" => true
		);
		return new Fcz\Permissions($this, $content = $topic->ref("ContentId"), $this->getAuthorizator(), $data);
	}



	public function createComponentNewTopicForm()
	{
		$form = new UI\Form;

		// Topic name
		$form->addText('Name', 'Název * :')
			->setRequired('Je nutné zadat název tématu')
			->getControlPrototype()->class = 'Wide';

		// Flags
		$form->addCheckbox('IsForRegisteredOnly', 'Jen pro registrované')->setValue(false);
		$form->addCheckbox('IsForAdultsOnly', '18+')->setValue(false);
		$form->addCheckbox('IsFlame', 'Flamewar')->setValue(false);

		// Category
		$categories = $this->context->database->table('TopicCategories');
		$radioList = array('0' => 'Žádná');
		foreach ($categories as $category)
		{
			$p = Html::el('p');
			$p->class = 'ForumCategoryRadioItem';
			$p->add(Html::el('strong')->text($category['Name']));
			$p->add($category['Description']);
			$radioList[$category['Id']] = $p;
		}
		$form->addRadioList('Category', 'Sekce:', $radioList)->setValue(0);

		// Permissions
		$form->addCheckbox('CanListContent', 'Vidí téma')->setValue(true);
		$form->addCheckbox('CanViewContent', 'Může téma navštívit')->setValue(true);
		$form->addCheckbox('CanEditContentAndAttributes', 'Může měnit název a atributy')->setValue(false);
		$form->addCheckbox('CanEditHeader', 'Může měnit hlavičku')->setValue(false);
		$form->addCheckbox('CanEditOwnPosts', 'Může upravovat vlastní příspěvky')->setValue(true);
		$form->addCheckbox('CanDeleteOwnPosts', 'Může mazat vlastní příspěvky')->setValue(true);
		$form->addCheckbox('CanDeletePosts', 'Může mazat a upravovat jakékoli příspěvky')->setValue(false);
		$form->addCheckbox('CanWritePosts', 'Může psát příspěvky')->setValue(true);
		$form->addCheckbox('CanEditPermissions', 'Může spravovat oprávnění')->setValue(false);
		$form->addCheckbox('CanEditPolls', 'Může spravovat ankety')->setValue(false);

		// Headers
		$form->addTextArea('Header', 'Hlavička:', 2, 10); // Small rows/cols values to allow css scaling
		$form->addTextArea('HeaderForDisallowedUsers', 'Hlavička pro nepovolený přístup:', 2, 10);

		// Submit
		$form->onSuccess[] = $this->processValidatedNewTopicForm;
		$form->addSubmit('SubmitNewTopic', 'Vytvořit');

		return $form;
	}



	public function processValidatedNewTopicForm($form)
	{
		if (! $this->user->isInRole("approved"))
		{
			throw new BadRequestException("Pouze schválení uživatelé mohou zakládat nová diskusní témata");
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
				'CanEditPolls' => $values['CanEditPolls'],
				'CanReadPosts' => $values['CanViewContent']
			));

			// Create content
			$content = $database->table('Content')->insert(array(
				'Type' => 'Topic',
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

			// Create header CMS
			$headerCmsId = null;
			if ($values['Header'] != '')
			{
				$headerCms = $database->table('CmsPages')->insert(array(
					'Name' => 'Topic header (ContentId: ' . $content['Id'] . ')',
					'Text' => Fcz\SecurityUtilities::processCmsHtml($values['Header'])
				));
				$headerCmsId = $headerCms['Id'];
			}

			// Create header CMS for disallowed
			$altHeaderCmsId = null;
			if ($values['HeaderForDisallowedUsers'] != '')
			{
				$headerCms = $database->table('CmsPages')->insert(array(
					'Name' => 'Topic alt. header (ContentId: ' . $content['Id'] . ')',
					'Text' => Fcz\SecurityUtilities::processCmsHtml($values['HeaderForDisallowedUsers'])
				));
				$altHeaderCmsId = $headerCms['Id'];
			}

			// Create topic
			$database->table('Topics')->insert(array(
				'ContentId' => $content['Id'],
				'CategoryId' => $values['Category'] == 0 ? null : $values['Category'],
				'Header' => $headerCmsId,
				'HeaderForDisallowedUsers' => $altHeaderCmsId,
				'IsFlame' => $values['IsFlame'],
				'Name' => $values['Name']
			));

			$database->commit();
		/*}
		catch(Exception $exception)
		{
			$database->rollBack();
			Nette\Diagnostics\Debugger::log($exception);
		}*/

		$this->flashMessage('Diskusní téma bylo vytvořeno', 'ok');
		$this->redirect('Forum:default');
	}



	/**
	* @param int $topicId Topic ID
	* @param int $page Page number
	* @param int $findPost ID of topic to find find and highlight.
	*/
	public function renderTopic($topicId, $page, $findPost)
	{
		list($topic, $content, $access) = $this->checkTopicAccess($topicId, $this->user);

		// Setup template
		$this->template->setParameters(array(
			'topic' => $topic,
			'content' => $content,
			'access' => $access
		));
	}



	public function renderDelete($topicId, $postId)
	{
		$database = $this->context->database;
		// TODO: Add permission check!
	}



	public function renderInfo($topicId)
	{
		$database = $this->context->database;

		$users = $database->table('Users');
		foreach($users as $user)
		{
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"], $user["Username"]);
		}

		$topic = $database->table('Topics')->where('Id', $topicId)->fetch();
		$conte = $topic->ref('Content');

		$this->template->Name = $topic["Name"];

		$this->template->topicId = $topicId;
		$this->template->create = $conte["TimeCreated"];
		if ($topic['CategoryId'] != null)
		{
			$this->template->sekce = $topic->ref('TopicCategories')->select('Name');
		}
		else
		{
			$this->template->sekce = array('Name' => 'Nezařazeno', 'Id' => 0);
		}
		$owner = $database->table('Ownership')->where('ContentId', $conte["Id"])->fetch();
		$this->template->owner = array(
			$owner["UserId"],
			$allUserWithInfo[$owner["UserId"]][0],
			$allUserWithInfo[$owner["UserId"]][2]
		);
		$this->template->posts = count($database->table('Posts')->where("ContentId",$conte["Id"])->where("Deleted",0));
	}



	/**
	* Fetches item from DB and checkes permissions.
	* @return array $topic, $content, $access
	* @throws BadRequestException If the topic isn't found.
	*/
	private function checkTopicAccess($topicId, $user)
	{
		$database = $this->context->database;
		// Fetch item
		$topic = $database->table("Topics")->where("Id", $topicId)->fetch();
		if ($topic === false)
		{
			throw new BadRequestException("Diskusní téma nenalezeno");
		}

		$content = $topic->ref("Content");
		if ($content === false)
		{
			throw new ApplicationException("Database/Image (Id: {$topicId}) has no asociated Database/Content");
		}

		$access = $this->getAuthorizator()->authorize($content, $user);
		return array($topic, $content, $access);
	}
}
