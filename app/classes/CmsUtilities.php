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
	
	/**
	 * @param time $ptime
	 * @return time as text 'před 7 hodinami'
	 */
	public static function getTimeElapsedString($ptime)
	{
		$etime = time() - $ptime;

		if ($etime < 1)
		{
			return 'právě teď';
		}

		$a = array( 12 * 30 * 24 * 60 * 60  =>  array('rokem','roky'),
					30 * 24 * 60 * 60       =>  array('měsícem','měsíci'),
					24 * 60 * 60            =>  array('dnem','dny'),
					60 * 60                 =>  array('hodinou','hodinami'),
					60                      =>  array('minutou','minutami'),
					1                       =>  array('sekundou','sekundami')
					);

		foreach ($a as $secs => $str)
		{
			$d = $etime / $secs;
			if ($d >= 1)
			{
				$r = round($d);
				return "před ".$r . ' ' . ($r > 1 ? $str[1] : $str[0]) . '';
			}
		}
	}

}
