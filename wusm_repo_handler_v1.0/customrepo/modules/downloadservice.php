<?php

$flagAllowedAccess = false;

if (isset($_GET['token'])) {
	$token = $_GET['token'];
	$secretToken = md5(date('YmdH').'maKe!tg0to11'.date('m H.Y d'));  // make it go to 11

	if ($token == $secretToken) {
		$flagAllowedAccess = true;
	}
}

if (!$flagAllowedAccess) {
	exit;  // Denied access you have not provided correct token
}

$nameFlag = false;
if (isset($_GET['name'])) {
	$val = $_GET['name'];
	if ($val == 1) {
		$nameFlag = true;
	}
}

require_once 'DownloadServiceHandler.php';
require_once 'listing.php';  // wacky bits one: contains our repo listing data, generated.
$moduleListing = getModuleNameListGenerated();

$dlhelper = new DownloadServiceHandler();

$dlhelper->feedModuleListing($moduleListing);  // wacky bits two.  we've pulled a generated listing and are using that data to set data in the class object to use.

// Check Module ID
$moduleId = null;

if (isset($_GET['module_id'])) {
	$val = $_GET['module_id'];
	$moduleId = $dlhelper->filterModuleId($val);
	
	if ($moduleId == null) {
		exit;  // we always need moduleId or we are done
	}
}
// *** end check module ID ***

// Check name  getName function really
if ($nameFlag) {
	$moduleName = $dlhelper->getName($moduleId);
	
	echo $moduleName;
	exit;
}
// *** end check name: normally ends here ***
//
// *** if we proceed to here, we are getting the zip file and handing that back ***
//

	// Check zip  getZip functionality zip downloading action
	// get Module Zip
	// standard process is to get module zip, given module id
  $zipName = $dlhelper->getZip($moduleId);
  
  $zipPath = '';
  $zipPath = dirname(__FILE__);
  
  $zipPath .=  DIRECTORY_SEPARATOR . 'filesarea' . DIRECTORY_SEPARATOR;
	
	// path and file
	$filepath = $zipPath . $zipName;

  // Process download
  if(file_exists($filepath)) {
	  header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename='.$zipName);
		header('Content-Length: ' . filesize($filepath));
		readfile($filepath);
	}
	
// ****************************************
// ****************************************
// ****************************************
// ****************************************

// ****************************************

?>
