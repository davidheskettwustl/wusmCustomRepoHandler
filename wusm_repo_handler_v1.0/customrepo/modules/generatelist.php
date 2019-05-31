<?php
	// create a static data list to use in the download service handler.
	//  fixes the listing.php file with the available EM items.
	//
	define("NOAUTH", true);  // helps shib get through
	
	// pull in our configuration data
	require_once 'configuration.php';
	//
	$configData = getRepoConfigurationData();
	//
	$customRepoProjectId = $configData['repoProjectId'];
	
	// Not configured yet, message user and stop.
	if ($customRepoProjectId == 0) {
		echo 'You will need to set up the configuration with the PROJECT ID';
		echo '<hr>';
		echo 'Edit the configuration.php file and fill in the PROJECT ID ';
		exit;
	}

	// FindRedcapVersionPath.php
	require_once 'FindRedcapVersionPath.php';
	// get some basic REDCap set up going
	//
	// we could perhaps yank the dir path dynamically but I guess for now that is not really needed.
	//
	$here = dirname(__FILE__);
	$herePieces = explode(DIRECTORY_SEPARATOR.'modules', $here);
	$dirslist = explode(DIRECTORY_SEPARATOR, $herePieces[0]);
	$repoRootLocation = $dirslist[(count($dirslist) - 1)];

	// EX: /consortium/modules    /customrepo/modules
	$server_repo_redcap_version = findRedcapVersionPath(DIRECTORY_SEPARATOR . $repoRootLocation . DIRECTORY_SEPARATOR . 'modules', true);  // REPO server REDCap version

	// Get REDCap functions and db database handling amongst other things.
	// '../../redcap_v'. $server_repo_redcap_version . '/Config/init_global.php'
	$initglobal = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'redcap_v'. $server_repo_redcap_version . DIRECTORY_SEPARATOR .'Config'.DIRECTORY_SEPARATOR.'init_global.php';

	require_once $initglobal;

	//define('CUSTOM_REPO_PROJECT_ID_DEFINE', $customRepoProjectId); 
		
	
	require_once 'DownloadServiceDataGrabber.php';
	
	$dlhelper = new DownloadServiceDataGrabber();
	$list = $dlhelper->getModuleNameListDatabase($customRepoProjectId);
	
	
	$nl = "\n";
	
	$phpstart = '<' . '?' . 'php';
	$phpstart .= $nl;
	
	$phpend = '?' . '>';
	$phpend .= $nl;
	
	
	$str = '';
	$str .= '';
	
	$str .= 'function getModuleNameListGenerated()';
	$str .= $nl;
	$str .= '{';
	$str .= $nl;
	
	$str .= '	$modules =  null;';
	$str .= $nl;

		if ($list) {
			foreach ($list as $key => $val) {
				$module_id             = $key;
				$module_short_name     = $val['module'];
				$module_system_version = $val['version'];
	
				$str .= '	$modules[' . $module_id . '] = array(' ."'". 'module' ."'" .' => ' ."'" . $module_short_name ."'".', '."'".'moduleName'."'".' => '."'". $module_short_name . '_v' . $module_system_version . "'".', '."'" . 'version' ."'".' => '."'". $module_system_version ."'".');';
				$str .= $nl;
			}
		}
	
	$str .= $nl;
	$str .= '	return $modules;';
	
	$str .= $nl;
	$str .= '}';
	$str .= $nl;
	
	$datafile = 'listing.php';
	
	$fp = fopen($datafile, 'w');
	
	if ($fp) {
		
		fwrite($fp, $phpstart);
		fwrite($fp, $str);
		fwrite($fp, $phpend);
		
		fclose($fp);
	}

echo 'Listing generated. Done.';
?>
