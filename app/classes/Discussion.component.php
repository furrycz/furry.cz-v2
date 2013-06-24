<?php

namespace Fcz
{

/**
* Displays discussion posts and form.
*/
class Discussion extends \Nette\Application\UI\Control
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
		$posts = $database->table('Posts')->limit($paginator->getLength(), $paginator->getCountdownOffset());
	}
}

} // namespace Fcz
