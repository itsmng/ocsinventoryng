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
//$notimport = new PluginOcsinventoryngNotimportedcomputer();
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
$thread = new PluginOcsinventoryngThread();

//Prepare datas to log in db
$fields["start_time"] = date("Y-m-d H:i:s");
$fields["threadid"] = $threadid;
$fields["status"] = PLUGIN_OCSINVENTORYNG_STATE_STARTED;
$fields["plugin_ocsinventoryng_ocsservers_id"] = $ocsservers_id;
$fields["synchronized_snmp_number"] = 0;
$fields["imported_snmp_number"] = 0;
$fields["synchronized_snmp_number"] = 0;
$fields["notupdated_snmp_number"] = 0;
$fields["failed_imported_snmp_number"] = 0;
$fields["total_number_machines"] = 0;
$fields["error_msg"] = '';
//TODO create thread & update it ?
//$tid = $thread->add($fields);
//$fields["id"] = $tid;
$tid = $threadid;

if ($ocsservers_id != -1) {
   $result = launchSync($tid, $ocsservers_id, $thread_nbr, $threadid, $fields, $config);
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
      $result = launchSync($tid, $ocsservers["id"], $thread_nbr, $threadid, $fields, $config);
      if ($result) {
         $fields = $result;
      }
   }
}

//Write in db all the informations about this thread
// TODO create thread & update it ?
//$fields["total_number_machines"] = $fields["synchronized_snmp_number"]
//   + $fields["notupdated_snmp_number"];
//$fields["end_time"] = date("Y-m-d H:i:s");
//$fields["status"] = PLUGIN_OCSINVENTORYNG_STATE_FINISHED;
//$fields["error_msg"] = "";
//$thread->update($fields);

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
function launchSync($threads_id, $ocsservers_id, $thread_nbr, $threadid, $fields, $config)
{

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

   return importSNMPFromOcsServer($threads_id, $cfg_ocs, $server, $thread_nbr,
      $threadid, $fields, $config);
}


/**
 * @param $threads_id
 * @param $cfg_ocs
 * @param $server
 * @param $thread_nbr
 * @param $threadid
 * @param $fields
 * @param $config
 *
 * @return mixed
 */
function importSNMPFromOcsServer($threads_id, $cfg_ocs, $server, $thread_nbr,
                                 $threadid, $fields, $config)
{
   global $DB;

   echo "\tThread #" . $threadid . ": synchronize SNMP objects from server: '" . $cfg_ocs["name"] . "'\n";

   $multiThread = false;
   if ($threadid != -1 && $thread_nbr > 1) {
      $multiThread = true;
   }

   $ocsServerId = $cfg_ocs['id'];
   $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($ocsServerId);

   $ocsResult = $ocsClient->getSnmpRework($ocsServerId);
   $ocsImported = $ocsClient->getSnmpReworkAlreadyImported($ocsServerId);

   $nbUpdate = 0;
   $nbNotUpdated = 0;
   $nbImported = 0;
   $nbImportFailed = 0;
   
   //Update SNMP objects
   if (isset($ocsImported['SNMP'])) {
      foreach ($ocsImported['SNMP'] as $ID => $snmpids) {
         foreach ($snmpids as $key => $snmpid) {
            $id = $ID . "_" . $snmpid['ID'];
            $action = PluginOcsinventoryngSnmplinkRework::updateSnmp($id, $ocsServerId);
            if($action['status'] == 11) $nbUpdate++;
            if($action['status'] == 14) $nbNotUpdated++;
            PluginOcsinventoryngOcsProcess::manageImportStatistics($fields, $action['status']);
         }
      }
   }

   if (isset($ocsResult['SNMP'])) {
      //Import SNMP objects
      foreach ($ocsResult['SNMP'] as $ID => $snmpids) {
         foreach ($snmpids as $key => $snmpid) {
            $id = $ID . "_" . $snmpid['ID'];
            $action = PluginOcsinventoryngSnmplinkRework::importSnmp($id, $ocsServerId, []);
            if($action['status'] == 10) $nbImported++;
            if($action['status'] == 13) $nbImportFailed++;
            PluginOcsinventoryngOcsProcess::manageImportStatistics($fields, $action['status']);
         }
      }
   }


   echo "\tThread #$threadid: $nbUpdate object(s) updated\n";
   echo "\tThread #$threadid: $nbNotUpdated object(s) not updated\n";
   echo "\tThread #$threadid: $nbImported object(s) imported\n";
   echo "\tThread #$threadid: $nbImportFailed object(s) failed import\n";

   $fields["total_number_machines"] += $nbUpdate + $nbImported;

   return $fields;
}