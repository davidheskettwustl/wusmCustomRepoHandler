<?php
define("NOAUTH", true);

	// This intends to simulate what this page does:  https://redcap.vanderbilt.edu/consortium/modules/index.php
	//
	//
	
	// NOTE: due to early nature of processing here, (configurationKeys, configuration), are primitive, not in database or other type configuration files.
	// goal here is to short circuit processing the more advanced stuff if our basic protection is not present.
	// you can't get in if you don't have the basic creditials, and higher level processing is not even attempted
	// this is probably fairly crude, however, should be effective enough to keep out unwanted activity.
	
	$prefix = 'keepItSecretKeepiTSafe' . 'andsome rea lly lo ng sTory HereAbout A Hobbit And Some Wizard Guy Named Gandalf That Went OutTraveling Distant Lands And Eventually Meltingthe one ring ina volcano.';
	
	// ************************************************** 
	// Security section END
	// ************************************************** 
	//
	// This section acts as a login protection and 
	// security protection (simple and crude, could use refinements)
	//
	$pval = null;
	if (isset($_POST['measurement'])) {  // secretkeypasscode  named as measurement to hide purpose of item
		$pval = $_POST['measurement'];
	}

	require_once 'configurationKeys.php';
	$keys = getSecretKeys($prefix);
	$psecretkey = $keys['psecretkey'];
	
	$flagProceed = false;
	if ($psecretkey == $pval) {
		$flagProceed = true;
	}
	
	// use the POST secret key variant
	//
	if ($pval == '') {  // POST KEY if no key given or key is blank, stop.
		$flagProceed = false;
	}
	
	if (!$flagProceed) {
		exit;
	}
	//
	// This section acts as a login protection
	//
	// ************************************************** 
	// Security section END
	// ************************************************** 
	
	// **********
	//  Main
	// **********

	// pull in our configuration data
	require_once 'configuration.php';
	//
	$configData = getRepoConfigurationData();
	//
	$customRepoProjectId = $configData['repoProjectId'];
	
	// not configured, stop
	if ($customRepoProjectId == 0) {
		exit;
	}

	$customInstituteName = $configData['instituteName'];
	$customInstituteRepoCustomText = $configData['instituteRepoCustomText'];
	$customEmailSupport = $configData['emailSupport'];

	$activeFlag = $configData['activeFlag'];
	
	if (!$activeFlag) { // disabled the page, so stop
		exit;  // the server is turned off
	}
	//
	// end configuration handling
	$urlReferer = $_POST['referer'];
	$client_redcap_version = $_POST['redcap_version'];  // Version of REDCap on calling server coming from. // Client REDCap version

	// FindRedcapVersionPath.php
	require_once 'FindRedcapVersionPath.php';
	// get some basic REDCap set up going
	//
	// we could perhaps yank the dir path dynamically but I guess for now that is not really needed.
	//
	// dirname(__FILE__)  to get where we are dynamically.   ex: customrepo    of /etc.../redcap/customrepo/modules/
	$here = dirname(__FILE__);
	$herePieces = explode(DIRECTORY_SEPARATOR.'modules', $here);
	$dirslist = explode(DIRECTORY_SEPARATOR, $herePieces[0]);
	$repoRootLocation = $dirslist[(count($dirslist) - 1)];
	
	// DIRECTORY_SEPARATOR
	$server_repo_redcap_version = findRedcapVersionPath(DIRECTORY_SEPARATOR . $repoRootLocation . DIRECTORY_SEPARATOR . 'modules', true);  // REPO server REDCap version
	
	// Get REDCap functions and db database handling amongst other things.
	$initglobal = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'redcap_v'. $server_repo_redcap_version . DIRECTORY_SEPARATOR .'Config'.DIRECTORY_SEPARATOR.'init_global.php';
	require_once $initglobal;

	// fiddly bits  get the server and url pathing all correct
	$repoServerName    = SERVER_NAME;
	
	$webFullStr = APP_PATH_WEBROOT_FULL;
	$arr1 = explode('://', $webFullStr);
	$httpType = $arr1[0];

	$port = '';
	if (substr_count($arr1[1], ':')) {
		$arr2 = explode(':', $arr1[1]);
		$portType = $arr2[1];
		$port = ':' . rtrim($portType, '/');
	}

	$callingEmManagerId = $_POST['callingEmManagerId'];
	$callingEmManagerIdPrefix = $_POST['callingEmManagerIdPrefix'];
	
	require_once 'ExternalModuleRepoUtility.php';
	
	// Handle the REPO information 
	$repoTool = new ExternalModuleRepoUtility($customRepoProjectId);
	$repoTool->loadRepoDataStructure();  // repo EM listing
	
	$classButton = $repoTool->getButtonDownloadClass($server_repo_redcap_version);

	// link things
	// make dynamic back links
	$clientServerPort = $port; // ':8888' and such
	$linkUrlBase	    = ''.$httpType.'://'.$repoServerName.''.$port.'/redcap/redcap_v' . $server_repo_redcap_version;  //$linkUrlBase      = ''.$httpType.'://'.$repoServerName.''.$port.'/redcap_v' . $server_repo_redcap_version;
	$linkUrlBaseBack  = $urlReferer; //''.$httpType.'://'.$clientServerName.''.$clientServerPort.'/redcap_v' . $redcapVersion;

	$jsUpdateScripts = $repoTool->getJsUpdateScripts($linkUrlBase, $linkUrlBaseBack, $callingEmManagerId, $callingEmManagerIdPrefix);

//	$htmlPiece  = $repoTool->getHtmlPiece($customInstituteRepoCustomText, $customInstituteName, $customEmailSupport, $classButton);
	$htmlPiece  = $repoTool->getHtmlPiece($customInstituteRepoCustomText, $customInstituteName, $customEmailSupport, $classButton, $repoServerName, $port, $httpType);
	$tableHead  = $repoTool->getTableHead();
	$tableFoot  = '</tbody></table>';
	
	$html       = $repoTool->getHeaderHtml($server_repo_redcap_version, $repoServerName, $port, $httpType);
	$footerinfo = $repoTool->getFooterHtml($customInstituteName, $server_repo_redcap_version);
	
	
	// ************************************************** 
	// ************************************************** 
	// get the client side list of External Modules they have and the versions
	// list of their EMs as in:  wusm_parent_child_v1.0
	//
	$givenClientEmList = (isset($_POST['downloaded_modules']) ? $_POST['downloaded_modules'] : null);
	
	// convert the list into useable data
	// or if not given data exit out of our page.
	//
	if ($givenClientEmList) {
		$clientEmListing = $repoTool->buildListOfClientEms($givenClientEmList);
	} else {
		// exit
		echo '<hr>';
		echo '<br>';
		echo '<h1>No External Modules to Display</h1>';
		echo '<br>';
		echo '<hr>';
		exit;
	}
	
	// build the page and display
	// ************************************************** 
	// HTML section
	// ************************************************** 
	$nl = "\n";
	$br = '<br>';
	$hr = '<hr>';
	$htmlpage = '';
	
	$htmlpage .= $html; // header
	
	$htmlpage .= $htmlPiece;
	
	$htmlpage .= $jsUpdateScripts;
	
	$htmlpage .= $hr;
	$htmlpage .= '<h2>Listing Repo Server: ' . $repoServerName . '</h2>';
	$htmlpage .= '<h3>Your REDCap version on your client server: ' . $client_redcap_version . '</h2>';
	$htmlpage .= $hr;

	$htmlpage .= $tableHead;
	
	// main component that generates the page from the Repo data and comparison of the client EM list
	//
	$htmlpage .= $repoTool->buildStrHtml($clientEmListing, $server_repo_redcap_version);
	
	$htmlpage .= $tableFoot;
	
	$htmlpage .= '<div class="clearfix"></div>';

	$htmlpage .= $footerinfo;
	
	$htmlpage .= '<div>';
	$htmlpage .= '</body></html>';
	
	echo $htmlpage;

// ************************************************** 
// ************************************************** 
// ************************************************** 
// ************************************************** 
// ************************************************** 


// ************************************************** 
// ************************************************** 
// ************************************************** 
// 
// ************************************************** 
// ************************************************** 
// ************************************************** 

?>
