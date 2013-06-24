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

		$numPosts = $database->table('Posts')->count('ContentId', $content['Id']);
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



	public function createComponentDiscussion()
	{
		$data = $this->discussionComponentsData;
		if ($data === null)
		{
			throw new Exception("Discussion component requested, input data not provided");
		}
		return new Fcz\Discussion($this, $data['content'], $data['access'], $data['paginator']);
	}

}
