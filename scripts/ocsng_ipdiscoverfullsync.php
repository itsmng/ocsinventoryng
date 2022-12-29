<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2016 by the ocsinventoryng Development Team.

 https://github.com/pluginsGLPI/ocsinventoryng
 -------------------------------------------------------------------------

 LICENSE
      
 This file is part of ocsinventoryng.

 ocsinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocsinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

# Converts cli parameter to web parameter for compatibility
if (isset ($_SERVER["argv"]) && !isset ($argv)) {
    $argv = $_SERVER["argv"];
 }
 if ($argv) {
    for ($i = 1; $i < count($argv); $i++) {
       $it = explode("=", $argv[$i], 2);
       $it[0] = preg_replace('/^--/', '', $it[0]);
       if(isset($it[1])) {
          $_GET[$it[0]] = $it[1];
       } else {
          $_GET[$it[0]] = 1;
       }
    }
 }
 
 // Can't run on MySQL replicate
 $USEDBREPLICATE = 0;
 $DBCONNECTION_REQUIRED = 1;

 // MASS IMPORT for OCSNG
include('../../../inc/includes.php');

$_SESSION["glpicronuserrunning"] = $_SESSION["glpiname"] = 'ocsinventoryng';
// Check PHP Version - sometime (debian) cli version != module version
if (phpversion() < "5") {
   die("PHP version:" . phpversion() . " - " . "You must install at least PHP5.\n\n");
}
// Chech Memory_limit - sometine cli limit (php-cli.ini) != module limit (php.ini)
$mem = Toolbox::getMemoryLimit();
if (($mem > 0) && ($mem < (64 * 1024 * 1024))) {
   die("PHP memory_limit = " . $mem . " - " . "A minimum of 64Mio is commonly required for GLPI.'\n\n");
}

//Check if plugin is installed
$plugin = new Plugin();
if (!$plugin->isInstalled("ocsinventoryng")) {
   echo "Disabled plugin\n";
   exit (1);
}

if (!$plugin->isActivated("ocsinventoryng")) {
   echo "Disabled plugin\n";
   exit (1);
}

$thread_nbr = '';
$threadid = '';
$ocsservers_id = -1;
$fields = array();

//Get script configuration
$config = new PluginOcsinventoryngConfig();
$config->getFromDB(1);

if (!isset ($_GET["ocs_server_id"]) || ($_GET["ocs_server_id"] == '')) {
   $ocsservers_id = -1;
} else {
   $ocsservers_id = $_GET["ocs_server_id"];
}

if (isset($_GET["thread_nbr"]) || isset ($_GET["thread_id"])) {
   if (!isset($_GET["thread_id"])
      || ($_GET["thread_id"] > $_GET["thread_nbr"])
      || ($_GET["thread_id"] <= 0)
   ) {
      echo("Threadid invalid: threadid must be between 1 and thread_nbr\n\n");
      exit (1);
   }

   $thread_nbr = $_GET["thread_nbr"];
   $threadid = $_GET["thread_id"];

   echo "=====================================================\n";
   echo "\tThread #$threadid: starting ($threadid/$thread_nbr)\n";
} else {
   $thread_nbr = -1;
   $threadid = -1;
}

//Get the script's process identifier
if (isset ($_GET["process_id"])) {
   $fields["processid"] = $_GET["process_id"];
}

$ipd_to_inventory = array(
    "full",
    "identified",
    "noninventoried"
);

if(isset($_GET["ipd_to_inventory"]) && in_array($_GET["ipd_to_inventory"], $ipd_to_inventory)) {
    $fields["ipd_to_inventory"] = $_GET["ipd_to_inventory"];
} elseif(isset($_GET["ipd_to_inventory"]) && !in_array($_GET["ipd_to_inventory"], $ipd_to_inventory)) {
    echo("ipd_to_inventory invalid: ipd_to_inventory must be full, identified or noninventoried\n\n");
    exit (1);
} else {
    $fields["ipd_to_inventory"] = "full";
}

$thread = new PluginOcsinventoryngThread();

//Prepare datas to log in db
$fields["start_time"] = date("Y-m-d H:i:s");
$fields["threadid"] = $threadid;
$fields["status"] = PLUGIN_OCSINVENTORYNG_STATE_STARTED;
$fields["plugin_ocsinventoryng_ocsservers_id"] = $ocsservers_id;
$fields["imported_ipdiscover_number"] = 0;
$fields["notupdated_ipdiscover_number"] = 0;
$fields["failed_imported_ipdiscover_number"] = 0;
$fields["synchronized_ipdiscover_number"] = 0;
$fields["removed_ipdiscover_number"] = 0;
$fields["failed_removed_ipdiscover_number"] = 0;
$fields["total_number_machines"] = 0;
$fields["error_msg"] = '';
$force = (isset($_GET['force'])) ? true : false;
$tid = $threadid;

if ($ocsservers_id != -1) {
    $result = launchSync($tid, $ocsservers_id, $thread_nbr, $threadid, $fields, $config, $force);
    if ($result) {
        $fields = $result;
    }
} else {
    //Import from all the OCS servers
    $query = "SELECT `id`, `name`
                    FROM `glpi_plugin_ocsinventoryng_ocsservers`
                    WHERE `is_active`
                    AND `use_massimport`";
    $res = $DB->query($query);

    while ($ocsservers = $DB->fetchArray($res)) {
        $result = launchSync($tid, $ocsservers["id"], $thread_nbr, $threadid, $fields, $config, $force);
        if ($result) {
            $fields = $result;
        }
    }
}

echo "\tThread #" . $threadid . ": done!!\n";
echo "=====================================================\n";
//}

/**
 * @param $threads_id
 * @param $ocsservers_id
 * @param $thread_nbr
 * @param $threadid
 * @param $fields
 * @param $config
 *
 * @return bool|mixed
 */
function launchSync($threads_id, $ocsservers_id, $thread_nbr, $threadid, $fields, $config, $force = false) {

   $server = new PluginOcsinventoryngServer();
   $ocsserver = new PluginOcsinventoryngOcsServer();

   if (!PluginOcsinventoryngOcsServer::checkOCSconnection($ocsservers_id)) {
      echo "\tThread #" . $threadid . ": cannot contact server\n\n";
      return false;
   }

   if (!$ocsserver->getFromDB($ocsservers_id)) {
      echo "\tThread #" . $threadid . ": cannot get OCS server information\n\n";
      return false;
   }

   $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);

   return importIPDFromOcsServer($threads_id, $cfg_ocs, $server, $thread_nbr, $threadid, $fields, $config, $force);
}




function importIPDFromOcsServer($threads_id, $cfg_ocs, $server, $thread_nbr, $threadid, $fields, $config, $force = false) {
   global $DB;

   echo "\tThread #" . $threadid . ": synchronize IpDiscover objects from server: '" . $cfg_ocs["name"] . "'\n";

   $multiThread = false;
   if ($threadid != -1 && $thread_nbr > 1) {
      $multiThread = true;
   }

   $ocsServerId = $cfg_ocs['id'];
   $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($ocsServerId);

   $ocsResult = $ocsClient->getIpDiscover($ocsServerId, $fields["ipd_to_inventory"]);
   $ocsImported = $ocsClient->getIpDiscoverAlreadyImported($ocsServerId, $fields["ipd_to_inventory"], $force);
   
   //Update Ipd objects
   if($ocsImported['TOTAL_COUNT'] != 0) {
      foreach ($ocsImported['IPDISCOVER'] as $mac => $ipdDatas) {
         if($fields["ipd_to_inventory"] != 'full') {
            $action = PluginOcsinventoryngIpdiscoverOcslinkrework::updateIpDiscover($ipdDatas, $ocsServerId, ["inventory_type" => $fields["ipd_to_inventory"]]);
            PluginOcsinventoryngOcsProcess::manageImportStatistics($fields, $action['status']);
         } else {
            foreach($ipdDatas as $addmac => $ipdDatasBis) {
               $action = PluginOcsinventoryngIpdiscoverOcslinkrework::updateIpDiscover($ipdDatasBis, $ocsServerId, ["inventory_type" => $mac]);
               PluginOcsinventoryngOcsProcess::manageImportStatistics($fields, $action['status']);
            }
         }  
      }
   }

   // Remove before insert
   if(isset($ocsImported['UNKNOW_IPD'])) {
      foreach($ocsImported['UNKNOW_IPD'] as $mac) {
         $action = PluginOcsinventoryngIpdiscoverOcslinkrework::removeIpDiscover($mac, $ocsServerId);
         PluginOcsinventoryngOcsProcess::manageImportStatistics($fields, $action['status']);
      }
   }

   //Import Ipd objects
   if($ocsResult['TOTAL_COUNT'] != 0) {
      foreach ($ocsResult['IPDISCOVER'] as $mac => $ipdDatas) {
         if($fields["ipd_to_inventory"] != 'full') {
            $action = PluginOcsinventoryngIpdiscoverOcslinkrework::importIpDiscover($ipdDatas, $ocsServerId, ["inventory_type" => $fields["ipd_to_inventory"]]);
            PluginOcsinventoryngOcsProcess::manageImportStatistics($fields, $action['status']);
         } else {
            foreach($ipdDatas as $addmac => $ipdDatasBis) {
               $action = PluginOcsinventoryngIpdiscoverOcslinkrework::importIpDiscover($ipdDatasBis, $ocsServerId, ["inventory_type" => $mac]);
               PluginOcsinventoryngOcsProcess::manageImportStatistics($fields, $action['status']);
            }
         }  
      }
   }

   switch ($fields["ipd_to_inventory"]) {
      case 'noninventoried':
      case 'identified':
         $nb  = (isset($ocsResult['IPDISCOVER'])) ? count($ocsResult['IPDISCOVER']) : 0;
         $nbUpdate = (isset($ocsImported['IPDISCOVER'])) ? count($ocsImported['IPDISCOVER']) : 0;
         $nbRemoved = (isset($ocsImported['UNKNOW_IPD'])) ? count($ocsImported['UNKNOW_IPD']) : 0;
         break;
      default:
         $nb  = ((isset($ocsResult['IPDISCOVER']['noninventoried'])) ? count($ocsResult['IPDISCOVER']['noninventoried']) : 0) + ((isset($ocsResult['IPDISCOVER']['identified'])) ? count($ocsResult['IPDISCOVER']['identified']) : 0);
         $nbUpdate  = ((isset($ocsImported['IPDISCOVER']['noninventoried'])) ? count($ocsImported['IPDISCOVER']['noninventoried']) : 0) + ((isset($ocsImported['IPDISCOVER']['identified'])) ? count($ocsImported['IPDISCOVER']['identified']) : 0);
         $nbRemoved = (isset($ocsImported['UNKNOW_IPD'])) ? count($ocsImported['UNKNOW_IPD']) : 0;
         break;
   }

   echo "\tThread #$threadid: $nb object(s) imported\n";
   echo "\tThread #$threadid: $nbUpdate object(s) updated\n";
   echo "\tThread #$threadid: $nbRemoved object(s) removed\n";

   $fields["total_number_machines"] += $nb + $nbUpdate;

   return $fields;
}