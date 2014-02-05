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
			'topics' => $topics
		));
	}



	/**
	* Action: Cretates new topic
	*/
	public function renderNewTopic()
	{

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
		$form->addCheckbox('CanDeletePosts', 'Může mazat jakékoli příspěvky')->setValue(false);
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
