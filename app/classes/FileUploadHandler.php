<?php

namespace Fcz
{

use Nette\Utils;
use Nette\Application;
use Nette\Database;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\ApplicationException;
use Nette\Application\BadRequestException;

class FileUploadHandler extends \Nette\Object
{

	private $presenter;



	public function __construct($presenter)
	{
		$this->presenter = $presenter;
	}



	/**
	* @param string $uploadType - maps directly to config file entries
	* @return array Rules specified in config file
	*/
	private function getUploadRules($uploadType)
	{
		if ($uploadType != null && isset($this->presenter->context->parameters['fileUpload'][$uploadType]))
		{
			return $this->presenter->context->parameters['fileUpload']['types'][$uploadType].toArray();
		}
		else
		{
			return null;
		}
	}



	/** Validates file upload.
	* @param Nette\Http\FileUpload $fileUpload
	* @param string                $uploadType Defined in config file.
	* @return array {bool:result, string:error_message} Result: True if file is OK to upload, false on errors.
	*/
	public function validateUpload(\Nette\Http\FileUpload $fileUpload, $uploadType)
	{
		if (!($fileUpload instanceof \Nette\Http\FileUpload))
		{
			throw new Exception("FileHandler::handleUpload(): Not a file upload");
		}

		// GET RULES
		$rules = $this->getUploadRules($uploadType);

		// APPLY RULES

		// Filesize
		// Note: Form::MAX_FILE_SIZE rule can't be used because it makes the field REQUIRED
		if (isset($rules['maxFilesize']) && $fileUpload->getSize() > $rules['maxFilesize'])
		{
			return array(false, "Soubor je příliš velký (" . $fileUpload->getSize()/1024 . "Kb, maximum " . $rules['maxFilesize']/1024 . "Kb)");
		}

		// Is image
		// Note: Form::IMAGE rule can't be used because it makes the field REQUIRED
		if (isset($rules['isImage']) && $rules['isImage'] == true)
		{
			if ($fileUpload->isImage() == false)
			{
				return array(false, "Obrázek musí být ve formátu PNG/JPEG/GIF");
			}

			// Image width
			if (isset($rules['maxWidth']) && $fileUpload->getImageWidth() > $rules['maxWidth'])
			{
				return array(false, "Obrázek je příliš široký (" . $fileUpload->getImageWidth() . "px, maximum  " . $rules['maxWidth'] . "px)");
			}

			// Image height
			if (isset($rules['maxHeight']) && $fileUpload->getImageHeight() > $rules['maxHeight'])
			{
				return array(false, "Obrázek je příliš vysoký (" . $fileUpload->getImageHeight() . "px, maximum " . $rules['maxHeight'] . "px)");
			}
		}

		return array(true, "");
	}



	/** Validates file upload and adds error report to form.
	* @param Nette\Application\UI\Form $form
	* @param Nette\Http\FileUpload     $fileUpload
	* @param string                    $errMsgFieldName
	* @param string                    $uploadType Defined in config file.
	* @return array {bool:result, string:error_message} Result: True if file is OK to upload, false on errors.
	*/
	public function validateFormUpload(\Nette\Application\UI\Form $form, $errMsgFieldName, \Nette\Http\FileUpload $fileUpload, $uploadType)
	{
		list($result, $errMsg) = $this->validateUpload($fileUpload, $uploadType);
		if (! $result)
		{
			$form->addError("$errMsgFieldName: $errMsg");
		}
		return $result;
	}



	/**
	* @param int Nonzero index
	* @return string Hash
	*/
	private function generateFileKey($index)
	{
		// abcde fghij klmno pqrst uvwxy z = 16 chars (With uppercase = 32 chars)
		// 0-9 = 10 chars
		// Total: 42 combinations per character
		// 6 characters = 5 489 031 744 (INT_MAX is 2147483648)

		$config = $this->presenter->context->parameters['fileUploads']['keyGeneratorDictionaries'];
		$dictionaries = array (
			$config['d1'],
			$config['d2'],
			$config['d3'],
			$config['d4'],
			$config['d5'],
			$config['d6'],
		);

		$offset = (int) ($index / 6);
		$remainder = $index % 6;

		$hash = array();
		for ($i = 0; $i < 6; $i++)
		{
			$hash[] = substr($dictionaries[$i], ($i <= $remainder-1) ? $offset+1 : $offset, 1);
		}

		return implode($hash, "");
	}



	/**
	* @return string ".ext" | ""
	*/
	private function getFileUploadExtension(\Nette\Http\FileUpload $fileUpload)
	{
		$tokens = explode('.', $fileUpload->sanitizedName);
		if (count($tokens) > 1)
		{
			return "." . $tokens[count($tokens) - 1];
		}
		else
		{
			return "";
		}
	}



	/** Handles file upload.
	* @param \Nette\Http\FileUpload $fileUpload The data.
	* @param string                 $sourceType {GalleryImage, [Special]*Cms[Image/Attachment], Forum[Image/Attachment]}
	* @param int                    $sourceId   Id of 'Content' where the file was downloaded from.
	* @return array                 {id, key}   Database keys of the uploaded file.
	*/
	public function handleUpload(\Nette\Http\FileUpload $fileUpload, $sourceType, $sourceId)
	{
		$database = $this->presenter->context->database;
		$config = $this->presenter->context->parameters;
		$user = $this->presenter->user;

		if (!$fileUpload->isOk())
		{
			throw new Exception("Upload se nezdaril.");
		}

		// Generate IDs
		$id = $database->table('UploadedFiles')->max('Id') + 1;
		$key = $this->generateFileKey($id);
		$path = $config["fileUploads"]["types"]["genericFile"]["directory"]. '/' . $key;

		// Save database entry
		$dbEntry = $database->table('UploadedFiles')->insert(array(
			'Id'         => $id,
			'Key'        => $key,
			'FileName'   => $path,
			'Name'       => $fileUpload->getName() . " (" . $user->identity->data['username'] . ")",
			"SourceType" => $sourceType,
			"SourceId"   => $sourceId
		));

		// Move the file
		$fileUpload->move($config["baseDirectory"] . "/" . $path);

		return array(
			$dbEntry["Id"],
			$dbEntry["Key"]
		);
	}



	/** Handles file upload, replaces an existing upload.
	* @param \Nette\Http\FileUpload $fileUpload The data.
	* @param int                    $uploadedFileId Database entry to updte
	* @return bool                  True on success.
	*/
	public function handleUploadUpdate(\Nette\Http\FileUpload $fileUpload, $uploadedFileId)
	{
		$database = $this->presenter->context->database;
		$config = $this->presenter->context->parameters;
		$user = $this->presenter->user;

		if (!$fileUpload->isOk())
		{
			throw new Exception("Upload se nezdaril.");
		}

		// Generate IDs
		$dbUploadedFile = $database->table('UploadedFiles')->where("Id", $uploadedFileId)->fetch();
		if ($dbUploadedFile === false)
		{
			throw new BadRequestException("Zadaný soubor neexistuje");
		}

		$fullPath = $config["baseDirectory"] . "/" . $dbUploadedFile["FileName"];

		// Update database entry
		$dbUploadedFile->update(array(
			'Name' => $fileUpload->getName() . " (" . $user->identity->data['username'] . ")",
		));

		// Delete old file
		unlink($fullPath);

		// Move the new file
		$fileUpload->move($fullPath);

		return true;
	}



	/** Handles uploading user avatar/profile photo
	* @param \Nette\Forms\Controls\UploadControl The upload
	* @param string {userAvatar | profilePhoto}
	* @return string | null The result filename to save into user profile. Null if no image was uploaded
	*/
	public function handleProfileImageUpload(\Nette\Forms\Controls\UploadControl $fileUploadControl, $uploadType)
	{
		if ($fileUploadControl->isFilled() == false)
		{
			return null;
		}

		$fileUpload = $fileUploadControl->getValue();
		if (!$fileUpload->isOk())
		{
			throw new Exception("Soubor se nepodarilo nahrat");
		}
		$database = $this->presenter->context->database;
		$config = $this->presenter->context->parameters;
		$upConfig = $config["fileUploads"];
		$user = $this->presenter->user;

		// Generate file path
		$filename = $this->presenter->user->id . $this->getFileUploadExtension($fileUpload);
		$path = $config["baseDirectory"]
			. '/' . $upConfig["types"][$uploadType]["directory"]
			. '/' . $filename;

		// Move the file
		$fileUpload->move($path);

		return $filename;
	}



	/**
	* Deletes file and it's database entry.
	*/
	public function deleteUploadedFileById($id)
	{
		if (! is_int($id))
		{
			throw Exception("deleteUploadedFileById(): wrong param 'id', expected int, got: " . gettype($id));
		}
	
		$database = $this->presenter->context->database;

		$this->deleteUploadedFile($database->table("UploadedFiles")->where("Id", $id)->fetch());
	}
	
	
	
	/**
	* Deletes file and it's database entry.
	* @param string $uploadType Defined in config file [common/parameters/fileUploads/types]
	*/
	public function deleteUploadedFile(Database\Table\ActiveRow $entry, $uploadType)
	{
		$config = $this->presenter->context->parameters;

		$this->deleteImagePreviews($entry["Id"]);
	
		// Delete the file
		// NOTE: UploadedFiles/FileName = Application-local path of the file
		$path = $config["baseDirectory"] . '/' . $entry["FileName"];
		unlink($path);

		// Delete db entry
		$entry->delete();
	}



	/**
	* Deletes thumbnails of specified uploaded file(s).
	* @param int|array $uploadedFileIds
	*/
	public function deleteImagePreviews($uploadedFileIds)
	{
		if (is_int($uploadedFileIds))
		{
			$uploadedFileIds = array($uploadedFileIds);
		}
		if (! is_array($uploadedFileIds))
		{
			throw new \Nette\InvalidArgumentException("Expected int or array, got " . gettype($uploadedFileIds));
		}

		$database = $this->presenter->context->database;
		$thumbs = $database->table("ImagePreviewCache")->where("UploadedFile", $uploadedFileIds);
		if ($thumbs === false)
		{
			// Nothing to do
			return;
		}
		$config = $this->presenter->context->parameters;
		$cacheDir = $config["baseDirectory"] . "/" . $config["fileUploads"]["previews"]["cache"]["directory"];
		foreach ($thumbs as $thumb)
		{
			$path = $cacheDir . "/" . $thumb["Filename"];
			if (! is_file($path))
			{
				throw new ApplicationException("Could not find image preview file `$path`, DB id: `{$thumb["Id"]}`");
			}
			if (! unlink($path))
			{
				throw new ApplicationException("Failed to unlink() image preview file `$path`, DB id: `{$thumb["Id"]}`");
			}
		}
		$thumbs->delete();
	}

}

} // namespace Fcz
