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

if (isset($_POST["force_ocssnmp_resynch"])) {
   $item = new $_POST['itemtype']();
   $item->check($_POST['items_id'], UPDATE);

   PluginOcsinventoryngSnmplinkrework::updateSnmp($_POST["id"], $_POST["plugin_ocsinventoryng_ocsservers_id"]);
   Html::back();

} else if (isset($_GET["delete_link"])) {
   $link = new PluginOcsinventoryngSnmplinkrework();
   $links = $link->find([['ocs_snmp_type_id' => $_GET["snmpocstype"]]]);
   foreach ($links as $key => $value) {
      $link->delete(['id' => $value['id']]);
   }
   Html::back();

} else if ((isset($_POST["SelectGLPI"]) && isset($_POST["SelectOCS"])) || (isset($_GET["SelectGLPI"]) && isset($_GET["SelectOCS"]))) {
   Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "synclink");
   PluginOcsinventoryngSnmplinkrework::addSnmpLinks();
   Html::footer();

} else if (isset($_GET["valid"])) {
   $params = $_GET;

   PluginOcsinventoryngSnmplinkrework::createSnmpLinks($params);

   Html::redirect("{$CFG_GLPI['root_doc']}/plugins/ocsinventoryng/front/ocsngsnmprework.link.php");
} else if (isset($_GET["update"])) {
   if (!isset($_GET["snmpocstype"])) {
      Html::redirect("{$CFG_GLPI['root_doc']}/plugins/ocsinventoryng/front/ocsngsnmprework.link.php");
   }

   Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "synclink");

   $link = new PluginOcsinventoryngSnmplinkrework();
   $links = $link->find([['ocs_snmp_type_id' => $_GET["snmpocstype"]]]);

   $params = [];

   foreach ($links as $key => $value) {
      $params[$value['glpi_col']] = $value['ocs_snmp_type_id'] . "_" . $value['ocs_snmp_label_id'] . "_" . ($value['is_reconsiliation'] == 0 ? "" : 1);
      $_GET['SelectGLPI'] = $value['object'];
      $_GET['SelectOCS'] = $value['ocs_snmp_type_id'];
   }

   PluginOcsinventoryngSnmplinkrework::addSnmpLinks($params);

   Html::footer();
} else {
   Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "synclink");

   PluginOcsinventoryngSnmplinkrework::addSnmp();

   Html::footer();
}
