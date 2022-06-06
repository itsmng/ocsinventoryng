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


include('../../../inc/includes.php');

Session::checkRight("plugin_ocsinventoryng", UPDATE);

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "importipdiscover");

$plugin = new Plugin();
if ($plugin->isActivated("ocsinventoryng")) {
    $ipd = new PluginOcsinventoryngIpdiscoverOcslink();
    $ipdRework = new PluginOcsinventoryngIpdiscoverOcslinkrework();

    $_SESSION["ocs_importipdiscover"]['statistics']["imported_ipdiscover_number"] = 0;
    $_SESSION["ocs_importipdiscover"]['statistics']["notupdated_ipdiscover_number"] = 0;
    $_SESSION["ocs_importipdiscover"]['statistics']["failed_imported_ipdiscover_number"] = 0;
    $_SESSION["ocs_importipdiscover"]['statistics']["synchronized_ipdiscover_number"] = 0;
    $_SESSION["ocs_importipdiscover"]['statistics']["removed_ipdiscover_number"] = 0;
    $_SESSION["ocs_importipdiscover"]['statistics']["failed_removed_ipdiscover_number"] = 0;
    $_SESSION["ocs_importipdiscover"]['statistics']["total_number_machines"] = 0;

    if (!isset($_GET['action'])) {
        $_GET['action'] = "import";
    }

    if (isset($_GET["ip"]) || isset($_POST["ip"])) {
        $ocsServerId   = $_SESSION["plugin_ocsinventoryng_ocsservers_id"];
        $status        = $_GET["status"];
        $glpiListLimit = $_SESSION["glpilist_limit"];
        $ipAdress      = "";

        if (isset($_GET["ip"])) {
            $ipAdress = $_GET["ip"];
        } else {
            $ipAdress = $_POST["ip"];
        }

        $subnet           = PluginOcsinventoryngIpdiscoverOcslink::getSubnetIDbyIP($ipAdress);
        $hardware         = [];
        $knownMacAdresses = $ipd->getKnownMacAdresseFromGlpi('noninventoried');

        if (isset($status)) {
            $hardware = $ipdRework->getHardware($ipAdress, $ocsServerId, $status, $knownMacAdresses);
            $lim      = count($hardware);
            if ($lim > $glpiListLimit) {
                if (isset($_GET["start"])) {
                    $ipdRework->showHardware($hardware, $glpiListLimit, intval($_GET["start"]), $ipAdress, $status, $subnet, $_GET['action']);
                } else {
                    $ipdRework->showHardware($hardware, $glpiListLimit, 0, $ipAdress, $status, $subnet, $_GET['action']);
                }
            } else {
                if (isset($_GET["start"])) {
                    $ipdRework->showHardware($hardware, $lim, intval($_GET["start"]), $ipAdress, $status, $subnet, $_GET['action']);
                } else {
                    $ipdRework->showHardware($hardware, $lim, 0, $ipAdress, $status, $subnet, $_GET['action']);
                }
            }
        }
    }

    if(isset($_POST['IdentifyAndImport']) && isset($_POST["mactoimport"]) && sizeof($_POST["mactoimport"]) > 0) {
        // NON-IMPORTED
        if(isset($_GET["b"]) && $_GET["b"][1] == "nonimported") {
            $macAdresses = $_POST["mactoimport"];
            $equipmentToImport = [];
            $ipAdress = $b[0];
            $status   = $b[1];

            foreach($macAdresses as $key => $mac) {
                $equipmentToImport = PluginOcsinventoryngIpdiscoverOcslinkrework::getEquipmentDetails(key($mac), $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);

                if(!empty($equipmentToImport)) {
                    $equipmentToImport["description"] = $_POST["itemsdescription"][$key];
                    $equipmentToImport["type"] = $_POST["ocsitemstype"][$key];
                    $equipmentToImport["user"] = $_SESSION["glpiname"];

                    $action = PluginOcsinventoryngIpdiscoverOcslinkrework::importIpDiscover($equipmentToImport, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], ["inventory_type" => "identified"]);
                    if($action['status'] == PluginOcsinventoryngOcsProcess::IPDISCOVER_IMPORTED) {
                        PluginOcsinventoryngIpdiscoverOcslinkrework::updateOCSLink($equipmentToImport, $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
                    }
                    PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
                }
            }

            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
            echo "<div class='center b'><br>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "?ip=$ipAdress&status=$status'>" . __('Back') . "</a></div>";
        }
        // NON-INVENTORIED
        if(isset($_GET["b"]) && $_GET["b"][1] == "noninventoried") {
            $macAdresses = $_POST["mactoimport"];
            $equipmentToIdentified = [];
            $ipAdress = $_GET["b"][0];
            $status   = $_GET["b"][1];

            foreach($macAdresses as $key => $ocsLinkIds) {
                $mac = PluginOcsinventoryngIpdiscoverOcslinkrework::cleanNonIdentified(key($ocsLinkIds));
                $equipmentToImport = PluginOcsinventoryngIpdiscoverOcslinkrework::getEquipmentDetails($mac, $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);

                if(!empty($equipmentToImport)) {
                    $equipmentToImport["description"] = $_POST["itemsdescription"][$key];
                    $equipmentToImport["type"] = $_POST["ocsitemstype"][$key];
                    $equipmentToImport["user"] = $_SESSION["glpiname"];

                    $action = PluginOcsinventoryngIpdiscoverOcslinkrework::importIpDiscover($equipmentToImport, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], ["inventory_type" => "identified"]);
                    if($action['status'] == PluginOcsinventoryngOcsProcess::IPDISCOVER_IMPORTED) {
                        PluginOcsinventoryngIpdiscoverOcslinkrework::updateOCSLink($equipmentToImport, $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
                    }
                    PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
                }

            }

            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
            echo "<div class='center b'><br>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "?ip=$ipAdress&status=$status'>" . __('Back') . "</a></div>";
        }
    }
}

Html::footer();