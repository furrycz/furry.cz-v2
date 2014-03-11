<?php

namespace Fcz
{

use Nette\Application\UI;

class DiscussionPaginator extends UI\Control
{

	private $nettePaginator;

	private $subContentId; // Id of topic/event/cms/writing/image

	private $presenter;

	private $baseUrl;



	public function __construct(UI\Control $parent, UI\Presenter $presenter, \Nette\Utils\Paginator $nettePaginator, $subContentId, $baseUrl)
	{
		parent::__construct($parent, "discussionPaginator");

		//$this->presenter = $presenter;
		$this->nettePaginator = $nettePaginator;
		$this->subContentId = $subContentId;
		$this->presenter = $presenter;
		$this->baseUrl = $baseUrl;
	}



	public function render()
	{
		// Config
		$config = $this->presenter->context->parameters["discussionPaginatorComponent"];
		$edgeSpan = (int) $config['edgeSpan'];
		$currPageSpan = (int) $config['currentPageSpan'];

		// Vars
		$firstPage = $this->nettePaginator->getFirstPage();
		$lastPage = $this->nettePaginator->getLastPage();
		$currentPage = $this->nettePaginator->getPage();
		$route = "{$this->presenter->name}:{$this->presenter->action}";

		// Figure out precision
		$precisionSkip = (int) ($this->nettePaginator->getPageCount() / $config['maxLinks']);

		// Generate paginator items
		$items = array();
		for ($i = $firstPage; $i <= $lastPage; $i++)
		{
			$href = $this->presenter->link($route, $this->subContentId, $i) . "#discussion";

			if ($i == $currentPage)
			{
				$items[] = "<span class='CurrentPage'>$i</span\n>";
			}
			else if ($i <= $firstPage + $edgeSpan
				|| $i >= $lastPage - $edgeSpan
				|| ($i >= $currentPage - $currPageSpan && $i <= $currentPage + $currPageSpan))
			{
				$items[] = "<a href='$href' class='Num'>$i</a\n>";
			}
			else if ($precisionSkip > 0 && $i % $precisionSkip == 0)
			{
				$items[] = "<a href='$href' class='Dot'><span>$i</span>.</a\n>";
			}
		}

		// Generate arrow links
		$nextPageHref = null;
		if ($currentPage < $lastPage)
		{
			$nextPageHref = $this->presenter->link($route, $this->subContentId, $currentPage + 1) . "#discussion";
		}
		$prevPageHref = null;
		if ($currentPage > $firstPage)
		{
			$prevPageHref = $this->presenter->link($route, $this->subContentId, $currentPage - 1) . "#discussion";
		}

		// Setup template
		$template = $this->template;
		$template->setFile(__DIR__ . '/../templates/components/discussionPaginator.latte');
		$template->setParameters(array(
			'paginator' => $this->nettePaginator,
			'nextPageHref' => $nextPageHref,
			'prevPageHref' => $prevPageHref,
			'paginatorHtmlLinks' => $items
		));
		$template->render();
	}



	public function getNettePaginator()
	{
		return $this->nettePaginator;
	}

}

} // namespace Fcz
