<?php

use Nette\Application\UI;

/**
 * Uploaded files presenter
 */
class UploadPresenter extends BasePresenter
{

	/**
	 * Presents a single CMS page
	 */
	public function renderDefault($key)
	{


	}



	/** Generates file key based on it's database index.
	*/
	public function generateKey($index)
	{
		// abcde fghij klmno pqrst uvwxy z = 16 (With uppercase = 32)
		// 0-9 = 10
		// Total: 42 combinations per character
		// 6 characters = 5 489 031 744 (INT_MAX is 2147483648)


	}

}
