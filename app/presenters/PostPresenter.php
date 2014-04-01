<?php

use Nette\Application;
use Nette\Application\UI;
use Nette\Database;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;

class PostPresenter extends BasePresenter
{

	public function actionDefault(){
		$this->redirect("Forum:default");
	}



	public function renderEdit($postId)
	{
		$this->checkEditPostPermissions($postId, $this->user);
	}
	
	public function createComponentEditPostForm()
	{
		$form = new \Nette\Application\UI\Form;

		$form->addTextArea('text', 'Text', 1, 5) // Small rows/cols to allow css scaling
			// NOTE: Nette form rules "REQUIRED", "PATTERN" don't work with TinyMCE
			->setAttribute('placeholder', 'Tvůj příspěvek ...')
			->setAttribute('class', 'tinimce')
			->setAttribute('style', 'height:300px;');

		$form->addSubmit('save', 'Upravit')->setAttribute("style","padding:5px;");

		$form->onValidate[] = $this->validateEditPostForm;
		$form->onSuccess[] = $this->handleValidatedEditPostForm;

		return $form;
	}



	public function validateEditPostForm(UI\Form $form)
	{
		$values = $form->getValues();
		if (trim($values["text"]) == "")
		{
			$form->addError("Nelze uložit prázdný příspěvek");
		}
	}



	public function handleValidatedEditPostForm(UI\Form $form)
	{
		$postId = $this->getParameter('postId');
		list($post, $access) = $this->checkEditPostPermissions($postId, $this->user);

		$values = $form->getValues();
		$database = $this->presenter->context->database;

		$pel = explode("#",$post["Edited"]);
		$result = $post->update(array(
			"Text" => $values["text"],
			"Edited" => time()."#".$this->user->id."#".(@$pel[2]+1)
		));
		if ($result === false)
		{
			throw new ApplicationException("Failed to save post {$postId}");
		}
		$this->flashMessage("Diskusní příspěvek byl upraven.", "ok");

		// Redirect
		$content = $post->ref("Content");
		switch ($content["Type"])
		{
			case "Topic":
				$this->redirect("Forum:topic", $content->related("Topics")->fetch()->Id);
				break;

			case "Image":
				$this->redirect("Gallery:showImage", $content->related("Images")->fetch()->Id);
				break;

			default:
				$this->redirect("Homepage:default");
		}
	}



	public function checkEditPostPermissions($postId, $user)
	{
		$database = $this->context->database;
		$post = $database->table('Posts')->where('Id',$postId)->fetch();
		if ($post === false)
		{
			throw new BadRequestException("Tento příspěvek neexistuje!");
		}

		$content = $post->ref("ContentId");
		$access = $this->getAuthorizator()->authorize($content, $this->user);

		if (($post["Author"]==$user->id && $access["CanEditOwnPosts"]) || $access["CanEditPosts"] )
		{
			$this['editPostForm']->setDefaults(array("text"=>$post["Text"]));
		}
		else
		{
			throw new ForbiddenRequestException("Nemáte oprávnění upravovat tento příspěvek!");
		}

		return array($post, $access);
	}
}
