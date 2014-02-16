<?php

use Nette\Application\UI;

class PostPresenter extends BasePresenter
{
	public function renderDefault(){
	
	}
	
	public function renderDelete($postId){
		$database = $this->context->database;
		$post = $database->table('Posts')->where('Id',$postId)->fetch();
		if($post){
			$Content = $post->ref("ContentId");
			if($Content["Type"]=="Topic"){
				if($post["Author"]==$this->user->id or $this->user->isInRole('admin')){
					$post->update(array("Deleted" => 1));
				}else { throw new Nette\Application\BadRequestException("Nemáš oprávnění mazat tento příspěvek!"); }
				$this->redirect("Forum:topic",$Content["Id"]);
			}else{
				throw new Nette\Application\BadRequestException("Tento typ Příspěvku není znám!");
			}
		}else{
			$this->flashMessage('Tento příspěvek neexistuje!', 'error');
			$this->redirect("Homepage:default");
		}
	}
	
	public function renderEdit($postId){
		$database = $this->context->database;
		$post = $database->table('Posts')->where('Id',$postId)->fetch();
		if($post){
			$template = $this->presenter->template;
			$Content = $post->ref("ContentId");
			if($Content["Type"]=="Topic"){
				if($post["Author"]==$this->user->id or $this->user->isInRole('admin')){
					
					$template->text = $post["Text"];
					$template->typ  = "Příspěvek ve foru";
					$this['editPostForm']->setDefaults(array("text"=>$post["Text"]));
					
				}else { throw new Nette\Application\BadRequestException("Nemáš oprávnění upravovat tento příspěvek!");$this->redirect("Forum:topic",$Content["Id"]); }				
			}else{
				throw new Nette\Application\BadRequestException("Tento typ Příspěvku není znám!");
			}
		}else{
			$this->flashMessage('Tento příspěvek neexistuje!', 'error');
			$this->redirect("Homepage:default");
		}
	}
	
	public function createComponentEditPostForm()
	{
		$form = new \Nette\Application\UI\Form;

		$form->addTextArea('text', 'Text', 1, 5) // Small rows/cols to allow css scaling
			//->setRequired('Nelze uložit prázdný příspěvek')
			//->addRule(\Nette\Application\UI\Form::PATTERN, "Nelze uložit prázdný příspěvek", "\S+") // Deny whitespace-only posts
			->setAttribute('placeholder', 'Tvůj příspěvek ...')
			->setAttribute('class', 'tinimce')			
			->setAttribute('style', 'height:300px;');

		$form->addSubmit('save', 'Upravit')->setAttribute("style","padding:5px;");
		
		$form->addHidden('DiscussionID');

		$form->onSuccess[] = $this->handleValidatedEditPostForm;

		return $form;
	}
	
	public function handleValidatedEditPostForm(\Nette\Application\UI\Form $form)
	{
			$values = $form->getValues();
			$database = $this->presenter->context->database;
			
			if(trim($values["text"])==""){
				$this->presenter->flashMessage('Prosím zadej text!', 'ok');
			}else{
			
			$post = $database->table('Posts')->where('Id',$this->getParameter('postId'))->fetch();
			$Content = $post->ref("ContentId");
			
			if($post["Author"]==$this->user->id or $this->user->isInRole('admin')){
				
				$pel = explode("#",$post["Edited"]);
				$database->table('Posts')->where("Id",$this->getParameter('postId'))->update(array(
					"Text" => $values["text"],
					"Edited" => time()."#".$this->user->id."#".(@$pel[2]+1)
				));
				$this->redirect("Forum:topic",$Content["Id"]);
			
			}else{ throw new Nette\Application\BadRequestException("Nemáš oprávnění upravovat tento příspěvek!");$this->redirect("Forum:topic",$Content["Id"]); }
			
			}
	}
}
