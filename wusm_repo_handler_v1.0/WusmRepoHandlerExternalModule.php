<?php
/// A support module to help create custom repo handling.
/**
 *  WusmRepoHandlerExternalModule 
 *  - CLASS for .
 *    + key functions
 *  - The project adds . 
 *  
 *  
 *  - WUSM - Washington University School of Medicine. 
 * @author David L. Heskett
 * @version 1.0
 * @date 20181205
 * @copyright &copy; 2018 Washington University, School of Medicine, Institute for Infomatics <a href="https://informatics.wustl.edu/">Institute for Informatics (I2)</a>
 * @todo .
 */

namespace WashingtonUniversity\WusmRepoHandlerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

use \REDCap;
use \HtmlPage;


class WusmRepoHandlerExternalModule extends AbstractExternalModule
{
	private $version;
	private $projectId;
	private $customRepoLocation;
	private $customRepoRootDirPathDir;
	private $customRepoSecretKey;
	private $infoProjectId;  // info_project_id
	private $generatedKey;

	CONST MODULE_VERSION = '1.0';
	CONST MODULE_VERSION_DATE  = '20190131';
	CONST MODULE_CREATION_DATE = '20181205';

	CONST PROJECT_NAME = 'WUSM Repo Handler EM';

	//CONST INSTITUTION_REPO_SERVER_PATH = DS.'consortium'.DS.'modules'.DS;  // consider changing this path, such that can be different than the vanderbilt repo.
	//CONST INSTITUTION_REPO_SERVER_PATH = DS.'customrepo'.DS.'modules'.DS;    // custom repo path, can be different than the vanderbilt repo path (/redcap/consortium/modules/).
	CONST INSTITUTION_REPO_SERVER_DEFAULT_BASE_PATH = 'customrepo'; // base custom repo path, can be different than the vanderbilt repo path (/redcap/consortium/modules/).
	
	CONST SECRET_KEY_PREFIX = 'keepItSecretKeepiTSafe' . 'andsome rea lly lo ng sTory HereAbout A Hobbit And Some Wizard Guy Named Gandalf That Went OutTraveling Distant Lands And Eventually Meltingthe one ring ina volcano.';

	CONST STATUS_ERR_NOEM          = '0';
	CONST STATUS_ERR_NOWRITE       = '1';
	CONST STATUS_ERR_NOEXTRACT     = '2';
	CONST STATUS_ERR_NOEXTRACT2    = '3';
	CONST STATUS_ERR_ALREADYEXISTS = '4';
	CONST STATUS_SUCCESS           = '99';


	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * - set up our defaults.
	 */
	function __construct($pid = null)
	{
		parent::__construct();
		
		$this->version = WusmRepoHandlerExternalModule::MODULE_VERSION;
		
		$this->projectId = null;
		
		// project ID of project 
		$projectId = (($pid) ? $pid : (isset($_GET['pid']) ? $_GET['pid'] : 0));
		
		if ($projectId > 0) {
			$this->projectId = $projectId;
		}
				
		$this->methodInitialize();
	}

	/**
	 * methodInitialize - initializations here.
	 */
	public function methodInitialize()
	{
		$this->customRepoLocation = null;
		$this->generatedKey = 'somethingnotblankandvaried' . date('YmdHis') . rand(1,10000);  // this is so the key is not blank
		$this->customRepoSecretKey = $this->generatedKey;
		
		// ***** Load CONFIG *****
		$this->loadConfig($this->projectId);
		// ******************************
	}
		
	/**
	 * loadConfig - configuration settings here.
	 */
	public function loadConfig($projectId = 0) 
	{
		(($projectId > 0) ? $this->loadProjectConfig($projectId) : $this->loadProjectConfigDefaults());

		$this->loadSystemConfig();

		$this->debugLogFlag = ($this->debug_mode_log_project || $this->debug_mode_log_system ? true : false);
	}

	/**
	 * loadSystemConfig - System configuration settings here.
	 */
	public function loadSystemConfig() 
	{
		$this->debug_mode_log_system = $this->getSystemSetting('debug_mode_log_system');

		$customRepoLocation = $this->getSystemSetting('custom_repo_location');
		$customRepoSecretKey = $this->getSystemSetting('custom_repo_secret_key');
		
		// custom_repo_root_dir
		$customRepoRootDirPathDir = $this->getSystemSetting('custom_repo_root_dir');  // root path on server: customrepo  of  /redcap/customrepo/modules
		$customRepoRootDirPathDir = ($customRepoRootDirPathDir ? $customRepoRootDirPathDir : self::INSTITUTION_REPO_SERVER_DEFAULT_BASE_PATH);  // default if not set.
		$this->customRepoRootDirPathDir = DS . $customRepoRootDirPathDir . DS . 'modules' . DS;
		
		$this->customRepoSecretKey = ($customRepoSecretKey ? $customRepoSecretKey : $this->generatedKey);
		
		$this->customRepoLocation = ($customRepoLocation ? $customRepoLocation : null);

		$this->infoProjectId = $this->getRepoListingProjectId();
	}

	/**
	 * loadProjectConfig - Project configuration settings here.
	 */
	public function loadProjectConfig($projectId = 0) 
	{
		// this will not have any project specific data.
	}

	/**
	 * loadProjectConfigDefaults - set up our defaults.
	 */
	public function loadProjectConfigDefaults()
	{
		$this->debug_mode_log_project   = false;
	}

	/**
	 * getRepoListingProjectId - get the project ID for the Repo Listing information.
	 * @return project ID.
	 */
	public function getRepoListingProjectId()
	{
		$infoProjectId = $this->getSystemSetting('info_project_id');

		return $infoProjectId;
	}

	/**
	 * getModuleDirs - get list of external module dir names.
	 * @return array list of EM directories.
	 *
	 * NOTE: it is possible that scanning of alternate directories could be a nice addition, 
	 * similar to getAltModuleDirectories(), getModuleDirectories(), getModulesInModuleDirectories().
	 * although, not essential at this time. 
	 */
	public function getModuleDirs($dir = './../../modules/')
	{
		$dh = opendir($dir);
		$dirname = readdir($dh);
		
		while (false !== ($dirname = readdir($dh))) {
			if ($dirname != '..' && $dirname != '.' && $dirname != '.DS_Store') {
				$list[] = $dirname;
			}
		}
		
		sort($list); // fix some version compare issues if directories not list out handily.

		return $list;
	}

	/**
	 * utilityParseVersionFromModuleName - given a module name (load_visit_data_v1.2.1) return the version of that module.
	 * @return string version.  1.2.1
	 */
	public function utilityParseVersionFromModuleName($moduleName)
	{
		$moduleVersion = '';
		
		$data = explode('_v', $moduleName);
		
		$size = count($data) - 1;
		
		$moduleVersion = $data[$size];  // get the last one, that must be the "_v1.2.1" or 1.2.1

		return $moduleVersion;
	}

	/**
	 * makeCustomInstituteExternalModuleButton - make the custom form html snippet to use.
	 * @return string custom form html snippet.
	 */
	public function makeCustomInstituteExternalModuleButton()
	{
		$theCustomButton = '';
		
		$allowedToView = SUPER_USER && !isset($_GET['pid']); // must be a super user and not be in a project context

		if ($allowedToView) { 
			$customRepoLocation = $this->customRepoLocation;  // target REPO server

			if ($customRepoLocation) {
				$prefix = self::SECRET_KEY_PREFIX;
				$customRepoSecretKey = $this->customRepoSecretKey;
				$secretkey     = md5($prefix . date('YmdH') . $customRepoSecretKey);
				//$secretkey     = htmlspecialchars(password_hash($prefix . date('YmdH') . $customRepoSecretKey, PASSWORD_BCRYPT), ENT_QUOTES);  // bcrypt is random. we need repeatable on both sides.
		
				$userId        = USERID;
				$userFirstName = $GLOBALS['user_firstname'];
				$userLastName  = $GLOBALS['user_lastname'];
				$userName      = htmlspecialchars($userFirstName." ".$userLastName, ENT_QUOTES);
				$userEmail     = $GLOBALS['user_email'];
				$userEmail     = htmlspecialchars($userEmail, ENT_QUOTES);
				$serverName    = SERVER_NAME;
				$phpVersion    = PHP_VERSION;
				$institution   = $GLOBALS['institution'];
				$institution   = htmlspecialchars($institution, ENT_QUOTES);
				$redcapVersion = REDCAP_VERSION;
				$controlCenter = htmlspecialchars(APP_URL_EXTMOD . 'manager' . DS . 'control_center.php', ENT_QUOTES);
	
				$moduleDirs = $this->getModuleDirs();  // get listing of the EM module dirs, basically the EMs and their versions are the directory filenames.  example: "some_module_name_v1.0"

				$callingEmManagerId = $_GET['id'];
				$callingEmManagerIdPrefix = $_GET['prefix'];
	
				//$webrootpath = str_replace(DS, '', APP_PATH_WEBROOT);
				$webrootpath = ltrim(APP_PATH_WEBROOT, DS);  // FIXME: for DEV
				$csslink = '<link rel="stylesheet" type="text/css" href="'.DS.$webrootpath.DS.'ExternalModules'.DS.'manager'.DS.'css'.DS.'style.css">';
				$theCustomButton .= $csslink;
				
				$textHere = 'Download new module from ';
				$downloadTagBanner .= $textHere . ' (' . $institution . ') CUSTOM REDCap Repo';
	
				$repoServerPath = $this->customRepoRootDirPathDir;  // INSTITUTION_REPO_SERVER_PATH
	
				// action:  server/consortium/modules/index.php
				//
				$theCustomButton .= '<form id="download-new-mod-form" action="'          . $customRepoLocation . $repoServerPath . 'index.php" method="post" enctype="multipart/form-data">';
				$theCustomButton .= '<input type="hidden" name="user" value="'           . $userId             . '">';
				$theCustomButton .= '<input type="hidden" name="name" value="'           . $userName           . '">';
				$theCustomButton .= '<input type="hidden" name="email" value="'          . $userEmail          . '">';
				$theCustomButton .= '<input type="hidden" name="server" value="'         . $serverName         . '">';
				$theCustomButton .= '<input type="hidden" name="referer" value="'        . $controlCenter      . '">';
				$theCustomButton .= '<input type="hidden" name="php_version" value="'    . $phpVersion         . '">';
				$theCustomButton .= '<input type="hidden" name="redcap_version" value="' . $redcapVersion      . '">';
				$theCustomButton .= '<input type="hidden" name="institution" value="'    . $institution        . '">';
	
				$theCustomButton .= '<input type="hidden" name="measurement" value="'    . $secretkey        . '">';  // this is a cheap security measure.   secretkeypasscode  changed to measurement
				$theCustomButton .= '<input type="hidden" name="callingEmManagerId" value="'    . $callingEmManagerId        . '">';  // this is so we can make a back link dynamic for the download.php service here.
				$theCustomButton .= '<input type="hidden" name="callingEmManagerIdPrefix" value="'    . $callingEmManagerIdPrefix        . '">';  // this is so we can make a back link dynamic for the download.php service here.
	
				// method getModulesInModuleDirectories is newer version of REDCap above 8.0.3, unknown exact version this addition was made.
				//foreach (\ExternalModules\ExternalModules::getModulesInModuleDirectories() as $thisModule) {
				// ** just above is a note for reference **
				// we may want this in the future.  it scans other EM module directories, there currently can be three?  /modules/  /external_modules/ ?  and ? (alt module directories)
				// 
				// right now, we are just going for the base main common one.  /modules/
				//
				foreach ($moduleDirs as $key => $thisModule) {  // this will be good enough in our case
					$theCustomButton .= '<input type="hidden" name="downloaded_modules[]" value="' . $thisModule . '">';
				}
	
				$charImageButtonDownloadClass = $this->getButtonDownloadClass($redcapVersion);

				$buttonSpanDownload .= '<span class="'.$charImageButtonDownloadClass.'" aria-hidden="true"></span>';  // makes the fancy down arrow downloader looking button image character
	
				$theCustomButton .= '<button class="btn btn-primary btn-sm" type="submit" form="download-new-mod-form" value="Submit">' . $buttonSpanDownload . ' ' . $downloadTagBanner . '</button>';
				$theCustomButton .= '</form>';
	
				$theCustomButton .= '<br>';
			}
		}
		
		return $theCustomButton;
	}
	
	/**
	 * isGoodModuleId - do some simple validation for module ID.
	 * @return bool true false.
	 */
	public function isGoodModuleId($moduleId = null)
	{
		$moduleId = filter_var($moduleId, FILTER_SANITIZE_NUMBER_INT);
		
		$moduleId = db_escape($moduleId);
		
		if (empty($moduleId) || !is_numeric($moduleId)) return false;
		
		return (int)$moduleId;
	}

	/**
	 * isEnsuredUser - Ensure user is super user unless we want to bypass this check.  bypass = true will get through the check.
	 * @return bool true false.
	 */
	public function isEnsuredUser($bypass)
	{
		// Ensure user is super user
		return ((!$bypass && (!defined("SUPER_USER") || !SUPER_USER)) ? false : true);
	}

	/**
	 * entryEmDownloadForModule - note the download activity for the module.
	 */
	public function entryEmDownloadForModule($module_id, $moduleFolderName)
	{
		// redcap_external_modules_downloads
		//
		// Add row to redcap_external_modules_downloads table
		$sql = "INSERT INTO redcap_external_modules_downloads (module_name, module_id, time_downloaded) 
				VALUES ('" . db_escape($moduleFolderName) . "', '" . db_escape($module_id) . "', '" . NOW . "')
				ON DUPLICATE KEY UPDATE 
				module_id = '" . db_escape($module_id) . "', time_downloaded = '" . NOW . "', time_deleted = null";
		
		db_query($sql);  // TODO: reactivate once we have this all properly working
	}

	/**
	 * makeBackToStandardEmManagerButton - build the button html.
	 */
	public function makeBackToStandardEmManagerButton($webroot = null, $redcapVersionPath = null)
	{
		if ($webroot == null) {
			$webroot   = APP_PATH_WEBROOT_FULL;
		}
		
		if ($redcapVersionPath == null) {
			$redcapVersionPath = APP_PATH_WEBROOT;
			$redcapVersionPath = ltrim($redcapVersionPath, DS);
		}

		// build the path to the standard manager page
		$vpieces = explode('redcap_v', $redcapVersionPath);
		$version = $vpieces[1];
		$mainManagerPageLink = $webroot . 'redcap_v' . $version . 'ExternalModules'.DS.'manager'.DS.'control_center.php';

		// Explicit link to Standard REDCap Extenal Module Manager page
		//
		// since all the user expected module management is not present on the custom EM page, give them something to get back to that which they are familiar with.
		// plus, all that management utility gets complex and we do not want to get into the game of replicating that, let alone repeating things that likely would break since our context is different.
		//
		//
		$pagemockhtmlcode .= '<div id="back_to_standard_external_module_manager_page">';
		$pagemockhtmlcode .= '<hr>';
		$pagemockhtmlcode .= '<p>';
		$pagemockhtmlcode .= '<h4>';
		$pagemockhtmlcode .= 'To Manage your External Modules, <br>please go to the standard Manager page here: ';
		$pagemockhtmlcode .= '<span id="back_to_standard_external_module_manager_page_button_style">';
		$pagemockhtmlcode .= '<a href="' . $mainManagerPageLink . '" style="font-size:18px; color:#00AA00;">External Modules Manager' . '</a>';
		$pagemockhtmlcode .= '</span>';
		$pagemockhtmlcode .= '</h4>';
		$pagemockhtmlcode .= '</p>';
		$pagemockhtmlcode .= '</div>';
		
		return $pagemockhtmlcode;
	}

	/**
	 * handleErrMsg - show the button page.
	 */
	public function handleErrMsg($errMsg = null, $buttonBackToStandardEmManager = null, $returnCode = null)
	{
		$htmlErrMsgStr = '<hr>Error: ' . $errMsg . '<hr>';
		$htmlErrMsgStr .= $buttonBackToStandardEmManager;
		$this->viewHtmlControl($htmlErrMsgStr, 'control');
		
		return $returnCode;
	}

	// NOTE: the core code that this is derived from uses a hard coded server path. This method downloadModule makes it more adaptable.
	// References:
	// 			define("APP_URL_EXTMOD_LIB", "https://redcap.vanderbilt.edu/consortium/modules/");
	//
	//		$moduleFolderName = http_get(APP_URL_EXTMOD_LIB . "download.php?module_id=$module_id&name=1");
	//
	// http://wwwrepo:8888/consortium/modules/index.php
	//		$moduleFolderName = http_get('' . "download.php?module_id=$module_id&name=1");
	//
	// http://wwwrepo:8888/consortium/modules/index.php
	//		$moduleFolderName = http_get('http://wwwrepo:8888/consortium/modules/' . "downloadservice.php?module_id=$module_id&name=1");

	/**
	 * downloadModule - this emulates ExternalModules::downloadModule however changes one critical path, APP_URL_EXTMOD_LIB, to our custom repo server.
	 *  (also extensive changes, however, essentially very similar to original)
	 *  remember, we are coming from our client side, "download.php" file page (probably should rename that, is it confusing?) which
	 *  replicates in part, ExternalModules/ajax/download-module.php without all the extra ajax actions.
	 *
	 * @return string codes Not really used in the caller download.php though as dealt with here using the viewHtml message system.
	 */
	public function downloadModule($module_id = null, $bypass = false, $sendUserInfo = false) 
	{
		// why zero text?
		// why the string numbers.   states in download handler ajax javascript, ExternalModules/manager/templates/globals.php, function  
		// (but, do we care.... now? No. not really, unless we get all ajax fancy. Leave that for a future enhancement, we may not really need.)
		// NOTE: if you can reference the original Vanderbilt repo server code Rob wrote, these things make more sense as to why.
		//
		//
		// 0, 1, 2,3, 4 
		$statusType_Err_NoEm          = self::STATUS_ERR_NOEM;          // '0';
		$statusType_Err_NoWrite       = self::STATUS_ERR_NOWRITE;       // '1';
		$statusType_Err_NoExtract     = self::STATUS_ERR_NOEXTRACT;     // '2';
		$statusType_Err_NoExtract2    = self::STATUS_ERR_NOEXTRACT2;    // '3';
		$statusType_Err_AlreadyExists = self::STATUS_ERR_ALREADYEXISTS; // '4';
		$statusType_Success           = self::STATUS_SUCCESS;           // '99';
		 
		$buttonBackToStandardEmManager .= $this->makeBackToStandardEmManagerButton();

		// Ensure user is super user
		if (!$this->isEnsuredUser($bypass)) {
			$errMsg = '0 = Module does not exist in library (001 Not correct user privelige)';
			return $this->handleErrMsg($errMsg, $buttonBackToStandardEmManager, $statusType_Err_NoEm);
		}

		// Set modules directory path
		$modulesDir = dirname(APP_PATH_DOCROOT).DS.'modules'.DS;

		// Validate module_id
		$module_id = $this->isGoodModuleId($module_id); // this will clean up and sanitize data as well as check if have good data to use
		
		// 'http://wwwrepo:8888/consortium/modules/'   or    'http://wwwrepo:8888/customrepo/modules/'
		$serverRepo = $this->customRepoLocation;
		$repoUrl = $serverRepo . $this->customRepoRootDirPathDir; // '/consortium/modules/'; // custom REPO EM Server Url location    INSTITUTION_REPO_SERVER_PATH

		$secretToken = md5(date('YmdH').'maKe!tg0to11'.date('m H.Y d'));  // make it go to 11
		//$secretToken = md5(date('YmdH'));  // make it go to 11

	  // getModuleName
	  //
		$moduleFolderName = http_get($repoUrl . 'downloadservice.php?module_id=' . $module_id . '&name=1' . '&token=' . $secretToken);

		$moduleFolderDir = null;
		if ($moduleFolderName) {
			$moduleFolderDir = $modulesDir . $moduleFolderName . DS;
		}
		
		if ($moduleFolderDir == null) {
			$errMsg = '0 = No Dirs No zips (002 Module Folder Directory)';
			
			return $this->handleErrMsg($errMsg, $buttonBackToStandardEmManager, $statusType_Err_NoEm);
		}

		// File exists: you have the external module already
		if (file_exists($moduleFolderDir) && is_dir($moduleFolderDir)) {
			$errMsg = '4 = Files Already exist (002 Module Folder Directory) [' . $moduleFolderDir . ']';
			
			return $this->handleErrMsg($errMsg, $buttonBackToStandardEmManager, $statusType_Err_AlreadyExists);
		}

		$postParams = null;

		// Send user info?
		if ($sendUserInfo) {
			$postParams = array(
				'user'        => USERID, 
				'name'        => $GLOBALS['user_firstname'] . ' ' . $GLOBALS['user_lastname'], 
				'email'       => $GLOBALS['user_email'], 
				'institution' => $GLOBALS['institution'], 
				'server'      => SERVER_NAME
			);
		} else {
			$postParams = array(
				'institution' => $GLOBALS['institution'], 
				'server'      => SERVER_NAME
			);
		}

		// NOTE: http_post, http_get  built in REDCap functions.  even though a PHP function http_get exists, they are overriding functionality.

		// Call the module download service to download the module zip
		$moduleZipContents = http_post($repoUrl . 'downloadservice.php?module_id=' . $module_id . '&token=' . $secretToken, $postParams);
		
		// Errors?
		if ($moduleZipContents == 'ERROR') {  // original Vanderbilt repo server give ERROR http_post as a possible return. left in place to be compatible. (may yet use that original with some changes).
			// 0 = Module does not exist in library
			$errMsg = '0 = Module does not exist in library (003 Module Zip Contents)';
			return $this->handleErrMsg($errMsg, $buttonBackToStandardEmManager, $statusType_Err_NoEm);
		}

		if ($moduleZipContents == false) {
			// 0 = Module does not exist in library
			$errMsg = '0 = Module does not exist in library (004 Module Zip Contents)';
			return $this->handleErrMsg($errMsg, $buttonBackToStandardEmManager, $statusType_Err_NoEm);
		}

		// ***** ***** *****
		// ***** Zip handler section
		// ***** ***** *****
		//
		// Place the file in the temp directory before extracting it
		$filename = APP_PATH_TEMP . date('YmdHis') . "_externalmodule_" . substr(sha1(rand()), 0, 6) . ".zip";
		if (file_put_contents($filename, $moduleZipContents) === false) {
			// 1 = Module zip couldn't be written to temp
			$errMsg = '1 = Module zip couldn\'t be written to temp';
			return $this->handleErrMsg($errMsg, $buttonBackToStandardEmManager, $statusType_Err_NoEm);
		}

		// Extract the module to /redcap/modules
		$zip = new \ZipArchive;
		$zipReturnCode = $zip->open($filename);
		
		if ($zipReturnCode !== TRUE) {
			$errStr = '<hr>';
			$errStr .= 'moduleFolderName : ' . $moduleFolderName ;
			$errStr .= '<hr>';
			$errStr .= 'file: ' . $filename;
			$errStr .= '<hr>';
			$errStr .= 'Open zip error code: '; 
			$errStr .= $zipReturnCode; 

			return $this->handleErrMsg($errMsg, $buttonBackToStandardEmManager, $statusType_Err_NoExtract);
		}
		
		// First, we need to rename the parent folder in the zip because GitHub has it as something else
		$i = 0;
		while ($item_name = $zip->getNameIndex($i)){
			$item_name_end = substr($item_name, strpos($item_name, "/"));
			
			// cleans out the mac os junk if there is any. Normal operation should not have to be concerned with this.  harmless if remains here.
			if (substr_count($item_name, '__MACOSX')) {
				$zip->deleteIndex($i);  // remove the oddity?
				$i++;
				continue;  // skip the odd mac os items?
			}
			$zip->renameIndex($i++, $moduleFolderName . $item_name_end);
		}
		$zip->close();
		
		// Now extract the zip to the modules folder
		$zip = new \ZipArchive;
		if ($zip->open($filename) === TRUE) {
			$zip->extractTo($modulesDir);
			$zip->close();
		}
		
		// Remove temp file
		unlink($filename);
		
		// Now double check that the new module directory got created
		if (!(file_exists($moduleFolderDir) && is_dir($moduleFolderDir))) {
			$errMsg = '2 = File exists and module directory exists';

			return $this->handleErrMsg($errMsg, $buttonBackToStandardEmManager, $statusType_Err_NoExtract2);
		}
		// ***** ***** *****
		// ***** Zip handler section
		// ***** ***** *****
		
		// Add row to redcap_external_modules_downloads table
		$this->entryEmDownloadForModule($module_id, $moduleFolderName);

		// Log this event
		if (!$bypass) {
			$logMsg = 'CUSTOM Repo Download EM (' . $moduleFolderName . ') : ' . date('Y m d H i s');
			REDCap::logEvent($logMsg);
		}

		// Give success message
		$htmlMsgStr = '<div id="custom_em_download_success" class="clearfix"><div class="pull-left"><img src="' . APP_PATH_IMAGES . 'check_big.png"></div><div class="pull-left" style="width:360px;margin:8px 0 0 20px;color:green;font-weight:600;">The module was successfully downloaded to the REDCap server, and can now be enabled.</div></div>';
		$htmlMsgStr .= $buttonBackToStandardEmManager;
		$this->viewHtmlControl($htmlMsgStr, 'control');
		return $statusType_Success;
	}

	/**
	 * getButtonDownloadClass - adjust for different icon char font changes. Font type changed in 8.7.0 changing the download arrow widget character font used.
		
		
		 redcap_v8.0.3/Resources/css/style.css"
		 .glyphicon { font-family: 'Glyphicons Halflings' !important; }
		
		 <span class="glyphicon glyphicon-save" style="font-size:14px;"></span>
		
		 Changed somewhere along the line which download arrow widget character font used.
		
		 redcap_v8.8.0/Resources/css/style.css"
				.fa, .far, .fas {
		    font-family: "Font Awesome 5 Free" !important;
			}
		
		 <span class="fas fa-download" aria-hidden="true"></span>
		
	 */
	public function getButtonDownloadClass($redcapVersion = '8.0.0')
	{
		// where the version change for the font changed: glyphicon last in 8.6.5 changed to fas in 8.7.0
		$charImageButtonDownloadClass = ($redcapVersion <= '8.6.5' ? 'glyphicon glyphicon-save' : 'fas fa-download'); 
		
		return $charImageButtonDownloadClass;
	}

	/**
	 * geRepoPageToDisplay - get some html mock page data.
	 */
	public function geRepoPageToDisplay()
	{
		$webroot           = APP_PATH_WEBROOT_FULL;
		$redcapVersionPath = APP_PATH_WEBROOT;
		$redcapVersionPath = ltrim($redcapVersionPath, DS);
		$imagePath         = APP_PATH_IMAGES;
		//$imagePath         = ltrim($imagePath, DS);
		$imagePath         = 'redcap'.ltrim($imagePath, 'redcap'. DS);  // FIXME: for DEV

		// 
		$externalModuleImagePath = $webroot . $imagePath . '..'.DS.'..'.DS.'ExternalModules'.DS.'images'.DS.'';
		
		$specialImage = 'puzzle_medium.png';  // EM images dir (small, medium, big)
		
		$pagemockhtmlcode = '';
		$pagemockhtmlcode .= '<h4 style="margin-top:0;" class="clearfix">';
		$pagemockhtmlcode .= '<div id="control_center_custom_repo_wrapper" style="padding-left:20px; width:100%;" class="col-xs-12 col-sm-8 col-md-9">';

		$pagemockhtmlcode .= '<div class="pull-left">';
		$pagemockhtmlcode .= '<img src="' . $externalModuleImagePath . $specialImage . '">';
		$pagemockhtmlcode .= ' External Modules - CUSTOM External Module Repo';
		$pagemockhtmlcode .= '</div>';
		
		$pagemockhtmlcode .= '<br>';
		$pagemockhtmlcode .= '<br>';


		$pagemockhtmlcode .= '<div id="custombutton">';
		$button = $this->makeCustomInstituteExternalModuleButton();
		$pagemockhtmlcode .= $button;
		$pagemockhtmlcode .= '</div>';

		$pagemockhtmlcode .= '</div>';
		$pagemockhtmlcode .= '</h4>';

		// Explicit link to Standard REDCap Extenal Module Manager page
		//
		// since all the user expected module management is not present on the custom EM page, give them something to get back to that they are familiar with.
		// plus, all that management utility gets complex and we do not want to get into the game of replicating that, let alone repeating things that likely would break since our context is different.
		//
		$pagemockhtmlcode .= $this->makeBackToStandardEmManagerButton($webroot, $redcapVersionPath);
		
		return $pagemockhtmlcode;
	}

	/**
	 * displayExternalModulesPage - show the button page.
	 */
	public function displayExternalModulesPage()
	{
		$repoPageElements = $this->geRepoPageToDisplay();
		
		$html = '';

		$html .= '<hr>';
		$html .= $repoPageElements;
		$html .= '<hr>';

		$this->viewHtmlControl($html, 'control');
	}

	/**
	 * viewHtml - the front end part, display what we have put together.
	 */
	public function viewHtml($msg = 'view', $flag = '')
	{
		$HtmlPage = new HtmlPage(); 

		// project
		if ($flag == 'project') {
			$HtmlPage->ProjectHeader();
		  echo $msg;
			$HtmlPage->ProjectFooter();
			return;
		} 
		
		// system
		$HtmlPage->setPageTitle($this->projectName);
		$HtmlPage->PrintHeaderExt();
	  echo $msg;
		$HtmlPage->PrintFooterExt();
	}
	
	/**
	 * viewHtmlControl - the front end part, display what we have put together. This method has an added feature for use with the control center, includes all the REDCap navigation.
	 */
	public function viewHtmlControl($msg = 'view', $flag = '')
	{
		$HtmlPage = new HtmlPage(); 

		switch ($flag) {
			case 'project':
				$HtmlPage->ProjectHeader();
			  echo $msg;
				$HtmlPage->ProjectFooter();
				break;

			case 'control':
				if (!SUPER_USER) {
					redirect(APP_PATH_WEBROOT); 
				}
	
				global $lang;  // this is needed for these two to work properly
				include APP_PATH_DOCROOT . 'ControlCenter/header.php';
			  echo $msg;
				include APP_PATH_DOCROOT . 'ControlCenter/footer.php';
				break;

			default:  // system
				$HtmlPage->setPageTitle($this->projectName);
				$HtmlPage->PrintHeaderExt();
			  echo $msg;
				$HtmlPage->PrintFooterExt();
				break;
		}
	}

} // *** end class

?>