<?php

use Nette\Application\UI;

/**
 * Base presenter for all presenters with discussion.
 */
abstract class DiscussionPresenter extends BasePresenter
{

	protected $discussionComponentsData = null;



	public function setupDiscussion($access, $content, $subContentId, $pageNumber, $findPost)
	{
		$database = $this->context->database;

		$numPosts = $database->table('Posts')->where('ContentId', $content['Id'])->where("Deleted",0)->count();
		$paginator = new Nette\Utils\Paginator;
		$paginator->setItemCount($numPosts);
		$paginator->setItemsPerPage($this->user->identity->data['postsPerPage']);
		$pageNumber = $pageNumber;
		$jumpToPostId = 0;

		if ($findPost != null)
		{
			$targetPost = null;
			if ($findPost == "first-unread")
			{
				$targetPost = $database->table('Posts')->select('Id')->where(array(
					'ContentId' => $content['Id'],
					'TimeCreated <' => $database->table('LastVisits')->select('Time')->where(array(
						'ContentId' => $content['Id'],
						'UserId' => $this->user->id
					))
				))->min('TimeCreated')->fetch();
			}
			else
			{
				$targetPost = $database->table('Posts')->select('Id')->where('Id', $findPost)->fetch();
			}
			$numNewerPosts = $database->table('Posts')->where(array(
				'ContentId' => $content['Id'],
				'Id <' => $targetPost['Id']
			))->count();
			$jumpToPostId = $targetPost['Id'];
			$pageNumber = abs($numNewerPosts - $numPosts) / $paginator->getItemsPerPage();
		}
		$paginator->setPage($pageNumber);

		// Setup template
		$this->template->setParameters(array(
			'paginator' => $paginator,
			'content' => $content,
			'access' => $access,
			'jumpToPostId' => $jumpToPostId
		));

		// Pass data to component factories
		$this->discussionComponentsData = array(
			'paginator' => $paginator,
			'access' => $access,
			'content' => $content,
			'subContentId' => $subContentId
		);
	}



	/** Nette component factory function
	*/
	public function createComponentDiscussionPaginator()
	{
		$data = $this->discussionComponentsData;
		if ($data === null)
		{
			throw new Exception("DiscussionPaginator component requested, input data not provided");
		}
		$baseUrl = $this->getHttpRequest()->url->baseUrl;
		return new Fcz\DiscussionPaginator($this, $data["paginator"], $data['subContentId'], $baseUrl);
	}



	/** Nette component factory function
	*/
	public function createComponentDiscussionPosts()
	{
		$database = $this->context->database;
		$topic = $database->table('Topics')->where('Id', $this->getParameter('topicId'))->fetch();
		$content = $topic->ref('Content');
		$authorizator = new Authorizator($database);
		$access = $authorizator->authorize($content, $this->user);
		$this->setupDiscussion($access, $content, $topic['Id'], $this->getParameter('page'), "");
		$data = $this->discussionComponentsData;
		return new Fcz\DiscussionPosts($this, $data['content'], $data['access'], $data['paginator']);
	}

	
	public function getAuthorData($authorId){
		$database = $this->context->database;
		$user = $database->table('Users')->where('Id', $authorId)->fetch();
		if($user){
			$hodnost = "";$image = ""; $color="";
			if($user["IsAdmin"]){$hodnost="Administrátor";$color="Admin";$image="star.png";}
			if($user["IsFrozen"]){$hodnost="Kostka ledu";$color="Frozen";}
			if($user["IsBanned"]){$hodnost="Zavřen v krabici";$color="Banned";$image="banana.png";}
			if($user["Deleted"]){$hodnost="Vymazán";$color="Delete";}			
			return array("Hodnost" => $hodnost, "Color" => $color, "Image" => $image);
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
				. Fcz\LanguageUtilities::czechCount($numDeleted, array("smazan", "ý", "é", "ých"));
			$textIgnored = $numIgnored . " "
				. Fcz\LanguageUtilities::czechCount($numIgnored, array("ignorovan", "ý", "é", "ých"));

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

}
