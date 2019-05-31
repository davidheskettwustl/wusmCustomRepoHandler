<?php

	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * findRedcapVersionPath - utility to help find the REDCap version dynamically using a relative file path starting point.
	 */
	// Find the highest numbered REDCap version folder in this directory
	function findRedcapVersionPath($fromWhatDir = '/modules', $justVersionNumber = false)
	{
		$nl = "\n";
		$files = array();
		$path = dirname(__FILE__);
		
		// working from the given directory, get our path and pull out the redcap top level path
		$x = explode($fromWhatDir, $path);
		$path = $x[0];
	
		$dh = opendir($path);
	
		// safety break so we avoid possible infinite loop
		$safety = 0;
		while (($filename = readdir($dh)) !== false) 
		{
			$safety++;
			if ($safety > 10000) {  // if we run 10000 times, escape
				break;
			}
			$substr = substr($filename, 0, 8);
	
			if ($substr == "redcap_v") {
				$isDir = is_dir($path . DIRECTORY_SEPARATOR . $filename);
				if ($isDir) {
					// Found one!
					$this_version = substr($filename, 8);
					list ($v1, $v2, $v3) = explode(".", $this_version, 3);
					$this_version_numerical = sprintf("%02d%02d%02d", $v1, $v2, $v3);
	
					$files[$this_version_numerical] = $this_version;
				}
			}
		}
		
		if (empty($files))
		{
			return ''; // No REDCap directories found
		}
	
		// Find the highest numbered key from the array and get its value
		ksort($files, SORT_NUMERIC);
		$this_version = array_pop($files);
		
		// give either the version number only or with the redcap version directory prefix added
		if ($justVersionNumber) {
			$nameRedcapVersion = $this_version;
		} else {
			$nameRedcapVersion = "redcap_v" . $this_version;
		}
	
		return $nameRedcapVersion;
	}

?>
