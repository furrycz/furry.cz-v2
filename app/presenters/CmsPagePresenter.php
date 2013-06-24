<?php

use Nette\Application\UI;

/**
 * CMS pages presenter
 */
class CmsPagePresenter extends BasePresenter
{

	/**
	 * Presents a single CMS page
	 */
	public function renderDefault($idOrAlias)
	{
		// Get id/alias of the page
		if (!isset($idOrAlias)) {
			$this->redirect('Homepage:default');
		}

		$where = NULL;
		if (ctype_digit($idOrAlias[0])) { // Aliases must not start with number
			$where = array('Id' => (int) $idOrAlias);
		} else {
			$where = array('Alias' => $idOrAlias);
		}

		// Load the page
		$cmsPage = $this->context->database->table('CmsPages')->where($where)->fetch();

		if ($cmsPage === false) {
			throw new Nette\Application\BadRequestException();
		}

		// Check access
		if ($cmsPage['Content']['Deleted'] === true) {
			throw new Nette\Application\BadRequestException();
		}

		// Display the pages
		$this->template->cmsPage = $cmsPage;

	}

}
