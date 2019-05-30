# WUSM Repo Handler

An external module for REDCap to allow use of Local External Module Repositories.  The local EM Repo would be set up on another REDCap server internal to your organization.
This mimics the standard REDCap EM Manager for retrieval of zip files to add external modules with the difference that it goes to your configured repository URL location.
********************************************************************************

## Getting Started

Enable the module and set the Custom Repo Url to your local repository, change the Custom Repo Key, and set the Custom Repo Main Dir to the directory where you have your repo server directory.

There is naturally set up for the local EM repo involved here where the above configuration parameters will make more sense.

Please note this only facilitates acquiring External Modules from your internal custom repository pulling zip files and that all the other External Module management is left to the standard processes within the standard External Module Management page.
********************************************************************************

### Prerequisites

You will first need to set up a whole new server with REDCap and then place the Custom Repo Server code there.
You MAY use an existing server, however you will need access to place the zip files and make some configuration changes REQUIRED.

On your local external module repository server, add a directory such as "customrepo" and put the Custom Repo Server code there.

Edit the configuration files as described in that separate code base.
********************************************************************************

```
For example:  
	/redcap/customrepo
	
	which will then have some directories as the following
	/redcap/customrepo/modules
	/redcap/customrepo/modules/filesarea   (This is where you put your zipped EM files)
	
	/redcap/customrepo/resources
	/redcap/customrepo/resources/img

```
********************************************************************************

### Installing

Have both the 'client' side and 'server' side have the same custom repo key.


```
Example:  Custom Repo Key:   testingkey

Repo server, configurationKeys.php  
	$customRepoSecretKey = 'testingkey';  // CUSTOMIZE

```

Also make sure, the Custom Repo Main Dir configuration matchs the directory on the Local Repo.


```
Custom Repo Main Dir:   customrepo

Repo server /redcap/customrepo/

```

********************************************************************************

### Operation

Click on the External module menu item:  **REPO: Custom EM Download**

There will be a menu item on the left side bar under "External Modules" titled:  
* "REPO: Custom EM Download"

You should get a simple page with a button link which leads to your Custom Repo Server.

Also there will be a link back to the standard EM Manager page.

### Set up issues

If you get a blank page.
* You should check the configuration.php file on the REPO site (/customrepo/modules/configuration.php and configurationKeys.php) and make sure settings are correct.
* Also make sure this external module has its settings correct. 
1. Custom Repo Url: URL to your repo, no trailing slash
2. Custom Repo Key: (which matches what is in configurationKeys on the REPO site)
3. Custom Repo Main Dir (The server side directory): (customrepo)
4. server times match to the hour. (a security feature uses date time to limit access)

********************************************************************************

### Deployment

Regular external module enable and configuration.

A separate install of the customrepo code on a different REDCap server assigned as your repository.

********************************************************************************
## Information
 
### Diagrams
An overview of the process relationships.  [Overview](?prefix=wusm_repo_handler&page=docs/YourCustomRepoSetup.pdf) 

An overview of docs.  [THIS DOC](?prefix=wusm_repo_handler&page=docs/README.html) 

********************************************************************************
### Authors

* **David Heskett** - *Initial work* - [](https://github.com/davidheskettwustl)

### License

This project is licensed under the MIT License - see the [LICENSE](?prefix=wusm_repo_handler&page=LICENSE.md) file for details

### Acknowledgments

* Inspired by REDCap, Rob Taylor, Andy Martin, Kevan Essmyer, and folks on the REDCap consortium asking for a feature such as this.

