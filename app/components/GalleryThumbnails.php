<?php

namespace Fcz
{

/**
* Displays image listing in gallery.
*/
class GalleryThumbnails extends \Nette\Application\UI\Control
{
	
	private $presenter = null;
	
	private $ownerUserId = null;
	
	private $expositionId = null;
	
	
	
	/**
	* @param \Nette\Application\UI\Presenter $presenter
	* @param int|NULL $ownerUserId The user owning the images, ignored if Exposition-ID is specified.
	* @param int|NULL $expositionId Expo database ID, pass NULL for main exposition.
	*/
	public function __construct(\Nette\Application\UI\Presenter $presenter, $ownerUserId, $expositionId)
	{
		$this->presenter = $presenter;
		$this->ownerUserId = $ownerUserId;
		$this->expositionId = $expositionId;
	}
	
	
	
	public function render()
	{
		$database = $this->presenter->context->database;
		
		if ($this->expositionId != null)
		{
			$images = $database->table("Images")->where("Exposition", $this->expositionId);
		}
		else
		{
			$images = $database->table("Images")->where(array(
				"Exposition" => 0,
				"ContentId" => $database->table("Ownership")->where("UserId", $this->ownerUserId)->select("ContentId")
			));
		}
		
		// Setup template
		$template = $this->presenter->template;
		$template->setFile(__DIR__ . '/../templates/components/galleryThumbnails.latte');
		$template->images = $images;
		$template->render();
	}
	
}

}

