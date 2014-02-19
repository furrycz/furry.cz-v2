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
				if($secs >= 86400 and $r>2) return Date("j.m.Y H:i", $ptime);
				return "před ".$r . ' ' . ($r > 1 ? $str[1] : $str[0]) . '';
			}
		}
	}
	
	public static function parseHTML($html){			
		$html = preg_replace_callback('/\<a href\=\"(.*)">(.*)\<\/a\>/U', function($match){
			$time=time();
			$end = explode(".",$match[1]);
			if(substr($match[1], 0, strlen("http://www.youtube.com/"))=="http://www.youtube.com/"){
			   $data = explode("?",$match[1]);
			   $data = explode("&",$data[1]);
			   $i=0;$IdVidea="";
			   while(@$data[$i]!=""){
				$polo = explode("=",$data[$i]);
				if($polo[0]=="v" and $IdVidea==""){$IdVidea=$polo[1];}
				$i+=1;
			   }
			   if($IdVidea=="")
				return '<font color=red>V adrese se nenachází id videa!<br>'.$match[0].'</font>';
			   else{
				//return '<iframe width="'.$match[1].'" height="'.$match[2].'" src="http://www.youtube.com/embed/'.$match[3].'" frameborder="0" allowfullscreen></iframe>';
				return '<div class="YoutubeBox"><iframe width="300" height="200" src="http://www.youtube.com/embed/'.$IdVidea.'?rel=0" frameborder="0" allowfullscreen style="padding-top: 6px;"></iframe><div style="padding:4px;"><a href="'.$match[1].'" title="'.$match[2].'" target="_blank">'.$match[2].'</a></div></div>';
			   }	
			}elseif(end($end)=="swf"){
				return '<div class="YoutubeBox"><a href=# onClick="this.style.display=\'none\';$(\'#flash-open-'.\Nette\Utils\Strings::webalize($match[1]).'\').css(\'display\',\'block\');return false;" style="display: block;padding: 5px 30px;width: 159px;overflow: hidden;border-radius:3px;margin: 92px auto;">Zobrazit flash animaci</a><object id="flash-open-'.\Nette\Utils\Strings::webalize($match[1]).'" type="application/x-shockwave-flash" data="'.$match[1].'" width="300" height="200" id="flashcontent" style="display:none;visibility: visible; height: 200px; width: 300px;padding-top: 6px;padding-bottom: 6px;"></object><div style="padding:4px;"><a href="'.$match[1].'" title="'.$match[2].'" target="_blank">'.$match[2].'</a></div></div>';
			}else{
				return $match[0];
			}		
		}, $html);
		return $html;
	}

}
