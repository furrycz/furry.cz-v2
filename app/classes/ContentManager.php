<?php

namespace Fcz
{

class ContentManager extends \Nette\Object
{

	private $presenter = null;
	
	
	
	public function __construct(\Nette\Application\UI\Presenter $presenter)
	{
		$this->presenter = $presenter;
	}
	
	
	
	public function deleteContent(\Nette\Database\ActiveRow $content)
	{
		$content->related("Ownership")->delete();
		$content->related("Permissions")->delete();
		$content->related("Posts")->delete();
		foreach ($content->related("Polls") as $poll)
		{
			$this->deletePoll($poll);
		}
		$content->delete();
	}
	
	
	
	public function deletePoll(\Nette\Database\ActiveRow $poll)
	{
		$poll->related("PollAnswers")->delete();
		$poll->related("PollVotes")->delete();
		$poll->delete();
	}
	
	
	
	/**
	* Deletes image from gallery.
	*/
	public function deleteImage(\Nette\Database\ActiveRow $image, $deleteFile = true)
	{	
		if ($deleteFile == true)
		{
			$this->getUploadHandler->deleteUploadedFile($image["UploadedFileId"]);
		}
		$this->deleteContent($image->ref("ContentId")->fetch());
		$image->delete();
	}

}

} // namespace Fcz