<?php

use Nette\Application\UI;
use Nette\Utils;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;
use Fcz;

/**
 * Presents uploaded files + thumbnails. Caches thumbnails.
 */
class FilesPresenter extends BasePresenter
{
	/**
	 * Presents a single file
	 */
	public function renderDefault($key)
	{
		// Security
		Fcz\SecurityUtilities::checkUploadedFileKey($key);

		$config = $this->context->parameters;
		
		$image = $this->context->database->table("UploadedFiles")->where("Key", $key)->fetch();
		if ($image == null)
		{
			exit();
		}
		
		$filePath = $config["baseDirectory"] . "/" . $image["FileName"];
		/*
		header('Content-Type: ' . Utils\MimeTypeDetector::fromFile($filePath));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filePath));
		ob_clean();
		flush();
		readfile($filePath);
		exit;*/

		$this->sendHeadersAndImage($filePath);
		exit;
	}



	/**
	* Generates and presents an image preview for given file. Caches generated images.
	*
	* @param string   $key       UploadedFile key
	* @param string   $profile   A preset of image attributes, defined in config file.
	*/
	public function renderPreview($key, $profile)
	{
		// Var
		$database = $this->context->database;

		// Security
		Fcz\SecurityUtilities::checkPreviewImageProfile($profile);
		Fcz\SecurityUtilities::checkUploadedFileKey($key);

		// Fetch profile
		$config = $this->context->parameters;
		$profileConf = $config["fileUploads"]["previews"]["profiles"][$profile];
		if ($profileConf == null)
		{
			throw new BadRequestException("Preview-image profile " . htmlentities($profile) . " is not supported", 405); // HTTP 405 = Method not supported
		}

		// Check if image exists
		$image = $database->table("UploadedFiles")->where("Key", $key)->fetch();
		if ($image === false)
		{
			throw new BadRequestException("Zadaný obrázek neexistuje", 404);
		}

		// Search the cache
		$cacheDir = $config["fileUploads"]["previews"]["cache"]["directory"];
		if ($cacheDir == null)
		{
			throw new ApplicationException("Missing config entry: parameters/fileUploads/previews/cache/directory");
		}

		$cachedFile = $database->table("ImagePreviewCache")->where("UploadedFile = ? AND Profile = ?", $image["Id"], $profile)->fetch();
		if ($cachedFile === false)
		{
			// NO CACHED FILE, GENERATE IT.
			$sourceFilePath = $config["baseDirectory"] . "/" . $image["FileName"];
			if (! file_exists($sourceFilePath))
			{
				throw new ApplicationException("DB/UploadedFile entry {$image["Id"]} has no associated file");
			}

			// Check image type
			$sourceInfo = getimagesize($sourceFilePath);
			$type = $sourceInfo[2];

			// Load image
			$resource = null;
			switch ($type)
			{
				case (IMAGETYPE_GIF):
					$resource = imagecreatefromgif($sourceFilePath);
					break;

				case (IMAGETYPE_JPEG):
					$resource = imagecreatefromjpeg($sourceFilePath);
					break;

				case (IMAGETYPE_PNG):
					$resource = imagecreatefrompng($sourceFilePath);
					break;

				case (IMAGETYPE_SWF):
					throw new Nette\NotImplementedException("Can't create preview of SWF files.");

				default:
					throw new ApplicationException("Can't create preview from uploaded file {$image["Id"]}, unknown type, IMAGETYPE_* is $type");
			}

			if ($resource == null)
			{
				throw new ApplicationException("Can't create preview from uploaded file {$image["Id"]}, loading failed, IMAGETYPE_* is $type");
			}

			// Resize image
			$srcW = $sourceInfo[0];
			$srcH = $sourceInfo[1];
			if ($srcW > $srcH)
			{
				$dstW = (int) $profileConf["maxWidth"];
				$dstH = (int) $srcH * ( ((float) $dstW) / ((float) $srcW));
			}
			else
			{
				$dstH = (int) $profileConf["maxHeight"];
				$dstW = (int) $srcW * ( ((float) $dstH) / ((float) $srcH));
			}
			$outResource = imagecreatetruecolor($dstW, $dstH);
			$result = imagecopyresampled($outResource, $resource, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
			if ($result === false)
			{
				throw new ApplicationException("Can't create preview from uploaded file {$image["Id"]}, imagecopyresampled() call returned FALSE, IMAGETYPE_* is $type");
			}

			// Check cache dir.

			$dstDir = $config["baseDirectory"] . "/" . $cacheDir;
			if (! is_dir($dstDir))
			{
				// Create cache dir
				if(! mkdir($dstDir))
				{
					throw new ApplicationException("Could not create preview-image-cache directory '$dstDir'");
				}
			}
			else
			{
				if (! is_writable($dstDir))
				{
					throw new ApplicationException("Cannot write to preview-image-cache directory '$dstDir'");
				}
			}

			// Save image
			$outFilename = "$key,$profile";
			$outPath = "$dstDir/$outFilename";
			$quality = $profileConf["jpegQuality"];
			imagejpeg($outResource, $outPath, (int) $quality);

			// Save DB entry
			$dbEntry = $database->table("ImagePreviewCache")->insert(array(
				"UploadedFile" => $image["Id"],
				"Filename" => $outFilename,
				"Profile" => $profile
			));

			if ($dbEntry === false)
			{
				throw new ApplicationException("Failed to create DB/ImagePreviewCache entry.");
			}

			$this->sendHeadersAndImage($outPath, $sourceInfo["mime"]);
			exit;
		}
		else
		{
			//Nette\Diagnostics\Debugger::dump($cachedFile);
			$path = $config["baseDirectory"] . "/" . $cacheDir . "/" . $cachedFile["Filename"];
			$this->sendHeadersAndImage($path);
			exit;
		}
	}



	/**
	* @param string $filePath Full server path
	* @param string $mime     optional
	* @throws Nette\FileNotFoundException If the file doesn't exist or isn't readable.
	*/
	private function sendHeadersAndImage($filePath, $mime = null)
	{
		// Check file
		if (! is_readable($filePath))
		{
			throw new Nette\FileNotFoundException("Cannot read file $filePath");
		}

		// Get MIME
		if ($mime == null)
		{
			$mime = Utils\MimeTypeDetector::fromFile($filePath);
		}

		header('Content-Type: ' . $mime);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filePath));
		ob_clean();
		flush();
		readfile($filePath);
	}

}
