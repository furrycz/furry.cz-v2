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
		$idp = $this->presenter->getParameter('topicId');		
		
		// Load posts to display
		if ($this->presenter->user->identity->postsOrdering == "NewestOnTop")
		{
			$posts = $database
				->table('Posts')
				->where("Id", $database
					->table('Posts')
					->select('Id')
					->where("ContentId", $this->content['Id'])
					->where("Deleted",0)
					->order('Id ASC')
					->limit($this->paginator->getLength(), $this->paginator->getCountdownOffset()))
				->order('Id DESC');
		}
		else
		{
			$posts = $database
				->table('Posts')
				->where("ContentId", $this->content['Id'])
				->where("Deleted",0)
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
			$allUserId[$user["Id"]] = $user["Username"];
			$allUserName[$user["Username"]] = $user["Id"];
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"]);
		}
		
		$ratingsData = null;
		foreach($posts as $post){ $ratingsData[$post["Id"]] = array(0,""); }
		$ratings = $database->table('RatingsPost')->Where("ContentId",$this->content['Id']);
		foreach($ratings as $rating){
			if(isset($ratingsData[$rating["PostId"]])){
				$ratingsData[$rating["PostId"]][0] += $rating["Rating"];
			}else{
				$ratingsData[$rating["PostId"]] = array($rating["Rating"],"");
				$ratingsData[$rating["PostId"]][2] = "";
				$ratingsData[$rating["PostId"]][3] = "";
			}
			if($ratingsData[$rating["PostId"]][0]<0){$c="Red";}elseif($ratingsData[$rating["PostId"]][0]>0){$c="Green";}else{$c="Orange";}
			$ratingsData[$rating["PostId"]][1] = $c;
			if($rating["Rating"]!=0){
				$ratingsData[$rating["PostId"]][2] = $allUserWithInfo[$rating["UserId"]][0]." [".$rating["Rating"]."], ";
				$ratingsData[$rating["PostId"]][3] = $allUserId[$rating["UserId"]].",".$allUserWithInfo[$rating["UserId"]][0].",".$rating["Rating"].",".$allUserWithInfo[$rating["UserId"]][1]."!";
			}
		}

		// Setup template
		$template = $this->presenter->template;
		$template->setFile(__DIR__ . '/../templates/components/discussionPosts.latte');
		$template->setParameters(array(
			'posts' => $posts,			
			'users' => $userData,
			'access' => $this->access,
			'ratings' => $ratingsData,
			'presenter' => $this->presenter,
			'contentId' => $this->content['Id']
		));
		
		$this['newPostForm']->setDefaults(array("DiscussionID"=>$idp));
		
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
