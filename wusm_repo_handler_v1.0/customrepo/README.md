********************************************************************************
# CUSTOM Repo Server

A supporting section of code for REDCap to allow use of Local External Module Repositories.  
The local EM Repo would be set up on another REDCap server internal to your organization.

This mimics the standard REDCap EM Manager for retrieval of zip files to add external modules with the difference that it goes to your configured local repository URL location.

* Created at Washington University School of Medicine Institute for Informatics
* Author: David L. Heskett

********************************************************************************

## Getting Started


### Setup

#### Basics

##### Server set up

1. Create CUSTOM REDCap Repo server

2. Add directory under redcap, /customrepo/  

3. Add the REPO handler files.

4. Add your External Module zip files to /redcap/customrepo/modules/filesarea/

##### Create project

* A project on the Repo server contains controlling information and data for 
managing the EM Repo.
* You will need access to create and update project information.

1. Create a project and use data dictionary to configure the project.  
* [Repo Data Dictionary] (/customrepo/modules/RepoExternalModulesProject_DataDictionary.csv) 
* <a href="/customrepo/modules/RepoExternalModulesProject_DataDictionary.csv">Repo Data Dictionary</a>

2. Create a bookmark to the generate listing process.
* Example: REPO: Generate Listing - http://wwwrepo/customrepo/modules/generatelist.php

3. Create a bookmark to show the listing.
* Example: REPO: Show Listing - http://wwwrepo/customrepo/modules/getlisting.php

4. Note the PROJECT ID.

##### Configuration

1. Edit configuration file: /redcap/customrepo/modules/configuration.php
	1. Change the $customRepoProjectId = PROJECT_ID_VALUE; 
	* Example: $customRepoProjectId = 123;
	2. Change the $customInstituteName = '';  
	* Example: $customInstituteName = 'Your Institute Name Here';
	3. Change the $customEmailSupport = '';  
	* Example: $customEmailSupport = 'helpdesk@yoursite.edu';
	4. Change the $instituteRepoCustomText = '';  
	* Example: $instituteRepoCustomText = 'Department';
2. Edit the configurationKeys file: /redcap/customrepo/modules/configurationKeys.php
	1. Change the $customRepoSecretKey = 'testingkey';  // CUSTOMIZE
	* Example: $customRepoSecretKey = 'yourdifferenttexthere';

## Usage

### Entry of a Modules data and Maintaining the listing

#### ADD or Edit records

1. Go to the project and add entries for your EMs 
* (module short name) the EM snake case short name without the version number
* (module system version) the correct latest version number of each related zip file<
* and other information
* finally (active flag) the availability of the module, set to "Yes"
* and set to Complete and SAVE

2. Add your External Module zip file to /redcap/customrepo/modules/filesarea/

3. When done with all the add and edit changes, run the <a href="/customrepo/modules/generatelist.php" target="_blank">REPO: Generate Listing</a>

4. Verify you have a good listing as expected.  <a href="/customrepo/modules/getlisting.php" target="_blank">REPO: Show Listing</a>

When you have a new version, edit the related EM record and update the version number (module system version) and other information as needed if changed. SAVE it.

**REMEMBER** to again run the REPO Generate Listing!

## Notes
* Requires a web server with REDCap and access http(s) from DEV, QA, PROD 
to communicate to web service hosted on the server allowing External Module
zip downloads and management.  

* System will be using http(s) post and get to request data about available external 
modules, and what client has, to compare versions and other information, and 
transfer zip files. (Content-Type: application/zip).

* Will need access to place zip files on to the server, 
path will be, /redcap/customrepo/modules/filesarea/*.zip

* Web service uses a token to restrict access to service along with other data elements 
being required to present any data to client requests.

********************************************************************************
