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

		// CMS
		$router[] = new Route('cms/add', 'CmsPage:newPage');
		$router[] = new Route('cms/edit/<idOrAlias>', 'CmsPage:editPage');
		$router[] = new Route('cms', 'CmsPage:default');
		$router[] = new Route('cms/permissions/<idOrAlias>', 'CmsPage:managePermissions');
		$router[] = new Route('page[/<idOrAlias>]', 'CmsPage:showPage');

		// Gallery - expositions
		$router[] = new Route('gallery/exposition/create', 'Gallery:createExposition');
		$router[] = new Route('gallery/exposition/edit/<expositionId>', 'Gallery:editExposition');
		$router[] = new Route('gallery/exposition/delete/<expositionId>', 'Gallery:deleteExposition');
		$router[] = new Route('gallery/exposition/<expositionId>', 'Gallery:exposition');

		// Gallery - images
		$router[] = new Route('gallery/add', 'Gallery:addImage');
		$router[] = new Route('gallery/show/<imageId>[/<page=1>]', 'Gallery:showImage');
		$router[] = new Route('gallery/edit/<imageId>', 'Gallery:editImage');
		$router[] = new Route('gallery/delete/<imageId>', 'Gallery:deleteImage');

		// Gallery
		$router[] = new Route('gallery/user[/<userId>][/<page=1>]', 'Gallery:user');
		$router[] = new Route('gallery/<action>[/<userId>][/<page=1>]', 'Gallery:default');

		// Forum
		$router[] = new Route('forum/<action>[/<topicId>][/<page=1>]', 'Forum:default');
		
		// Calendar
		$router[] = new Route('events/new[/<year>][/<month>]', 'Events:new');
		$router[] = new Route('events/view[/<eventId>]', 'Events:view');
		$router[] = new Route('events/visible[/<eventId>]', 'Events:visible');
		$router[] = new Route('events/edit[/<eventId>]', 'Events:edit');
		$router[] = new Route('events/day/[<year>/][<month>/][<day>]', 'Events:day');
		$router[] = new Route('events/[<year>/][<month>]', 'Events:default');

		// Writings
		$router[] = new Route('writings/categories', 'Writings:manageCategories');
		$router[] = new Route('writings/categories/add', 'Writings:addCategory');
		$router[] = new Route('writings/categories/edit/<categoryId>', 'Writings:editCategory');
		$router[] = new Route('writings/categories/delete/<categoryId>', 'Writings:deleteCategory');

		$router[] = new Route('writings/author/<userId>', 'Writings:user');
		$router[] = new Route('writings/show/<writingId>', 'Writings:showWriting');
		$router[] = new Route('writings/<action>[/<id>]', 'Writings:default');

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
