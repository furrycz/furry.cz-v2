<?php

use Nette\Application\UI;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends UI\Presenter
{

	private $uploadHandler = null;

	private $contentManager = null;

	private $authorizator = null;



	protected function beforeRender()
	{
		$this->template->robots = 'index,follow';
		$this->template->title = $this->context->parameters['title'];
	}



	/**
	* Create and fetch FileUploadHandler
	* @return Fcz\FileUploadHandler
	*/
	public function getUploadHandler()
	{
		if ($this->uploadHandler == null)
		{
			$this->uploadHandler = new Fcz\FileUploadHandler($this);
		}
		return $this->uploadHandler;
	}



	/**
	* Create and fetch ContentManager
	* @return Fcz\ContentManager
	*/
	public function getContentManager()
	{
		if ($this->contentManager == null)
		{
			$this->contentManager = new Fcz\ContentManager($this);
		}
		return $this->contentManager;
	}



	/**
	* Create and fetch ContentManager
	* @return Fcz\ContentManager
	*/
	public function getAuthorizator()
	{
		if ($this->authorizator == null)
		{
			$this->authorizator = new Authorizator($this->context->database);
		}
		return $this->authorizator;
	}



	/**
	* Creates login form, which is part of the basic layout and thus part of any page.
	*/
	protected function createComponentLoginForm()
	{
		$form = new UI\Form();
		$form->addText('Username', 'Uživatelské jméno:')
			->setRequired('Musíte zadat uživatelské jméno');
		$form->addPassword('Password', 'Heslo:')
			->setRequired('Musíte zadat heslo');
		$form->addCheckbox('Permanent', 'Trvale');
		$form->addSubmit('Login', 'Přihlásit');
		$form->onSuccess[] = callback($this, 'processValidatedLoginForm');
		return $form;
	}



	public function processValidatedLoginForm($form)
	{
		$database = $this->context->database;
		$values = $form->getValues();
		$user = $this->getUser();
		try {
			$user->login($values['Username'], $values['Password']);
			$user->setExpiration($values['Permanent'] ? '+ 7 days' : '+ 30 minutes', false);
			$content = $database->table('Users')->where('Username', $values['Username'])->update(array(
				'LastLogin' => date("Y-m-d H:i:s",time())
			));
		} catch (\Nette\Security\AuthenticationException $ex) {
			$this->flashMessage($ex->getMessage(), 'error');
		}
		$this->redirect('Homepage:default');
	}



	public function handleLogout()
	{
		$this->getUser()->logout(true);
		$this->flashMessage('Byl(a) jste odhlášen(a).');
		$this->redirect('Homepage:default');
	}

}
