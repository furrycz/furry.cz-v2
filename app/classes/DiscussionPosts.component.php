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

		// Setup template
		$template = $this->presenter->template;
		$template->setFile(__DIR__ . '/../templates/components/discussionPosts.latte');
		$template->setParameters(array(
			'posts' => $posts
		));
		$template->render();
	}
}

} // namespace Fcz
