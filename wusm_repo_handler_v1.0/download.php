<?php
namespace WashingtonUniversity\WusmRepoHandlerExternalModule;

require_once APP_PATH_DOCROOT . '/Config/init_global.php';
include_once 'WusmRepoHandlerExternalModule.php';

$repoEm = new WusmRepoHandlerExternalModule();

$repoEm->methodInitialize();

$download_module_id = $repoEm->isGoodModuleId($_GET['download_module_id']);

// download_module_title
// download_module_name
// module_name

$bypass = false;
$sendUserInfo = false;

$returnStatus = $repoEm->downloadModule($download_module_id, $bypass, $sendUserInfo);

// the standard process goes somewhere somehow to ExternalModules/templates/globals.php  the javascript piece.
// we probably should not really do that as we have changed diverted from typical process flow path logic.
// maybe we could work it all back in, or leave it simple and avoid some potential mishaps with links and such.
// replicates in part, ExternalModules/ajax/download-module.php

?>
