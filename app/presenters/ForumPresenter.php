<?php

use Nette\Application\UI;
use Nette\Utils\Html;
use Nette\Diagnostics\Debugger;

/**
 * Discussion forum presenter
 */
class ForumPresenter extends DiscussionPresenter
{

	/**
	 * Action: Shows a list of forum topics
	 */
	public function renderDefault()
	{
		$database = $this->context->database;

		$categories = $database->table('TopicCategories')->select('Id, Name');
		$topics = $database->table('Topics');
		$this->template->setParameters(array(
			'categories' => $categories,
			'topics' => $topics,
		));
	}



	/**
	* Action: Cretates new topic
	*/
	public function renderNewTopic()
	{

	}
	
	private $content = "";
	
	public function renderPermision($topicId){
		$database = $this->context->database;
		$topic = $database->table('Topics')->where('Id', $topicId)->fetch();
		if ($topic == false)
		{
			throw new Nette\Application\BadRequestException('Zadané téma neexistuje');
		}
		$this->template->Name = $topic["Name"];
		$this->template->topicId = $topicId;
		$this->content = $database->table('Content')->where('Id', $topic["ContentId"])->fetch();
	}

	public function createComponentPermissions()
	{
		$data = array(
							"Permisions" => array(  //Permision data
												"CanListContent" => array("L","Může topic vidět v seznamu","","CanViewContent","",1), //$Zkratka 1 písmeno(""==Nezobrazí), $Popis, $BarvaPozadí, $Parent(""!=Nezobrazí), $Zařazení práv, $default check
												"CanViewContent" => array("","","","CanReadPosts","Context",1),
												"CanEditContentAndAttributes" => array("E","Může topic upravit","D80093","","Context - Správce",0),
												"CanEditHeader" => array("H","Může upravit hlavičku","D80093","","Context - Správce",0),
												"CanEditPermissions" => array("S","Může upravit práva","D80093","","Context - Správce - NEBEZEPEČNÉ",0),												
												"CanDeleteOwnPosts" => array("","","","CanEditOwnPosts","",1),
												"CanReadPosts" => array("R","Může topic číst","","","",1),																								
												"CanWritePosts" => array("P","Může psát příspěvky","61ADFF","","Context",1),												
												"CanDeletePosts" => array("D","Může mazat a editovat všechny příspěvky","007AFF","","Moderátor",0),
												"CanEditPolls" => array("EP","Muže upravit ankety","007AFF","","Moderátor",0),
												"CanEditOwnPosts" => array("F","Frozen... Tímto uživately zakažete editovat a mazat vlastní příspěvky\n(Když nebude zaškrtnuto!)","F00","","",1)
												),
							"Description" => "!",
							"Visiblity" => array(
												"Public" => "Vidí všichni",
												"Private" => "Nevidí nikdo je třeba přidelit práva",
												"Hidden" => "Nezobrazí se v seznamu všech topiků, je třeba přidelit práva"
												),
							"DefaultShow" => true					
							);
		return new Fcz\Permissions($this, $this->content, new Authorizator($this->context->database), $data);
	}


	public function createComponentNewTopicForm()
	{
		// Check access
		if (!($this->user->isInRole('member') || $this->user->isInRole('admin')))
		{
			throw new Nette\Application\ForbiddenRequestException(
				'Pouze registrovaní uživatelé mohou zakládat nová diskusní témata');
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



	public function renderTopic($topicId, $page, $subAction, $findPost)
	{
		$database = $this->context->database;

		// Load topic
		$topic = $database->table('Topics')->where('Id', $topicId)->fetch();
		if ($topic == false)
		{
			throw new Nette\Application\BadRequestException('Zadané diskusní téma neexistuje');
		}
		$content = $topic->ref('Content');

		$authorizator = new Authorizator($database);
		$access = $authorizator->authorize($content, $this->user);
		
		if ($access['CanReadPosts'] == true)
		{			
			$this->setupDiscussion($access, $content, $topic['Id'], $page, $findPost);

			// Setup template
			$this->template->setParameters(array(
				'topic' => $topic
			));
		}
		else
		{
			// Setup template
			$this->template->setParameters(array(
				'topic' => $topic,
				'content' => $content,
				'access' => $access
			));
		}
	}
	
	public function renderDelete($topicId, $postId){
		$database = $this->context->database;
	}
}
