<?php

namespace Fcz
{

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
	* @param string                $rules { isImage, imgMaxWidth, imgMaxHeight, maxFilesize }
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



	/**
	* @param int Nonzero index
	* @return string Hash
	*/
	private function generateFileKey($index)
	{
		$dictionaries = array(
			"_tHGl5Ayw7BjS6m3Y2faRFiW4DbN9vgoQXKLMOZV0ePnd1kqTIExUhJzCrcp8su", // Starting underscore is never printed for nonzero input.
			"6y1e8lWj4VmYqNsw0H2iOKArthvguSkCRcX7ZaGz3PnF5E9bxDUBfodpTQMLIJ",
			"v1pL3WyiHrZ8TPafRUA7mIe4kldCuXSb0x5cGsqtYJwDhOoE6VQjzBngNF92KM",
			"J2sHxhC984I1lyEXutmRcqvB57MrSnkDWipOg3eVLdNzPbQKUoYZ6aTwjF0AfG",
			"Wfc542ztemhqdCUkBylVuT7RE1gjOn6SXP93oQI8KsLGNprHFvbZwAaiM0YxDJ",
			"K9kdCa8EzrHQ6eGUlWoy2vhcqnSFsTD5L1YPV0gb3wmBixNZI7uJfXAOpR4Mjt"
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



	public function handleUpload(\Nette\Http\FileUpload $fileUpload)
	{
		if (!$fileUpload.isOk())
		{
			throw new Exception("The file didn't upload correctly");
		}
		$database = $this->presenter->context->database;
		$config = $this->presenter->parameters;
		$user = $this->presenter->user;

		$database->beginTransaction();

		// Generate IDs
		$id = $database->table('UploadedFiles')->max('Id');
		$key = $this->generateFileKey($id);
		$path = $config["fileUploads"]["directory"]
			. '/user_' . $this->presenter->user->id
			. '_' . $key . $this->getFileUploadExtension($fileUpload);

		// Save database entry
		$database->table('UploadedFiles')->insert(array(
			'Id' => $id,
			'Key' => $key,
			'FileName' => $path,
			'Name' => 'User avatar: ' . $user->identity->data['username'] . " (ID: " . $user->id . ")"
		));

		$database->commit();

		// Move the file
		$fileUpload->move($config["baseDirectory"] . "/" . $path);

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
			throw new Exception("The file didn't upload correctly");
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
}

} // namespace Fcz
