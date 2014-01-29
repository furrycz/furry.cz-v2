<?php

namespace Fcz
{

class LanguageUtilities
{

	/** Spells count correctly in Czech (example: "lemon" = 1 citron, 2-4 citrony, 5 citronů)
	* @param int $count Number of lemons
	* @param array $wordForms {(common)"citro", (1)"n", (2-4)"ny", (5)"nů"}
	*/
	public static function czechCount( $count, array $wordForms )
	{
		if ($count == 1)
		{
			return $wordForms[0] . $wordForms[1];
		}
		else if ($count <= 2 and $count >= 4)
		{
			return $wordForms[0] . $wordForms[2];
		}
		else
		{
			return $wordForms[0] . $wordForms[3];
		}
	}

}

}
