<?php

// ****************************************
// ****************************************
// ****************************************
// ****************************************

// ****************************************
class DownloadServiceDataGrabber
{
	/**
	 * constructor - basic initialize.
	 */
	function __construct()
	{
	}
	
	/**
	 * getModuleNameListDatabase - .
	 */
	public function getModuleNameListDatabase($projectId = null)
	{
		$modules = null;
		if ($projectId != null) {
			
			$sql = "SELECT D.record AS recordId, D.field_name, D.value FROM redcap_data AS D WHERE project_id = " . $projectId . "  AND (D.field_name = 'module_id' OR D.field_name = 'module_short_name' OR D.field_name = 'module_system_version') ORDER BY D.record * 1, D.field_name";
			$result = db_query($sql);
		
			$data = null;	
		
			if ($result) {
				while($row = db_fetch_assoc($result)) {
		    	$recordId  = $row['recordId'];
		    	$field     = $row['field_name'];
		    	$value     = $row['value'];
		
		    	$data[$recordId][$field] = $value;
		    }	
			}
			
			if ($data) {
				foreach ($data as $key => $val) {
					$module_id             = $val['module_id'];
					$module_short_name     = $val['module_short_name'];
					$module_system_version = $val['module_system_version'];
					
					$modules[$module_id] = array('module' => $module_short_name, 'moduleName' => $module_short_name . '_v' . $module_system_version, 'version' => $module_system_version);
				}
			}
		}
				
		return $modules;
	}

} // *** end class

// **************************************************
// **************************************************
// **************************************************

?>
