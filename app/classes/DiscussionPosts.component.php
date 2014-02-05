<?php

namespace Fcz
{

/**
* Displays discussion posts and form.
*/
class DiscussionPosts extends \Nette\Application\UI\Control
{

	private $access = null;

	private $content = null;

	private $paginator = null;

	private $presenter = null;



	public function __construct($presenter, $content, $access, $paginator)
	{
		$this->access = $access;
		$this->content = $content;
		$this->paginator = $paginator;
		$this->presenter = $presenter;
	}



	public function render()
	{
		$database = $this->presenter->context->database;

		// Load posts to display
		if ($this->presenter->user->identity->postsOrdering == "NewestOnTop")
		{
			$posts = $database
				->table('Posts')
				->where("Id", $database
					->table('Posts')
					->select('Id')
					->where("ContentId", $this->content['Id'])
					->order('Id ASC')
					->limit($this->paginator->getLength(), $this->paginator->getCountdownOffset()))
				->order('Id DESC');
		}
		else
		{
			$posts = $database
				->table('Posts')
				->where("ContentId", $this->content['Id'])
				->order('Id ASC')
				->limit($this->paginator->getLength(), $this->paginator->getCountdownOffset());
		}
		
		$userData = null;
		
		$users = $database->table('Users');
		foreach($users as $user){
			$hodnost = "";$image = ""; $color="";
			if($user["IsAdmin"]){$hodnost="Administrátor";$color="Admin";$image="star.png";}
			if($user["IsFrozen"]){$hodnost="Kostka ledu";$color="Frozen";}
			if($user["IsBanned"]){$hodnost="Zavřen v krabici";$color="Banned";$image="banana.png";}
			if($user["Deleted"]){$hodnost="Vymazán";$color="Delete";}			
			$userData[$user["Id"]] = array("Hodnost" => $hodnost, "Color" => $color, "Image" => $image);
		}

		// Setup template
		$template = $this->presenter->template;
		$template->setFile(__DIR__ . '/../templates/components/discussionPosts.latte');
		$template->setParameters(array(
			'posts' => $posts,
			'users' => $userData,
			'access' => $this->access,
			'presenter' => $this->presenter
		));
		
		$this['newPostForm']->setDefaults(array("DiscussionID"=>$this->content['Id']));
		
		$template->render();
	}



	/** Nette sub-component factory function
	*/
	public function createComponentNewPostForm()
	{
		$form = new \Nette\Application\UI\Form;

		$form->addTextArea('text', 'Text', 1, 1) // Small rows/cols to allow css scaling
			//->setRequired('Nelze uložit prázdný příspěvek')
			//->addRule(\Nette\Application\UI\Form::PATTERN, "Nelze uložit prázdný příspěvek", "\S+") // Deny whitespace-only posts
			->setAttribute('placeholder', 'Tvůj příspěvek ...')
			->setAttribute('class', 'tinimce')
			->setAttribute('style', 'height:100px;');

		$form->addSubmit('save', 'Připsat');
		
		$form->addHidden('DiscussionID');

		$form->onSuccess[] = $this->handleValidatedNewPostForm;

		return $form;
	}



	/** Nette form callback
	*/
	public function handleValidatedNewPostForm(\Nette\Application\UI\Form $form)
	{
			$values = $form->getValues();
			$database = $this->presenter->context->database;
			
			if(trim($values["text"])==""){
				$this->presenter->flashMessage('Prosím zadej text!', 'ok');
			}else{
			
			$database->table('Posts')->insert(array(
				'ContentId' => $values['DiscussionID'],
				"Author" => $this->presenter->user->id,
				"Text" => $values["text"],
				"TimeCreated" => date("Y-m-d H:i:s",time())
			));
			
			}
			
			$this->presenter->redirect("Forum:topic",$values['DiscussionID']);
	}

}

} // namespace Fcz
