<?php

namespace Fcz
{

use Nette\Application;
use Nette\Application\UI;
use Nette\Database;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;

/**
* Displays discussion posts and form.
*/
class DiscussionPosts extends Application\UI\Control
{

	private $access = null;

	private $content = null;

	private $paginator = null;

	private $presenter = null;

	private $parent = null;

	private $subContentId; // Id of topic/event/cms/writing/image



	/**
	* @param Nette\Application\UI\Control    $parent
	* @param Nette\Application\UI\Presenter  $presenter
	* @param Nette\Database\Table\ActiveRow  $content
	* @param int                             $subContentId  Id of topic/event/cms/writing/image
	* @param array                           $access        Access permissions
	* @param Nette\Utils\Paginator           $paginator
	*/
	public function __construct(
			UI\Control $parent,
			UI\Presenter $presenter,
			Database\Table\ActiveRow $content,
			$subContentId,
			$access,
			$paginator
		)
	{
		parent::__construct($parent, "discussionPosts");

		$this->access = $access;
		$this->content = $content;
		$this->paginator = $paginator;
		$this->presenter = $presenter;
		$this->subContentId = $subContentId;
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
			$rank = "";$image = ""; $color="";
			if($user["IsAdmin"]){$rank="Administrátor";$color="Admin";$image="star.png";}
			if($user["IsFrozen"]){$rank="Kostka ledu";$color="Frozen";}
			if($user["IsBanned"]){$rank="Zavřen v krabici";$color="Banned";$image="banana.png";}
			if($user["Deleted"]){$rank="Vymazán";$color="Delete";}
			$userData[$user["Id"]] = array("Hodnost" => $rank, "Color" => $color, "Image" => $image);
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
		$template = $this->template;
		$template->setFile(__DIR__ . '/../templates/components/discussionPosts.latte');
		$template->setParameters(array(
			'posts' => $posts,
			'users' => $userData,
			'access' => $this->access,
			'ratings' => $ratingsData,
			'rootPresenter' => $this->presenter,
			'contentId' => $this->content['Id'],
			'thisComponent' => $this,
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
			// NOTE: Nette form rules "REQUIRED", "PATTERN" don't work with TinyMCE
			->setAttribute('placeholder', 'Tvůj příspěvek ...')
			->setAttribute('class', 'tinimce')
			->setAttribute('style', 'height:100px;');

		$form->addSubmit('save', 'Připsat');

		$form->addHidden('DiscussionID');

		$form->onValidate[] = $this->validateNewPostForm;
		$form->onSuccess[] = $this->handleValidatedNewPostForm;

		return $form;
	}



	public function validateNewPostForm(UI\Form $form)
	{
		$values = $form->getValues();
		if (trim($values["text"]) == "")
		{
			$form->addError("Nelze uložit prázdný příspěvek");
		}
	}



	/** Nette form callback
	*/
	public function handleValidatedNewPostForm(UI\Form $form)
	{
		$values = $form->getValues();
		$database = $this->presenter->context->database;

		$database->table('Posts')->insert(array(
			'ContentId' => $values['DiscussionID'],
			"Author" => $this->presenter->user->id,
			"Text" => $values["text"],
			"TimeCreated" => date("Y-m-d H:i:s",time())
		));

		$this->reloadPage();
	}



	/**
	* Nette signal handler: Delete a discussion post
	*/
	public function handleDelete($postId)
	{
		$database = $this->presenter->context->database;
		$post = $database->table('Posts')->where('Id',$postId)->fetch();
		if($post === false)
		{
			throw new BadRequestException('Tento příspěvek neexistuje!', 404);
		}

		$content = $post->ref("ContentId");
		$access = $this->presenter->getAuthorizator()->authorize($content, $this->presenter->user);
		if ( ($post["Author"] == $this->presenter->user->id && $access["CanDeleteOwnPosts"])
			or ($access["CanDeletePosts"]))
		{
			$post->update(array("Deleted" => 1));
			$this->reloadPage();
		}
		else
		{
			throw new ForbiddenRequestException("Nemáš oprávnění mazat tento příspěvek!");
		}
	}



	/** Template utility function - formats a "deleted/ignored" post block
	* @return array ("css": string, "text": string)
	*/
	public function formatHiddenPosts($numIgnored, $numDeleted)
	{
		if ($numIgnored == 1 and $numDeleted == 0)
		{
			$css = "Ignored";
			$text = "Ignorovaný příspěvek";
		}
		else if ($numIgnored == 0 and $numDeleted == 1)
		{
			$css = "Deleted";
			$text = "Smazaný příspěvek";
		}
		else
		{
			$textDeleted = $numDeleted . " "
				. LanguageUtilities::czechCount($numDeleted, array("smazan", "ý", "é", "ých"));
			$textIgnored = $numIgnored . " "
				. LanguageUtilities::czechCount($numIgnored, array("ignorovan", "ý", "é", "ých"));

			if ($numIgnored > 0 and $numDeleted > 0)
			{
				$text = "$textDeleted, $textIgnored";
				//$textNum = $numIgnored;
				$css = "Ignored Deleted";
			}
			else if ($numDeleted > 0)
			{
				$text = $textDeleted;
				//$textNum = $numDeleted;
				$css = "Deleted";
			}
			else
			{
				$text = $textIgnored;
				//$textNum = $numIgnored;
				$css = "Ignored";
			}
		}

		return array(
			"css" => $css,
			"text" => $text
		);
	}



	public function getAuthorData($authorId)
	{
		$database = $this->context->database;
		$user = $database->table('Users')->where('Id', $authorId)->fetch();
		if($user !== false)
		{
			$rank = "";
			$image = "";
			$color="";

			if($user["IsAdmin"]){$rank="Administrátor";$color="Admin";$image="star.png";}
			if($user["IsFrozen"]){$rank="Kostka ledu";$color="Frozen";}
			if($user["IsBanned"]){$rank="Zavřen v krabici";$color="Banned";$image="banana.png";}
			if($user["Deleted"]){$rank="Vymazán";$color="Delete";}

			return array("Hodnost" => $rank, "Color" => $color, "Image" => $image);
		}
	}



	public function reloadPage()
	{
		$route = "{$this->presenter->name}:{$this->presenter->action}";
		$this->presenter->redirect($route, $this->subContentId);
	}

}

} // namespace Fcz
