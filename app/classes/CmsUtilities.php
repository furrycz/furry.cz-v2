<?php

namespace Fcz;

class CmsUtilities extends \Nette\Object
{

	protected $presenter;

	public function __construct(\Nette\Application\IPresenter $presenter)
	{
		$this->presenter = $presenter;
	}

	/**
	 * Loads CMS page for inserting into another page.
	 * Returns the page on success and error/disclaimer for inaccessible pages.
	 * @param int|string $idOrAlias
	 * @return string The HTML code to insert into page
	 */
	public function getCmsHtml($idOrAlias)
	{
		// Prepare query
		$where = NULL;
		if (is_int($idOrAlias) || (is_string($idOrAlias) && ctype_digit($idOrAlias[0]))) { // Aliases must not start with number
			$where = array('Id' => (int) $idOrAlias);
		} elseif (is_string($idOrAlias)) {
			$where = array('Alias' => $idOrAlias);
		} else {
			// TODO print error to log
			return "<p>[!] Nastala chyba serveru; CMS stránka je chybně odkazována</p>";
		}

		// Load the page
		$cmsPage = $this->presenter->context->database->table('CmsPages')->where($where)->fetch();

		// Check if page exists
		if ($cmsPage === false) {
			return "<p>[!] Odkazovaná CMS stránka neexistuje</p>";
		}

		// Check access TODO

		// Return the HTML
		return $cmsPage['Text'];
	}

}
