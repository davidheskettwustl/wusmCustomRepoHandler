<?php

function getRepoConfigurationData()
{	
	// Set your institute name here
	// Set your Custom REPO project ID here
	
	// ***** ***** *****
	// ** ** ** Customize these settings Section Begin
	// ***** ***** *****
	  // CUSTOMIZE these four values here
	  // PROJECT_ID_VALUE
	$customRepoProjectId     = 0; // PROJECT ID VALUE // CRITICAL to have some project ID to look up the data for the Custom Repo.  TODO: make this some how configurable
	$customInstituteName     = '';
	$customEmailSupport      = '';
	$instituteRepoCustomText = '';
	
	// This allows turning off the host server to deny access and control the server if it needs to be disconnected for some reason
	$serverOnOffSwitch       = 1; // 1 = server ON, 0 = server OFF (for maintenance or other purposes)
	//$serverOnOffSwitch       = 0; // 1 = server ON, 0 = server OFF (for maintenance or other purposes)
	
	// ***** ***** *****
	// ** ** ** Customize these settings Section End
	// ***** ***** *****

	
	$config['instituteName'] = $customInstituteName;
	$config['repoProjectId'] = $customRepoProjectId;
	$config['emailSupport'] = $customEmailSupport;
	$config['instituteRepoCustomText'] = $instituteRepoCustomText;

	$config['activeFlag'] = ($serverOnOffSwitch ? true : false);  // server ON OFF switch
	
	return $config;
}

?>


