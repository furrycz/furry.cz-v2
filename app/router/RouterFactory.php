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

		// Gallery - expositions
		$router[] = new Route('gallery/exposition/create', 'Gallery:createExposition');
		$router[] = new Route('gallery/exposition/edit/<expositionId>', 'Gallery:editExposition');
		$router[] = new Route('gallery/exposition/delete/<expositionId>', 'Gallery:deleteExposition');
		$router[] = new Route('gallery/exposition/<expositionId>', 'Gallery:exposition');

		// Gallery - images
		$router[] = new Route('gallery/add', 'Gallery:addImage');
		$router[] = new Route('gallery/show/<imageId>', 'Gallery:showImage');
		$router[] = new Route('gallery/edit/<imageId>', 'Gallery:editImage');
		$router[] = new Route('gallery/delete/<imageId>', 'Gallery:deleteImage');

		// Gallery
		$router[] = new Route('gallery/user[/<userId>][/<page=1>]', 'Gallery:user');
		$router[] = new Route('gallery/<action>[/<userId>][/<page=1>]', 'Gallery:default');

		// Forum
		$router[] = new Route('forum/<action>[/<topicId>][/<page=1>][/<subAction>]', 'Forum:default');
		
		// Calendar
		$router[] = new Route('events/new[/<year>][/<month>]', 'Events:new');
		$router[] = new Route('events/view[/<eventId>]', 'Events:view');
		$router[] = new Route('events/visible[/<eventId>]', 'Events:visible');
		$router[] = new Route('events/edit[/<eventId>]', 'Events:edit');
		$router[] = new Route('events/day/[<year>/][<month>/][<day>]', 'Events:day');
		$router[] = new Route('events/[<year>/][<month>]', 'Events:default');

		// Intercom
		$router[] = new Route('intercom/autocomplete', 'Intercom:autocomplete');
		$router[] = new Route('intercom[/<name>]', 'Intercom:default');
		
		// Ajax
		$router[] = new Route('ajax/', 'Ajax:default');

		// Files
		$router[] = new Route('preview/<key>/<profile>', 'Files:preview');
		$router[] = new Route('file/<key>', 'Files:default');
		
		// Post
		$router[] = new Route('post/delete[/<postId>]', 'Post:delete');
		$router[] = new Route('post/edit[/<postId>]', 'Post:edit');
		$router[] = new Route('post/', 'Post:default');

		// Default route
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;

		//$router[] = new Route('user/<action>[/<userId>]', 'User:profile');
	}

}
