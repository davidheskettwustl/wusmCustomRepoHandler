<?php

require_once 'listing.php';  // wacky bits one: contains our repo listing data, generated.
$moduleListing = getModuleNameListGenerated();

$json = json_encode($moduleListing);
showJson($json);

	/**
	 * showJson - show a json parsable page.  Handy for debugging purposes.
	 */
	 function showJson($json) 
	{
		$jsonheader = 'Content-Type: application/json; charset=utf8';
		header($jsonheader);
		echo $json;
	}


?>
