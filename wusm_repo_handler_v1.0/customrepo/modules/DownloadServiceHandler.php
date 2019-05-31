<?php

// ****************************************
// ****************************************
// ****************************************
// ****************************************

// ****************************************
class DownloadServiceHandler
{
	private $modulesListing;
	
	/**
	 * constructor - basic initialize.
	 */
	function __construct()
	{
		$this->modulesListing = null;
	}
			
	/**
	 * getName - .
	 */
	public function getName($moduleId)
	{
		$moduleName = '';
		
		$moduleName = $this->getModuleName($moduleId);
		
		return $moduleName;
	}
	
	/**
	 * getZip - .
	 */
	public function getZip($moduleId)
	{
		$moduleZip = null;
		
		$moduleName = $this->getModuleName($moduleId);
		
		// zip file name
		$moduleZip = $moduleName . '.zip';
		
		return $moduleZip;
	}
	
	/**
	 * filterModuleId - .
	 */
	public function filterModuleId($val)
	{
		$moduleId = null;
		
		if ($val) {
			$valNum = filter_var($val, FILTER_SANITIZE_NUMBER_INT);
			$moduleId = $valNum;
		}
	
		return $moduleId;
	}
	
	/**
	 * getModuleName - .
	 */
	public function getModuleName($moduleId = null)
	{
		$moduleName = '';
		
		if ($moduleId) {
			$list = $this->getModuleNameList();
			$moduleName = (isset($list[$moduleId]) ? $list[$moduleId]['moduleName'] : '');
		}
		
		return $moduleName;
	}

	/**
	 * getModuleNameList - .
	 */
	public function getModuleNameList()
	{
		// TODO: read the list data
		
		// the problem here is this does not have all the handy REDCap functionality, database access and so forth within the limited scope that all this is residing in
		// and when adding that in, we run into issues of cross site scripting protections in REDCap
		// so we use a simpler yet less dynamic solution, a pre built file with the data.  and since that data will not rapidly change, only when we add new modules
		// all will be good and orderly.

		return $this->modulesListing;  // wacky thing here, a command line generator makes the data and we yank it up and set the value using the generated data from a generated function.
	}
	
	/**
	 * feedModuleListing - .
	 */
	public function feedModuleListing($moduleList)
	{
		$this->modulesListing = $moduleList;
	}


} // *** end class

// **************************************************
// **************************************************
// **************************************************

?>
