<?php

use Nette\Application\UI;

namespace Fcz
{

use Nette\Utils;
use Nette\Application;
use Nette\Database;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;

/**
* Displays discussion posts and form.
*/
class Discussion extends Application\UI\Control
{

	protected $presenter = null;

	protected $content = null;

	protected $nettePaginator = null;

	protected $access = null;

	protected $subContentId = null; // Id of topic/event/cms/writing/image...

	protected $baseUrl = null;



	public function __construct(
			Application\UI\Presenter $presenter,
			Database\Table\ActiveRow $content,
			$subContentId,
			$baseUrl,
			$access,
			$pageNumber,
			$findPost = null)
	{
		parent::__construct($presenter, "discussion");

		$database = $presenter->context->database;

		//echo "DBG Discussion::__construct()> this->getHttpRequest: [", gettype($presenter->getHttpRequest()), "] <br>";

		$numPosts = $database->table('Posts')->where('ContentId', $content['Id'])->where("Deleted",0)->count();
		$paginator = new Utils\Paginator;
		$paginator->setItemCount($numPosts);
		$postsPerPage = ($presenter->user->isLoggedIn()) ? $presenter->user->identity->data['postsPerPage'] : 50;
		$paginator->setItemsPerPage($postsPerPage);
		$pageNumber = $pageNumber;
		$jumpToPostId = 0;

		if ($findPost != null)
		{
			$targetPost = null;
			if ($findPost == "first-unread" && $presenter->user->isLoggedIn())
			{
				$targetPost = $database->table('Posts')->select('Id')->where(array(
					'ContentId' => $content['Id'],
					'TimeCreated <' => $database->table('LastVisits')->select('Time')->where(array(
						'ContentId' => $content['Id'],
						'UserId' => $presenter->user->id
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
		$this->presenter = $presenter;
		$this->nettePaginator = $paginator;
		$this->content = $content;
		$this->access = $access;
		$this->subContentId = $subContentId;
	}



	/*********** COMPONENTS ***********/



	public function createComponentDiscussionPosts()
	{
		return new DiscussionPosts($this, $this->presenter, $this->content, $this->subContentId, $this->access, $this->nettePaginator);
	}



	public function createComponentDiscussionPaginator()
	{
		return new DiscussionPaginator($this, $this->presenter, $this->nettePaginator, $this->subContentId, $this->baseUrl);
	}

}

} // namespace Fcz
