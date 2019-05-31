<?php

function getSecretKeys($prefix)
{
	// IMPORTANT:  Make this value your own custom value
	$customRepoSecretKey = 'testingkey';  // CUSTOMIZE
	
	$psecretkey = $prefix . date('YmdH') . $customRepoSecretKey;
	
	$keys['psecretkey'] = md5($psecretkey);
	
	return $keys;
}


?>


