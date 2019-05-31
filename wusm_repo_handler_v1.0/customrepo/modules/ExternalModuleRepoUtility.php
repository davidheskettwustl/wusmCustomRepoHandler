<?php
/// A support module to help create hashing identification values.
/**
 *  ExternalModuleRepoUtility 
 *  - CLASS for Server Repo of External Modules handling and building a page that will dynamically build a listing with elements that allow for download and update of client site EMs.
 *    + key functions
 *      loadRepoDataStructure();  // repo EM listing data
 *
 *      * page element construction *
 *
 *      getButtonDownloadClass($redcapVersion);
 *      getJsUpdateScripts($linkUrlBase, $linkUrlBaseBack);
 *      getHtmlPiece($customInstituteRepoCustomText, $customInstituteName, $customEmailSupport, $classButton);
 *      getTableHead();
 *      getHeaderHtml($redcapVersion, $serverName, $port, $httpType);
 *      getFooterHtml($customInstituteName, $redcapVersion);
 *      buildListOfClientEms($givenClientEmList);
 *  
 *  - The project adds a customized External Module Repo in a similar fashion as the Vanderbilt site uses.  Purpose is to allow an internal use Repo to maintain distribution to your internal REDCap sites 
 *     and give you control to place your EMs on your servers the same way the standard EM manager works and avoid the issue of access barriers to server command lines to move your files or less computer savvy users.
 *     You still will need a server and place your files upon that. 
 *  
 *  
 *  - WUSM - Washington University School of Medicine. 
 * @author David L. Heskett
 * @version 1.0
 * @date 20190109
 * @copyright &copy; 2018 Washington University, School of Medicine, Institute for Infomatics <a href="https://informatics.wustl.edu/">Institute for Informatics (I2)</a>
 *  
 * @todo 1) add ability to upload the file using the project, hook to the reference for linkage.
 */

	/*
	 todo: update comment about Authors as it is slightly changed
	 
	   Structure of the Project that maintains the REPO listing information
	
			moduleActive           checkbox  0, 1   0: not available (either list it, but no download upgrade or not listed),   1: available (list it)
			
			moduleRecordId         recordId
			moduleId               text number (we want to specify perhaps this) may be it is an em id, but, we need to avoid collision with standard redcap ID numbers.
			
			moduleName             text
			
			moduleSystemVersion    number (text)
			
			moduleTagName          text label name (may be different from module path dir name)
			
			moduleGitHubLink       text  url  
			moduleDescription      text 
			moduleAuthors          text, three elements, AuthorName, AuthorPlace, AuthorEmail; one or many
			modulePublishDate      date or text yyyymmdd  (when added to repo, or date of creation or such, or submitted date)
			moduleDownloadCount    number text  (updated when downloaded, do we also tag version, so counts per version?)
			
			
			compare version they have with latest version
			moduleState  0,1,2  they dont have, they have and same version, they have and newer version exists  version 0, =, <
			                       generated from compare
	*/
	
// ************************************************** 

class ExternalModuleRepoUtility
{
	CONST GITHUB_REPO_LINK_BASE = 'https://github.com/'; // your git hub base link repo if have one.  currently not really used.

	CONST MODULE_STATE_DOWNLOAD  = 0;
	CONST MODULE_STATE_INSTALLED = 1;
	CONST MODULE_STATE_UPGRADE   = 2;
	CONST MODULE_STATE_UNKNOWN   = 99;

	CONST VERSION_IS_OLDER  = -1;
	CONST VERSION_IS_SAME   = 0;
	CONST VERSION_IS_NEWER  = 1;
	
	private $repoModulesListing;
	private $externalModuleDetails;
	private $repoListing;
	private $repoListingProjectId;
	private $gitHubRepo;
	private $redcapVersion;

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * constructor - basic initialize.
	 */
	function __construct($repoListingProjectId = null)
	{
		$this->redcapVersion = null;
		$this->repoListing = null;
		$this->externalModuleDetails = null;
		$this->repoListingProjectId = $repoListingProjectId;
		$this->gitHubRepo = self::GITHUB_REPO_LINK_BASE;
	}
		
	/**
	 * setRepoListingProjectId - set repoListingProjectId property.
	 */
	public function setRepoListingProjectId($repoListingProjectId)
	{
		$this->repoListingProjectId = $repoListingProjectId;
	}
	
	/**
	 * getRepoModuleListing - get repoModulesListing property.
	 */
	public function getRepoModuleListing()
	{
		return $this->repoModulesListing;
	}

	/**
	 * getExternalModuleDetails - get externalModuleDetails property.
	 */
	public function getExternalModuleDetails()
	{
		return $this->externalModuleDetails;
	}

	/**
	 * getRepoListing - get repo listing property.
	 */
	public function getRepoListing()
	{
		return $this->repoListing;
	}

	/**
	 * getGitHubRepo - get the git hub link value.
	 */
	public function getGitHubRepo()
	{
		return $this->gitHubRepo;
	}	

	/**
	 * setGitHubRepo - set the git hub link value. (not completely used, not really necessary for custom repo use)
	 */
	public function setGitHubRepo($repoLink)
	{
		$this->gitHubRepo = $repoLink;
	}	

	/**
	 * getRepoListingData - get the repo listing short names, build it if not already done.
	 */
	public function getRepoListingData()
	{
		if (!$this->repoListing) {
			$this->buildRepoListing();
		}

		return $this->repoListing;
	}

	/**
	 * buildRepoListing - create our Repo Module listing of short names, remember the list (repoListing).
	 */
	public function buildRepoListing()
	{
		$repoListing = $this->getRepoModulesList();

		$this->repoListing = $repoListing;
		
		return $repoListing;
	}

	/**
	 * getRepoInfoProjectId - give our Repo Project ID, which is a configured value and REQUIRED.
	 */
	public function getRepoInfoProjectId()
	{
		$project = ($this->repoListingProjectId ? $this->repoListingProjectId : null);  // from configuration file
		
		return $project;
	}

	/**
	 * getRepoModulesList - grab the list of our REPO items, the module short name (lowercase underscore without the version _vN.N.N). ex: your_module_name_here
	 */
	public function getRepoModulesList()
	{
		$modulesList = null;
		$projectId = $this->getRepoInfoProjectId();  // pulled from a config file internally

		if ($projectId) {
			// SELECT D.record AS recordId , D.value AS moduleShortName FROM redcap_data AS D WHERE D.project_id = 376 AND D.field_name = 'module_short_name' ORDER BY D.record * 1, D.field_name
			//
			$sql = 'SELECT D.record AS recordId , D.value AS moduleShortName FROM redcap_data AS D WHERE D.project_id = ' . $projectId . ' AND D.field_name = \'module_short_name\' ORDER BY D.record * 1, D.field_name';

			$result = db_query($sql);
	
			while($row = db_fetch_assoc($result)) {
	    	$recordId        = $row['recordId'];
	    	$moduleShortName = $row['moduleShortName'];
	
				$modulesList[$recordId] = $moduleShortName; // record id to module short name
	    }	
		}
		
		return $modulesList;
	}
	
	/**
	 * getRepoDatabaseDataEmListings - query the database for our Repo listing data, stored in a Project.  Requires we have a project set up with the correct structure and knowing the Project ID.
	 */
	public function getRepoDatabaseDataEmListings()
	{
		$projectDetails = null;
		$modulesList = null;
		
		$projectId = $this->getRepoInfoProjectId();  // pulled from a config file internally
		
		// get list of module short names
    $modulesList = $this->getRepoModulesList();
		
		if ($projectId) {
			// SELECT D.record AS recordId, D.field_name, D.value FROM redcap_data AS D WHERE D.project_id = 376 ORDER BY D.record * 1, D.field_name;
			//
			$sql = 'SELECT D.record AS recordId, D.field_name, D.value FROM redcap_data AS D WHERE project_id = ' . $projectId . ' ORDER BY D.record * 1, D.field_name';
			
			$result = db_query($sql);
	
			if ($result) {
				while($row = db_fetch_assoc($result)) {
		    	$recordId  = $row['recordId'];
		    	$field     = $row['field_name'];
		    	$value     = $row['value'];
		
		    	$projectDetails['repo_external_module_listing'][$modulesList[$recordId]][$field] = $value;  
		    }	
			}
		}
    			
		$this->externalModuleDetails = $projectDetails;
		
		return $projectDetails;
	}
	
	/**
	 * loadRepoDataStructure - give us our data of our stored Repo information in a handy listing structure.
	 */
	public function loadRepoDataStructure()
	{
		$emModuleInfo = null;
		
		// make sure we have loaded our data
		if ($this->externalModuleDetails == null) {
			$this->getRepoDatabaseDataEmListings();
		}

		$emListNames = $this->getRepoListingData();

		foreach ($emListNames as $key => $moduleEmName) {  //  $moduleEmName  ex: wusm_parent_child
			$authors['authorList'] = $this->getAuthors($moduleEmName);

			$emModuleInfo[$moduleEmName] = array(
				'moduleKeyShortName'  => $moduleEmName, 
				'recordId'            => $this->getModuleRecordId($moduleEmName),
				'moduleId'            => $this->getModuleId($moduleEmName),
				'moduleSystemVersion' => $this->getModuleSystemVersion($moduleEmName), 
				'tagName'             => $this->utilityParseEmNameFromModuleName($moduleEmName), 
				'description'         => $this->getModuleDescription($moduleEmName), 
				'authorList'          => $authors['authorList'], 
				'publishDate'         => $this->getPublishDate($moduleEmName), 
				'downloadCount'       => $this->getDownloadCount($moduleEmName), 
				'gitHub'              => $this->getGitHubLink($moduleEmName), 
				'active'              => $this->getModuleActive($moduleEmName)
			);
		}

		$this->repoModulesListing = $emModuleInfo;
		
		return $emModuleInfo;
	}

	/**
	 * getFieldData - extract the particular data piece out of our data structure loaded with all our Repo system module information and provide with a default value if given.
	 */
	private function getFieldData($module, $field, $default = null)
	{
		$data = null;

		$data = (isset($this->externalModuleDetails['repo_external_module_listing'][$module][$field]) ? $this->externalModuleDetails['repo_external_module_listing'][$module][$field] : $default);
		
		return $data;
	}
	
	/**
	 * getModuleSystemVersion - get the system version for the given module.
	 */
	private function getModuleSystemVersion($module)
	{
		$moduleSystemVersion = $this->getFieldData($module, 'module_system_version', 0);
		
		return $moduleSystemVersion;
	}

	/**
	 * getLatestVersion - give a version list find the latest one in the bunch.
	 */
	private function getLatestVersion($ver)
	{
		$numVersions = count($ver);
		$max = $numVersions;

		$v1 = $ver[0];
		
		if ($max >= 2) {
			$v2 = $ver[1];
		
			for ($x = 0; $x < $max; $x++) {
				$compare = $this->compareVersions($v1, $v2);
		
				// determine the greater version
				switch ($compare)
				{
					case self::VERSION_IS_OLDER:  // v1 < v2
						$v1 = $v2;
						$v2 = $ver[$x+1];
						break;
						
					case self::VERSION_IS_SAME:   // v1 = v2
						$v1 = $v1;
						$v2 = $ver[$x+1];
						break;
						
					case self::VERSION_IS_NEWER:  // v1 > v2
						$v1 = $v1;
						$v2 = $ver[$x+1];
						break;
						
					default:  // !v1  v2
						break;
				}
			}
		}
		
		$latest = $v1;
		
		return $latest;
	}
	
	// derived from https://www.geeksforgeeks.org/compare-two-version-numbers/
	// python version translated to php
	// This expects v1 and v2 to be of formats such as 1  1.0  1.0.0  
	//   it would not handle certain variations that are beyond scope such as 1.0.-alpha and version numbers that get into big int sizes probably. just don't be silly.
	//
	/**
	 * compareVersions - determine version older, same, newer.  Derived from algorithm logic elsewhere and modified for PHP and our particular use case problem space.
	 */
	private function compareVersions($v1, $v2)
	{
		$arr1 = explode('.', $v1);
		$arr2 = explode('.', $v2);
		
		$i = self::VERSION_IS_SAME;
		$count = count($arr1);
		
		// if we have 1  or 1.0  fix by adding dummy increments to fill out 1.0.0
		if ($count == 2) {
			$arr1[2] = 0;
			$count = count($arr1);
		}
		if ($count == 1) {
			$arr1[1] = 0;
			$arr1[2] = 0;
			$count = count($arr1);
		}
		
		// check versions
		while ($i < $count) {
			$n2 = $arr2[$i];
			$n1 = $arr1[$i];
			
			if ($n2 > $n1) {  // v1 is smaller v2  v1 is older
				return self::VERSION_IS_OLDER;	
			}
			
			if ($n1 > $n2) {  // v1 is greater than v2  typically for our case won't happen (v1 is newer)
				return self::VERSION_IS_NEWER;	
			}

			$i++;
		}
		
		return self::VERSION_IS_SAME;  // versions are the same
	}

	/**
	 * getModuleState - find out what state we happen to need to present, given the client version, the on file system version and the module we are looking at.
	 *  return state number
	 *   -1 = upgrade      Your version is older than Repo version (you have an old one)
	 *      returns   2
	 *   0  = installed    Your version is the same as Repo verion (you have the same one)
	 *      returns   1
	 *   1  = download     Your version is non existing            (you do not have it)
	 *      returns   0
	 */
	private function getModuleState($moduleCurrentVersion, $moduleEmDirName, $moduleSystemVersion)
	{
		$moduleSystemVersion = $this->getModuleSystemVersion($moduleEmDirName);
		
		$compare = $this->compareVersions($moduleCurrentVersion, $moduleSystemVersion);
		
		// determine download, installed, upgrade
		switch ($compare)
		{
			case self::VERSION_IS_OLDER:  // v1 < v2
				$moduleState = self::MODULE_STATE_UPGRADE; // upgrade, give upgrade button
				break;
				
			case self::VERSION_IS_SAME:   // v1 = v2
				$moduleState = self::MODULE_STATE_INSTALLED; // installed, give installed button
				break;
				
			default:  // !v1  v2
			case self::VERSION_IS_NEWER:  // v1 > ? v2
				$moduleState = self::MODULE_STATE_UNKNOWN; // no version // not downloaded, give download button
				break;
		}
		
		return $moduleState;
	}

	/**
	 * getModuleId - get Module ID.  Now this one is going to be somewhat important. AND we MUST be careful, as we want to avoid some sort of potential collision with Vanderbilt ID system.
	 *  NOTE: there will be no issue with module ID.  the local host does not use this directly, only as a reference in the download table (redcap_external_modules_downloads).
	 */
	private function getModuleId($module)
	{
		$moduleId = 0;
		
		$moduleId = $this->getFieldData($module, 'module_id');
		
		return $moduleId;
	}
	
	/**
	 * getModuleRecordId - get a module record ID, just because it may be handy.
	 */
	private function getModuleRecordId($module)
	{
		$moduleRecordId = 0;
		
		$moduleRecordId = $this->getFieldData($module, 'record_id');

		return $moduleRecordId;
	}

	/**
	 * getDownloadCount - get the download count.  Not truly used though, nothing updates the count in the database (maybe should), just does not seem all that important to do though. Replication of original feature, almost.
	 */
	private function getDownloadCount($module)
	{
		$downloadCount = 0;
		
		$downloadCount = 'NA';
		$downloadCount = $this->getFieldData($module, 'down_load_count', $downloadCount);
		
		return $downloadCount;
	}

	/**
	 * getGitHubLink - grab the Git Hub or such link data.  Not really used, since it will be internal, however, original has the feature and we keep it available if design need changes.
	 */
	private function getGitHubLink($module)
	{
		$gitHub = '';
		$gitHub = $this->getFieldData($module, 'git_hub');
		
		return $gitHub;
	}

	/**
	 * getPublishDate - get the publish data, mdY format.
	 */
	private function getPublishDate($module)
	{
		$publishDate = 'blank';
		$publishDate = $this->getFieldData($module, 'publish_date', $publishDate);
		
		return $publishDate;
	}

	/**
	 * getAuthors - get and build the author list.  Data is semi colon separated, and aligns accordingly across the three data fields. 
	 * 
	 * NOTE: it takes all three to line up. 
	 * Defaults will fill in. 
	 * CAUTION (your listing may be off): Data alignment issues could arise from improper set up.  (Might start to get confusing at five authors to list for a single EM)
	 * 
	 * NOTE: possibly a better way to handle this, so far, what I have come up with and not spend a lot of time angsting over it.
	 */
	private function getAuthors($module)
	{
		$authors = array();
		
		$authorName      = $this->getFieldData($module, 'author_name', 'unknown');
		$authorEmail     = $this->getFieldData($module, 'author_email', '');
		$authorInstitute = $this->getFieldData($module, 'author_institution', 'not listed');
		
		$authorsNameList     = explode(';', $authorName);
		$authorEmailList     = explode(';', $authorEmail);
		$authorInstituteList = explode(';', $authorInstitute);
		
		$numAuthors = count($authorsNameList) - 1;
		
		for ($countAuthors = 0; $countAuthors <= $numAuthors; $countAuthors++) {
			$authors[] = array('authorName' => trim($authorsNameList[$countAuthors]), 'authorEmail' => trim($authorEmailList[$countAuthors]), 'authorInstitute' => trim($authorInstituteList[$countAuthors]));
		}

		return $authors;
	}

	/**
	 * getModuleDescription - get the description data.
	 */
	private function getModuleDescription($module)
	{
		$description = '';
		$description = $this->getFieldData($module, 'description', $description);
		
		return $description;
	}

	/**
	 * getModuleActive - check if the module is active or inactive.  1 = active, 0 = inactive, return true or false for logic checks.
	 */
	private function getModuleActive($module)
	{
		$flag = false;
		$isActive = $this->getFieldData($module, 'active_flag');
		
		$flag = ($isActive ? true : false);
		
		return $flag;
	}

	/**
	 * getModuleInformation - create the needed data listing of the module given a module name (the underscore name without the version attached) and its version.  Makes it easy to then build a listing entry.
	 */
	public function getModuleInformation($moduleShortName, $moduleClientVersion)
	{
		$moduleSystemVersion = 0;
		
		$moduleCurrentVersion    = $moduleClientVersion; //$this->utilityParseVersionFromModuleName($moduleEmDirName);
		$moduleSystemVersion     = $this->getModuleSystemVersion($moduleShortName);                                      // db
		$moduleId                = $this->getModuleId($moduleShortName);                                                 // db
		$moduleState             = $this->getModuleState($moduleCurrentVersion, $moduleShortName, $moduleSystemVersion);
		$moduleTagName           = $this->utilityParseEmNameFromModuleName($moduleShortName);                            // db
		$moduleEmNameVersionless = $this->utilityParseModuleNameFromModuleEmName($moduleShortName);
		$moduleDescription       = $this->getModuleDescription($moduleShortName);                                        // db
		$moduleDownloadCount     = $this->getDownloadCount($moduleShortName);                                            // db
		$modulePublishDate       = $this->getPublishDate($moduleShortName);                                              // db
		$moduleAuthors           = $this->getAuthors($moduleShortName);                                                  // db
		$moduleActiveFlag        = $this->getModuleActive($moduleShortName);                                             // db


		$moduleSystemVersion     = ($moduleSystemVersion == 0 ? $moduleCurrentVersion : $moduleSystemVersion);
		$moduleNameInstalled     = $moduleEmNameVersionless . '_v' . $moduleSystemVersion;
		$gitHubRepo              = $this->gitHubRepo;
		$gitHubRepoUrl           = $gitHubRepo . $this->utilityParseGitHubNameFromModuleEmName($moduleShortName);
		$gitHubRepoUrl           = '';

		$moduleInformation['moduleCurrentVersion']  = $moduleCurrentVersion;
		$moduleInformation['moduleId']              = $moduleId;
		$moduleInformation['state']                 = $moduleState;
		$moduleInformation['tagName']               = $moduleTagName;
		$moduleInformation['moduleName']            = $moduleNameInstalled; // display:   (modify_contact_admin_button_v2.0.0)
		$moduleInformation['moduleNameInstalled']   = $moduleNameInstalled; // display:   (modify_contact_admin_button_v2.0.0)
		$moduleInformation['github']                = $gitHubRepoUrl;
		$moduleInformation['description']           = $moduleDescription;
		$moduleInformation['publishDate']           = $modulePublishDate;
		$moduleInformation['downloadCount']         = $moduleDownloadCount;
		$moduleInformation['authorList']            = $moduleAuthors;
		$moduleInformation['active']                = $moduleActiveFlag;
		
		return $moduleInformation;
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
	public function getButtonDownloadClass($redcapVersion = '8.0.0')  // should be the version of the repo server, not the client
	{
		$charImageButtonDownloadClass = 'glyphicon glyphicon-save';
		
		$ver = $this->compareVersions(''.$redcapVersion, '8.6.5');  // glyphicon last in 8.6.5 changed to fas in 8.7.0
		
		// glyphicon older REDCap CSS
		// fas newer REDCap CSS
		// where the version change for the font changed: glyphicon last in 8.6.5 changed to fas in 8.7.0
		$charImageButtonDownloadClass = (($ver < 1) ? 'glyphicon glyphicon-save' : 'fas fa-download');
		
		return $charImageButtonDownloadClass;
	}
		
	/**
	 * createRow - core function to build a row entry listing from our data.  Dynamically decide if the state of what the requester has and what to show, download, installed, upgrade.
	 */
	public function createRow($moduleShortName, $moduleClientVersion)
	{
		$row = '';
		$moduleInfo = $this->getModuleInformation($moduleShortName, $moduleClientVersion);

		$moduleId             = $moduleInfo['moduleId'];              // 

		$moduleState          = $moduleInfo['state'];                 // 0, 1, 2   0 = download button, 1 = download button greyed, 2 = upgrade button (dynamically determined based upon versions of client and server)
		$moduleTagName        = $moduleInfo['tagName'];               // 'Auto DAGs';
		$moduleName           = $moduleInfo['moduleName'];            // 'auto_dags';
		$moduleGitHubUrl      = $moduleInfo['github'];                // 'https://github.com/vanderbilt-redcap/auto-dags-module';
		$moduleDescription    = $moduleInfo['description'];           // 'Automatically creates, renames, and assigns records to Data Access Groups (DAGs) based on a specified Text field, in which the value of the field becomes the name of the DAG. If the DAG already exists, then the record will be assigned to the existing DAG.';
		$moduleAuthorList     = $moduleInfo['authorList'];            // array('authorName' => 'Mark McEver', 'authorEmail' => 'datacore@vanderbilt.edu', 'authorInstitute' => 'Vanderbilt University Medical Center');
		$modulePublishDate    = $moduleInfo['publishDate'];           // '2018-11-28';
		$moduleDownloadCount  = $moduleInfo['downloadCount'];         // '412';
		$moduleActive         = $moduleInfo['active'];                // 1 or 0
		$moduleCurrentVersion = $moduleInfo['moduleCurrentVersion'];  // 1.2.1                                                  (given $moduleClientVersion)
		$moduleNameInstalled  = $moduleInfo['moduleNameInstalled'];   // 'auto_dags'_v1.0;                                      (dynamically determined by given client and server)

		if (!$moduleActive) {
			return '';
		}

		$moduleGitHubLink = '';
		if ($moduleGitHubUrl) {
			$row .= '			 <a style="font-size:12px;text-decoration:underline;" ';
			$row .= '			 	href="';
			$row .= $moduleGitHubUrl;
			$row .= '			 	" target="_blank">';
			$row .= '			 	View on GitHub';
			$row .= '			 </a>';
		}
		
		$row .= '<tr>';
		$row .= '	<td>';
		$row .= '	<hr>';
		$row .= '		<div>';
		$row .= '			<span style="font-size:14px;font-weight:bold;">';
		$row .= $moduleTagName;
		$row .= '			</span>';
		$row .= '			 &nbsp; <i style="font-size:12px;">(' . $moduleNameInstalled . ')</i> &nbsp; ';
		$row .= $moduleGitHubLink;
		$row .= '		</div>';
		$row .= '		<div style="margin:7px 0 4px; font-size:12px;">';
		$row .= '			<i style="color:#C00000;">Description:</i> ' . $moduleDescription .'';
		$row .= '		</div>';
		$row .= '			<div style="font-size:12px;">';
		$row .= '				<i style="color:#C00000;">Author:</i> ';

    $authorCount = count($moduleAuthorList);
    
    foreach ($moduleAuthorList as $authorKey => $author) {
    	$comma = '';
    	$nbsp = '';

    	if ($authorCount > 0 && ($authorKey < $authorCount - 1)) { // add ,&nbsp;
    		$comma = ',';
	    	$nbsp = '&nbsp;';
    	}
    	
    	$authorEmail = $author['authorEmail'];

    	if ($authorEmail == '') {  // no email so no emailer link
				$row .= '				<span style="font-size:12px;">' . $author['authorName'] . '</span>&nbsp; ';
    	} else {
				$row .= '				<a style="font-size:12px;text-decoration:underline;" href="mailto:' . $author['authorEmail'] .'">' . $author['authorName'] . '</a>&nbsp; ';
    	}

			$row .= '				<span style="color:#777;">(' . $author['authorInstitute'] . ')' . $comma . '</span>' . $nbsp;
		}

		$row .= '		</div>';
		$row .= '	</td>';
		$row .= '	<td style="font-size:13px;" class="text-center nowrap">' . $modulePublishDate . '</td>';
		$row .= '	<td style="font-size:15px;" class="text-center">' . $moduleDownloadCount . '</td>';
		$row .= '	<td>';

		$moduleTagNameUrlized = str_replace(' ', '+', $moduleTagName);
		$moduleId     = $moduleId;
		$bypass       = ''.$moduleTagNameUrlized.'+%28'.$moduleNameInstalled.'%29';
		$sendUserInfo = $moduleNameInstalled;
				
		$disabledFlag = ($moduleState == 1 ? ' disabled ' : '');
				
		// upgrade  btn-success
		// download btn-primary
		// already download btn-defaultrc
		$buttonState = '';
		
		if ($moduleClientVersion == 0) {
			$moduleState = self::MODULE_STATE_DOWNLOAD; // you do not have, so, you need a download.
		}
		
		switch ($moduleState)
		{
			case self::MODULE_STATE_DOWNLOAD:  // download                      (you do not have it)
				$buttonState = 'btn-primary';
				break;

			case self::MODULE_STATE_INSTALLED:  // already downloaded, installed (you have the same one)
				$buttonState = 'btn-defaultrc';
				break;
				
			case self::MODULE_STATE_UPGRADE:  // currently installed, upgrade  (you have an old one)
				$buttonState = 'btn-success';
				break;

			case self::MODULE_STATE_UNKNOWN:  // yours is newer, what, are you a dev?
			default:
				$buttonState = 'btn-defaultrc';
				$disabledFlag = ' disabled ';
				break;
		}

		$row .= '		<button class="btn ' . $buttonState . ' btn-sm" '.$disabledFlag.' onclick="downloadModule(\''.$moduleId.'\',\''.$bypass.'\',\''.$sendUserInfo.'\');">';

		$charImageButtonDownloadClass = $this->getButtonDownloadClass($this->redcapVersion);
		
		$row .= '			<span class="' . $charImageButtonDownloadClass . '" style="font-size:14px;"></span> ';

	
		// 3 states, download buttons:  download, already downloaded, upgrade
		// and an 'impossible' does not exist, for some dummy data testing.
		switch ($moduleState)
		{
			case self::MODULE_STATE_DOWNLOAD:  // download
				$row .= 'Download';
				$row .= '		</button>';
				$row .= '		<div class="nowrap"" style="margin-top:4px;font-size:11px;color:#666;">';
				$row .= '';
				$row .= '		</div>';
				break;

			case self::MODULE_STATE_INSTALLED:  // already downloaded, installed
				$row .= 'Installed';
				$row .= '		</button>';
				$row .= '<div class="nowrap" style="margin-top:4px;font-size:11px;color:#A00000;">Already downloaded</div>';
				break;
				
			case self::MODULE_STATE_UPGRADE:  // currently installed, upgrade
				$row .= 'Upgrade';
				$row .= '		</button>';
				$row .= '		<div class="nowrap"" style="margin-top:4px;font-size:11px;color:#666;">';
				$row .= '			Currently installed: v'.$moduleCurrentVersion.'';
				$row .= '		</div>';
				break;

			// this state likely will not happen on a live server, however, on a developers station it will
			case self::MODULE_STATE_UNKNOWN:  // yours is newer, what, are you a dev?
				$row .= 'DEV';
				$row .= '		</button>';
				$row .= '<div class="nowrap" style="margin-top:4px;font-size:11px;color:#A00000;">Are you a developer?';
				$row .= '<br>Currently installed: v'.$moduleCurrentVersion.'';
				$row .= '		</div>';
				break;

			default:  // this may not be an actual viewable state, since, checking the item as inactive removes it from the list
				$row .= 'Discontinued';
				$row .= '		</button>';
				$row .= '<div class="nowrap" style="margin-top:4px;font-size:11px;color:#A00000;">Not Available</div>';
				break;
		}
		
		$row .= '	</td>';
		$row .= '</tr>';	

		return $row;
	}

	/**
	 * EM NAME
	 * utilityParseEmNameFromModuleName - given a module name (load_visit_data_v1.2.1) return the em name of that module: Load Vist Data.  NOTE: some modules may have a different tag name than just the name cleaned up.
	 * @return string EM NAME.
	 */
	public function utilityParseEmNameFromModuleName($moduleName)
	{
		$moduleVersion = '';
		$moduleVersion = $this->utilityParseVersionFromModuleName($moduleName);
		
		$tmp = str_replace('_v'.$moduleVersion, '', $moduleName);
		$tmp = str_replace('_', ' ', $tmp);
		$emName = ucwords($tmp);

		return $emName;
	}

	/**
	 * VERSION
	 * utilityParseVersionFromModuleName - given a module name (load_visit_data_v1.2.1) return the version of that module.
	 * @return string VERSION.
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
	 * MODULE NAME
	 * utilityParseModuleNameFromModuleEmName - given a module name (load_visit_data_v1.2.1) return the em dir name of that module, load_visit_data.
	 * @return string MODULE NAME.
	 */
	public function utilityParseModuleNameFromModuleEmName($moduleName)
	{
		$moduleVersion = '';
		$moduleVersion = $this->utilityParseVersionFromModuleName($moduleName);
		
		// get the name, that must be the "load_visit_data"
		$moduleEmName = str_replace('_v'.$moduleVersion, '', $moduleName);

		return $moduleEmName;
	}

	/**
	 * GIT HUB NAME
	 * utilityParseGitHubNameFromModuleEmName - given a module name (load_visit_data_v1.2.1) return the em dir name of that module, load-visit-data.
	 * @return string GIT HUB NAME.
	 */
	public function utilityParseGitHubNameFromModuleEmName($moduleName)
	{
		$moduleVersion = '';
		$moduleVersion = $this->utilityParseVersionFromModuleName($moduleName);
		
		$tmp = str_replace('_v'.$moduleVersion, '', $moduleName);
		// get the name, that must be the "load_visit_data"
		$moduleGitHubName = str_replace('_', '-', $tmp);  // change underscores to dashes. maybe that is standard for such git hub names?
		// get the git hub converted name, that must be the "load-visit-data"

		return $moduleGitHubName;
	}

	/**
	 * buildListOfClientEms - build a list of client EMs and handle, hopefully, version variance (TODO: test this more).
	 */
	public function buildListOfClientEms($emlist)
	{
		$givenEmList = null;
		
		$data = null;
		
		foreach ($emlist as $key => $item) {
			$name     = $this->utilityParseModuleNameFromModuleEmName($item);
			$version  = $this->utilityParseVersionFromModuleName($item);
			$longname = $item;
			
			$givenEmList[$name] = array('shortname' => $name, 'longname' => $longname, 'version' => $version);
			
			$data['versions'][$name][] = array('shortname' => $name, 'longname' => $longname, 'version' => $version);
		}
		
		// must account for multiple names and get latest version out of all the versions
		// this will adjust the data to make sure it reflects the latest version in the lists given
		foreach ($givenEmList as $emKey => $emItem) {
			$len = count($data['versions'][$emKey]);
			if ($len > 0) {
				$listversions = $data['versions'][$emKey];
				
				foreach ($listversions as $listKey => $versionData) {
					$versionList[] = $versionData['version'];
				}
				
				$lastversion = $this->getLatestVersion($versionList);
				$givenEmList[$name]['version'] = $lastversion;
				$givenEmList[$name]['longname'] = $givenEmList[$name]['shortname'] . '_v' . $lastversion;
			}
			unset($versionList); // clear out the temp version list
		}
		
		return $givenEmList;
	}
	
	/**
	 * showJson - show a json parsable page.  Handy for debugging purposes.
	 */
	public function showJson($json) 
	{
		$jsonheader = 'Content-Type: application/json; charset=utf8';
		header($jsonheader);
		echo $json;
	}

	/**
	 * buildStrHtml - make the main listing with all the required elements based upon what we are given from the client side to help dynamically determine what they will need to see, download, installed, upgrade.
	 */
	public function buildStrHtml($clientEmListing, $redcapVersion = '8.0.0')
	{
		$strHtml = '';
		
		$repoEmListing = $this->repoModulesListing;
		//
		// SERVER REPO REDCap version
		$this->redcapVersion = $redcapVersion;  // server repo redcap version
	
		// loop the em list
		foreach ($repoEmListing as $emShortName => $item) {
			$displayItem   = false;
			$emShortName   = $item['moduleKeyShortName'];
			$clientVersion = (isset($clientEmListing[$item['moduleKeyShortName']]['version']) ? $clientEmListing[$emShortName]['version'] : '0');
			$displayItem   = $item['active'];
			
			if ($displayItem) {
				$strHtml .= $this->createRow($emShortName, $clientVersion);
			}
		}	
		
		return $strHtml;
	}

	/**
	 * getTableHead - provide the table header section, to start the table.
	 */
	public function getTableHead()
	{
		$tableHead = '<table id="modules-table" style="width:100%;" class="dataTable no-footer" role="grid" aria-describedby="modules-table_info">
	
		<thead>
	
		<tr>
	
			<th style="color:#C00000;font-size:16px;">Module title and description</th>
	
			<th style="color:#C00000;">Date<br>Added</th>
	
			<th style="color:#C00000;">Downloads</th>
	
			<th style="color:#C00000;"></th>
	
		</tr>
	
		</thead>
	
		<tbody>';
		
		return $tableHead;
	}

	/**
	 * getHtmlPiece - provide some of the main html with the top text portion.
	 */
	public function getHtmlPiece($customName = '', $institute = '', $emailSupport = '', $classButton = '', $serverName = 'wwwrc8', $port = ':8888', $httpType = 'http')
	{
		// NOTE (optional if needed): wording involved that needs to change for the strikethrough text.
	
		//	by software developers at <span style="text-decoration: line-through;">various REDCap institutions around the world</span> ' . $institute . '.
		
		// **
		//		Disclaimer: <span style="text-decoration: line-through;">The modules listed in the REDCap Repo have gone through a process of testing, validation, and curation by the Vanderbilt REDCap Team with help
		//	from trusted individuals in the REDCap Consortium. It should not be assumed that all modules are bug-free or perfect, so please be aware 
		//	that the entire risk as to the quality and performance of the module as it is used in your REDCap installation
		//	is borne by the REDCap administators who download and install the modules. </span>

 // **

		// find where we are and get the current dir path name dynamically, as this root dir may change depending upon some set up.
		// used to get our resource image
		$here = dirname(__FILE__);
		$herePieces = explode(DIRECTORY_SEPARATOR.'modules', $here);
		$dirslist = explode(DIRECTORY_SEPARATOR, $herePieces[0]);


		$repoRootLocation = '/redcap/'.$dirslist[(count($dirslist) - 1)];

		$httpServerName = $httpType.'://'.$serverName;
		$repoRootLocation = $httpServerName.''.$port . $repoRootLocation;  // example - https://someserver:8080/customerepo
		
		$htmlPiece = '</head>
	<body>
	
	<style type="text/css">
	
	p { max-width: 900px; }
	
	</style>
	
	<div id="pagecontainer" class="container-fluid" role="main">
	
	<!-- Title and logo -->
	
	<div class="clearfix">
	
		<div class="pull-left"><img src="'.$repoRootLocation.'/resources/img/redcap_repo_custom.png"></div>	

	
		<div class="pull-right" style="margin:15px 10px 0;color:#C00000;font-size:24px;">Repository of ' . $customName . ' External Modules </div>
	
	</div>
	
	
	
	<!-- Info and instructions -->
	
	<p>
	
		The REDCap Repo is a centralized repository of curated External Modules that can be downloaded and installed in REDCap by a REDCap administrator. 
	
		External Modules are add-on packages of software that can extend REDCap\'s current functionality, as well as provide customizations and enhancements 
	
		for REDCap\'s existing behavior and appearance, either at the system level or project level. The modules provided here were created and submitted
	
		by software developers at ' . $institute . '.
	
	</p>
	
	<p>
	
		You may search below for available modules. If you got to this site directly, you will be able to view information about each module,
	
		but you will not be able to download modules from this page unless you arrived here from the REDCap application and are a REDCap administrator.
	
		If you have questions or are experiencing issues, please contact 
	
		<a style="text-decoration:underline;" href="mailto:' . $emailSupport . '?subject=REDCap%20Repo">' . $emailSupport . '</a>.
	
	</p>
	
	<p style="font-size:11px;line-height:13px;color:#777;">
	
		Disclaimer: Your disclaimer here if one is needed.
	
	</p>';

		// Will take the user back to the client site, the Main External Module Manager page
		$backButton = '
		<div style="margin-top:15px;">
			<button class="btn btn-defaultrc btn-sm" onclick="returnToREDCap()">
				&nbsp;<span style="font-size:16px; color:#00AA00;">
				< - - - &nbsp; Return to REDCap Main External Module Manager
				</span>
			</button>
		</div>';

		$htmlPiece .= $backButton;

		return $htmlPiece;
	}
	
	/**
	 * getJsUpdateScripts - provide some utility javascript that can move us back to client EM manager, sort the columns, and TBD download functionality feature.
	 */
	public function getJsUpdateScripts($linkUrlBase, $linkUrlBaseBack, $callingEmManagerId, $callingEmManagerIdPrefix)
	{
		$parseOutClientRefererUrl = explode('/ExternalModules', $linkUrlBaseBack);
		$clientRootPathUrl = $parseOutClientRefererUrl[0];  // get the client host url section: http://wwwrc8:8888/redcap_v8.0.3

		$clientRootPathUrl = js_escape($clientRootPathUrl);
		$callingEmManagerId = js_escape($callingEmManagerId);
		$callingEmManagerIdPrefix = js_escape($callingEmManagerIdPrefix);

		//$linkUrlBaseBackMockUP = $clientRootPathUrl . '/ExternalModules/?id='.$callingEmManagerId.'&page=download.php';
		if ($callingEmManagerId) {
			$linkUrlBaseBackMockUP = $clientRootPathUrl . '/ExternalModules/?id='.$callingEmManagerId.'&page=download.php';
		}
		if ($callingEmManagerIdPrefix) {
			$linkUrlBaseBackMockUP = $clientRootPathUrl . '/ExternalModules/?prefix='.$callingEmManagerIdPrefix.'&page=download.php';
		}

		// what does get module params do?
		// getModuleUpdatesUrlParam

		$secretToken = md5(date('YmdH'));

		// begin the javascript
		//
		$jsUpdateScripts = "
			</style><script type=\"text/javascript\">
			";
		
		// data table sorter header
		//
		$jsUpdateScripts .= "
			
			$(function() {
			
				$('#modules-table').DataTable( { 
			
					\"aaSorting\": [], 
			
					\"pageLength\": 10,
			
					\"oLanguage\": { \"sSearch\": \"\" }
			
				} );
			
				$('#modules-table_filter input[type=\"search\"]').attr('type','text').prop('placeholder','Search');
			
			});
			";
			
		// downloadModule button
		//
		$jsUpdateScripts .= "
			function downloadModule(module_id, module_title, module_name) {
			  var dialogHeaderTitle = 'Download Selection';
			  var dialogHeaderMsg   = 'Download or Cancel';
			
				simpleDialog(dialogHeaderMsg,dialogHeaderTitle,'module-license-dialog',700,null,'Cancel',function(){
			
					window.location.href = '" . $linkUrlBaseBackMockUP . "&token=" . $secretToken . "&download_module_id='+module_id
			
						+ '&download_module_title='+module_title+'&download_module_name='+module_name+'&'+getModuleUpdatesUrlParam();
			
				},'Download');
			
				fitDialog($('#module-license-dialog'));
			
			}
			";
			
		// back link button to Client REDCap External Modules Manager page, the standard EM manager.
		$jsUpdateScripts .= "
			
			
			function returnToREDCap() {
			
				window.location.href = '" . $linkUrlBaseBack . "?'			
			}
			";

		// params handler
		// which does.... what?
		//  name, version, title  list of the client modules listing.
		// as json data that is html url encoded
		// {"261":{"name":"admin_dash","version":"3.3.1","title":"Admin Dashboard"},
		// %7B%22261%22%3A%7B%22name%22%3A%22admin_dash%22%2C%22version%22%3A%223.3.1%22%2C%22title%22%3A%22Admin+Dashboard%22%7D%2C
		//
		// not used in the custom repo handler
		//
		// module_updates=%7B%7D   module_updates={}
		//
		
		$jsUpdateScripts .= "
			
			function getModuleUpdatesUrlParam() {
			
				return 'module_updates=%7B%7D';
			
			}
			";
			
			// end the javascript
			//
			$jsUpdateScripts .= "
		
			</script>";
	
		return $jsUpdateScripts;
	}	
	
	/**
	 * getHeaderHtml - provide the header html with some custom touches. Has required javascript data for other pieces to work properly, replicating how Vanderbilts section works.
	 */
	public function getHeaderHtml($redcapVersion = '8.0.3', $serverName = 'wwwrc8', $port = ':8888', $httpType = 'http')
	{
		$html = '';
		$httpServerName = $httpType.'://'.$serverName;
		
		$html .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
		$html .= '<html lang="en">';
		$html .= '<head>';
		$html .= '<meta name="googlebot" content="noindex, noarchive, nofollow, nosnippet">';
		$html .= '<meta name="robots" content="noindex, noarchive, nofollow">';
		$html .= '<meta name="slurp" content="noindex, noarchive, nofollow, noodp, noydir">';
		$html .= '<meta name="msnbot" content="noindex, noarchive, nofollow, noodp">';
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
		$html .= '<meta http-equiv="Cache-Control" content="no-cache">';
		$html .= '<meta http-equiv="Pragma" content="no-cache">';
		$html .= '<meta http-equiv="expires" content="0">';
		$html .= '<meta charset="utf-8">';
		$html .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
		$html .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
		$html .= '<title>REDCap Custom Repo</title>';

		$html .= '<link rel="shortcut icon" href="'.$httpServerName.''.$port.'/redcap'.'/redcap_v'.$redcapVersion.'/Resources/images/favicon.ico">';
		$html .= '<link rel="apple-touch-icon-precomposed" href="'.$httpServerName.''.$port.'/redcap'.'/redcap_v'.$redcapVersion.'/Resources/images/apple-touch-icon.png">';
		$html .= '<link rel="stylesheet" type="text/css" media="screen,print" href="'.$httpServerName.''.$port.'/redcap'.'/redcap_v'.$redcapVersion.'/Resources/css/jquery-ui.min.css"/>';
		$html .= '<link rel="stylesheet" type="text/css" media="screen,print" href="'.$httpServerName.''.$port.'/redcap'.'/redcap_v'.$redcapVersion.'/Resources/css/style.css"/>';
		$html .= '<link rel="stylesheet" type="text/css" media="screen,print" href="'.$httpServerName.''.$port.'/redcap'.'/redcap_v'.$redcapVersion.'/Resources/css/home.css"/>';

		$html .= '<script type="text/javascript" src="'.$httpServerName.'/redcap/redcap_v'.$redcapVersion.'/Resources/js/base.js"></script>';
		
	  // set up some data times
		$nowDateTimeYMD = date('Y-m-d H:i:s'); // 2019-01-15 08:54:37
		$nowDateTimeMDY = date('m-d-Y H:i:s'); // 01-15-2019 08:54:37
		$nowDateTimeDMY = date('d-Y H:i:s');   // 15-01-2019 08:54:37
		
		$nowDateYMD = date('Y-m-d'); // 2019-01-15
		$nowDateMDY = date('m-d-Y'); // 01-15-2019
		$nowDateDMY = date('d-m-Y'); // 15-01-2019
		
		// Javascript Data Elements
		//
		$html .= '<script type="text/javascript">';
		$html .= "var redcap_version = '".$redcapVersion."';";
		$html .= "var server_name = '".$serverName."';";
		$html .= "var app_path_webroot_full = '".$httpServerName."".$port."/';";

		$html .= "var app_path_webroot = '/redcap/redcap_v".$redcapVersion."/';";
		$html .= "var page = 'redcap/redcap_v".$redcapVersion."/ExternalModules/manager/control_center.php';";

		$html .= "var app_path_images = '/redcap/redcap_v".$redcapVersion."/Resources/images/';";

		$html .= "var sendit_enabled = 1;";
		$html .= "var super_user = 1;";
		$html .= "var surveys_enabled = 0;";
		$html .= "var now = '".$nowDateTimeYMD."'; var now_mdy = '".$nowDateTimeMDY."'; var now_dmy = '".$nowDateTimeDMY."';";
		$html .= "var today = '".$nowDateYMD."'; var today_mdy = '".$nowDateMDY."'; var today_dmy = '".$nowDateDMY."';";
		$html .= "var email_domain_whitelist = new Array();";
		$html .= "var user_date_format_jquery = 'mm/dd/yy';";
		$html .= "var user_date_format_validation = 'mdy';";
		$html .= "var user_date_format_delimiter = '/';";
		$html .= "var ALLOWED_TAGS = '<ol><ul><li><label><pre><p><a><br><center><font><b><i><u><h6><h5><h4><h3><h2><h1><hr><table><tbody><tr><th><td><img><span><div><em><strong><acronym><sub><sup>';";
		$html .= "var AUTOMATE_ALL = '0';";
		$html .= "var datatables_disable = [];";
			
		$html .= '</script>';
		
		return $html;
	}	

	/**
	 * getFooterHtml - provide the footer html with some custom touches.
	 */
	public function getFooterHtml($customInstituteName = 'Washington University Institute for Informatics', $redcapVersionInfo = '8.0.0')
	{	
		$footerinfo = '';
		$footerinfo .= '<div>';
		
		$footerinfo .= '<div> </div>';
		$footerinfo .= $hr;
		$footerinfo .= $hr;
		$footerinfo .= $hr;
		$footerinfo .= $hr;
		
		$footerinfo .= '<b>REQUIRED COPYRIGHT NOTICE</b><br />';
		
		$yearInfo = date('Y');
		
		$footerinfo .= '"Copyright '.$yearInfo.' '.$customInstituteName.'. All rights reserved."</div>';
		
		$footerinfo .= '<div>';
		$footerinfo .= '</div>';
		
		$footerCopyright = '<div id="footer" class="d-none d-sm-block col-md-12" aria-hidden="true"><a href="https://projectredcap.org" tabindex="-1" target="_blank">REDCap '.$redcapVersionInfo.'</a> - &copy; '.$yearInfo.' '.$customInstituteName.'</div>';
		
		$footerinfo .= $footerCopyright;
		
		$footerinfo .= '</div>';
		
		return $footerinfo;
	}
	
} // *** end class


?>
