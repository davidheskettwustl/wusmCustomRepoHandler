<?php
namespace WashingtonUniversity\WusmRepoHandlerExternalModule;

require_once APP_PATH_DOCROOT . '/Config/init_global.php';
include_once 'WusmRepoHandlerExternalModule.php';

$testWusmRepoHandlerExternalModule = new WusmRepoHandlerExternalModule();

$testWusmRepoHandlerExternalModule->displayExternalModulesPage();

?>
