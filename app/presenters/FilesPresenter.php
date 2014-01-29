<?php

use Nette\Application\UI;
use Nette\Utils;

/**
 * Uploaded files presenter
 */
class FilesPresenter extends BasePresenter
{
	/**
	 * Presents a single file
	 */
	public function renderDefault($key)
	{
		$config = $this->context->parameters;
		
		$image = $this->context->database->table("UploadedFiles")->where("Key", $key)->fetch();
		if ($image == null)
		{
			exit();
		}
		
		$filePath = $config["baseDirectory"] . "/" . $image["FileName"];
		
		header('Content-Type: ' . Utils\MimeTypeDetector::fromFile($filePath));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filePath));
		ob_clean();
		flush();
		readfile($filePath);
		exit;
	}





}
