<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();

		// Homepage
		$router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);

		// CmsPage
		$router[] = new Route('page[/<idOrAlias>]', 'CmsPage:default');

		// Forum
		$router[] = new Route('forum/<action>[/<topicId>][/<page=1>]', 'Forum:default');

		// Default route
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;

		//$router[] = new Route('user/<action>[/<userId>]', 'User:profile');
	}

}
