<?php

namespace Fcz
{

use \Nette\Diagnostics\Debugger;

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



	public function updateLastVisit(\Nette\Database\Table\ActiveRow $content, $userId, DateTime $time = null)
	{
		if (! $userId)
		{
			throw new InvalidArgumentException("Invalid parameter #2 `\$userId`");
		}
		if ($time === null)
		{
			$time = new \DateTime();
		}
		// Fill last visit
		$database = $this->presenter->context->database;
		$lastVisit = $database
			->table("LastVisits")
			->where("ContentId = ? AND UserId = ?", $content["Id"], $userId)
			->fetch();
		if ($lastVisit !== false)
		{
			$lastVisit->update(array("Time" => $time));
		}
		else
		{
			$database
				->table("LastVisits")
				->insert(array(
					"ContentId" => $content["Id"],
					"UserId" => $userId,
					"Time" => $time
				));
		}
	}



	/**
	* @param        string $contentType
	* @param           int $userId
	* @param DateTime|null $time
	*/
	public function bulkUpdateLastVisit($contentType, $userId, \DateTime $time = null)
	{
		$database = $this->presenter->context->database;
		$contentEntries = $database->table("Content")->where("Type", $contentType);
		if (! $userId)
		{
			throw new InvalidArgumentException("Invalid parameter #2 `\$userId`");
		}
		if ($time === null)
		{
			$time = new \DateTime();
		}

		// Fill last visit entries where missing;
		$lastVisits = $database->table("LastVisits")->where("UserId", $userId);
		$notVisited = $contentEntries->where("Id NOT", $lastVisits->select("ContentId"));
		foreach ($notVisited as $content)
		{
			$database->table("LastVisits")->insert(array(
				"UserId"    => $userId,
				"ContentId" => $content["Id"],
				"Time"      => $time,
			));
		}

		// Update all last visits
		$lastVisits
			->where("ContentId", $database->table("Content")->select("Id")->where("Type", $contentType))
			->update(array("Time" => $time));
	}

}

} // namespace Fcz
