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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginOcsinventoryngSnmplinkrework
 */
class PluginOcsinventoryngSnmplinkrework extends CommonDBTM {

   static $snmptypes = ['Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];
   static $rightname = "plugin_ocsinventoryng";
   /** @const */
   private static $CARTRIDGE_COLOR_CYAN    = ['cyan'];
   private static $CARTRIDGE_COLOR_MAGENTA = ['magenta'];
   private static $CARTRIDGE_COLOR_YELLOW  = ['yellow', 'jaune'];
   private static $CARTRIDGE_COLOR_BLACK   = ['black', 'noir'];
   const OTHER_DATA = 'other';

   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param $item               CommonGLPI object
    * @param $withtemplate (default 0)
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (in_array($item->getType(), self::$snmptypes)
          && $this->canView()) {
         if ($this->getFromDBByCrit(['items_id' => $item->getID(), 'itemtype' => $item->getType()])) {
            return __('OCSNG SNMP', 'ocsinventoryng');
         }
      } else if ($item->getType() == "PluginOcsinventoryngOcsServer") {

         if (PluginOcsinventoryngOcsServer::checkOCSconnection($item->getID())
             && PluginOcsinventoryngOcsServer::checkVersion($item->getID())
             && PluginOcsinventoryngOcsServer::checkTraceDeleted($item->getID())) {
            $client  = PluginOcsinventoryngOcsServer::getDBocs($item->getID());
            $version = $client->getTextConfig('GUI_VERSION');
            $snmp    = ($client->getIntConfig('SNMP') > 0)?true:false;

            if ($version < PluginOcsinventoryngOcsServer::OCS2_1_VERSION_LIMIT && $snmp) {
               return __('SNMP Import', 'ocsinventoryng');
            }
         }

      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum (default 1)
    * @param $withtemplate (default 0)
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if (in_array($item->getType(), self::$snmptypes)) {

         //self::showForItem($item);
         $ID = $item->getField('id');
         // j'affiche le formulaire
         $prof = new self();
         $prof->showForm($ID);

      } else if ($item->getType() == "PluginOcsinventoryngOcsServer") {

         $conf = new self();
         $conf->ocsFormSNMPImportOptions($item->getID());
      }
      return true;
   }

   /**
    * @param $ID
    *
    * @internal param $withtemplate (default '')
    * @internal param $templateid (default '')
    */
   function ocsFormSNMPImportOptions($ID) {

      $conf = new PluginOcsinventoryngOcsServer();
      $conf->getFromDB($ID);
      echo "<div class='center'>";
      echo "<form name='formsnmpconfig' id='formsnmpconfig' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer") . "' method='post'>";
      echo "<table class='tab_cadre_fixe'>\n";

      echo "<tr><th colspan ='4'>";
      echo __('All');

      echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
            function form_init_all(value) {
                if(value != -1) {
                  var selects = $("form[id='formsnmpconfig'] select");

                  $.each(selects, function(index, select){
                     if (select.name != "init_all") {
                       $(select).select2('val', value);
                     }
                  });
               }
            }
         </script>
JAVASCRIPT;
      $values = [-1 => Dropdown::EMPTY_VALUE,
                 0  => __('No'),
                 1  => __('Yes')];

      Dropdown::showFromArray('init_all', $values, [
         'width'     => '10%',
         'on_change' => "form_init_all(this.value);"
      ]);
      echo "</th></tr>";

      echo "<tr class='tab_bg_2'>\n";
      echo "<td class='top'>\n";

      echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
         function accordions(id, openall) {
             if(id == undefined){
                 id  = 'accordions';
             }
             jQuery(document).ready(function () {
                 $("#"+id).accordion({
                     collapsible: true,
                     //active:[0, 1, 2, 3],
                     //heightStyle: "content"
                 });
                 //if (openall) {
                     //$('#'+id +' .ui-accordion-content').show();
                 //}
             });
         };
         </script>
JAVASCRIPT;

      echo "<div id='accordions'>";

      echo "<h2><a href='#'>" . __('General SNMP import options', 'ocsinventoryng') . "</a></h2>";
      echo "<div>";
      echo "<table class='tab_cadre' width='100%'>";
      echo "<tr><th colspan='4'>" . __('General SNMP import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP name', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_name", $conf->fields["importsnmp_name"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Import SNMP serial', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_serial", $conf->fields["importsnmp_serial"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP comment', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_comment", $conf->fields["importsnmp_comment"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Import SNMP contact', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_contact", $conf->fields["importsnmp_contact"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP location', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_location", $conf->fields["importsnmp_location"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Import SNMP domain', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_domain", $conf->fields["importsnmp_domain"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP manufacturer', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_manufacturer", $conf->fields["importsnmp_manufacturer"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Create network port', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_createport", $conf->fields["importsnmp_createport"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . __('Computer SNMP import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP network cards', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computernetworkcards", $conf->fields["importsnmp_computernetworkcards"]);

      echo "</td><td class='center'>" . __('Import SNMP memory', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computermemory", $conf->fields["importsnmp_computermemory"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP processors', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computerprocessors", $conf->fields["importsnmp_computerprocessors"]);

      echo "</td><td class='center'>" . __('Import SNMP softwares', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computersoftwares", $conf->fields["importsnmp_computersoftwares"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP virtual machines', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computervm", $conf->fields["importsnmp_computervm"]);
      echo "</td><td class='center'>" . __('Import SNMP volumes', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computerdisks", $conf->fields["importsnmp_computerdisks"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . __('Printer SNMP import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP last pages counter', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_last_pages_counter", $conf->fields["importsnmp_last_pages_counter"]);

      echo "</td><td class='center'>" . __('Import SNMP printer memory', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_printermemory", $conf->fields["importsnmp_printermemory"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . __('Networking SNMP import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP firmware', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_firmware", $conf->fields["importsnmp_firmware"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Import SNMP Power supplies', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_power", $conf->fields["importsnmp_power"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP Fans', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_fan", $conf->fields["importsnmp_fan"]);
      echo "</td><td colspan='2'></td></tr>\n";
      echo "</table><br>";
      echo "</div>";

      //Components

      echo "<h2><a href='#'>" . __('General SNMP link options', 'ocsinventoryng') . "</a></h2>";

      /******Link ***/
      echo "<div>";
      echo "<table class='tab_cadre' width='100%'>";
      echo "<tr><th colspan='4'>" . __('General SNMP link options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP name', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_name", $conf->fields["linksnmp_name"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Link SNMP serial', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_serial", $conf->fields["linksnmp_serial"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP comment', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_comment", $conf->fields["linksnmp_comment"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Link SNMP contact', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_contact", $conf->fields["linksnmp_contact"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP location', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_location", $conf->fields["linksnmp_location"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Link SNMP domain', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_domain", $conf->fields["linksnmp_domain"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP manufacturer', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_manufacturer", $conf->fields["linksnmp_manufacturer"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Create network port', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_createport", $conf->fields["linksnmp_createport"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . __('Computer SNMP link options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP network cards', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computernetworkcards", $conf->fields["linksnmp_computernetworkcards"]);

      echo "</td><td class='center'>" . __('Link SNMP memory', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computermemory", $conf->fields["linksnmp_computermemory"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP processors', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computerprocessors", $conf->fields["linksnmp_computerprocessors"]);

      echo "</td><td class='center'>" . __('Link SNMP softwares', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computersoftwares", $conf->fields["linksnmp_computersoftwares"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP virtual machines', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computervm", $conf->fields["linksnmp_computervm"]);
      echo "</td><td class='center'>" . __('Link SNMP volumes', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computerdisks", $conf->fields["linksnmp_computerdisks"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . __('Printer SNMP link options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP last pages counter', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_last_pages_counter", $conf->fields["linksnmp_last_pages_counter"]);

      echo "</td><td class='center'>" . __('Link SNMP printer memory', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_printermemory", $conf->fields["linksnmp_printermemory"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . __('Networking SNMP link options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP firmware', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_firmware", $conf->fields["linksnmp_firmware"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Link SNMP Power supplies', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_power", $conf->fields["linksnmp_power"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP Fans', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_fan", $conf->fields["linksnmp_fan"]);
      echo "</td><td colspan='2'></td></tr>\n";
      echo "</table>\n";
      echo "</div>";

      echo "</div>";

      echo "<script>accordions();</script>";

      echo "</td></tr>\n";

      if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'updateSNMP']);
         echo "</td></tr>";
      }

      echo "</table>\n";
      Html::closeForm();
      echo "</div>";
   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    */
   static function snmpMenu($plugin_ocsinventoryng_ocsservers_id) {
      global $CFG_GLPI, $DB;
      $ocsservers = [];
      $dbu = new DbUtils();
      $numberActiveServers = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]);
      if ($numberActiveServers > 0) {
         echo "<form action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php\"
                method='post'>";
         echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Choice of an OCSNG server', 'ocsinventoryng') .
              "</th></tr>\n";

         echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
         echo "<td class='center'>";
         $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                   LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                      ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   WHERE `profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . " 
                   AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`= 1
                   ORDER BY `name` ASC";

         foreach ($DB->request($query) as $data) {
            $ocsservers[] = $data['id'];
         }
         Dropdown::show('PluginOcsinventoryngOcsServer', ["condition"           => ["id" => $ocsservers],
                                                          "value"               => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                          "on_change"           => "this.form.submit()",
                                                          "display_emptychoice" => false]);
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='2' class ='center red'>";
         echo __('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng');
         echo "</td></tr>";
         echo "</table></div>";
         Html::closeForm();
      }
      $sql      = "SELECT `name`, `is_active`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                  ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
              WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`id` = " . $plugin_ocsinventoryng_ocsservers_id . " 
              AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'];
      $result   = $DB->query($sql);
      $isactive = 0;
      if ($DB->numrows($result) > 0) {
         $datas    = $DB->fetchArray($result);
         $name     = " : " . $datas["name"];
         $isactive = $datas["is_active"];
      }
      if ($isactive) {
         $client = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

         //if (Session::haveRight("plugin_ocsinventoryng", UPDATE) && $version > PluginOcsinventoryngOcsServer::OCS2_1_VERSION_LIMIT && $snmp) {
         //host not imported by thread
         echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
         echo "<tr><th colspan='4'>";
         echo __('OCSNG SNMP import', 'ocsinventoryng');
         echo "<br>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsserver.form.php?id=" . $plugin_ocsinventoryng_ocsservers_id . "&forcetab=PluginOcsinventoryngSnmpOcslink\$1'>";
         echo __('See Setup : SNMP Import before', 'ocsinventoryng');
         echo "</a>";
         echo "</th></tr>";

         // SNMP device link feature
         echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
                  <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsngsnmprework.link.php'>
                   <i style='color:firebrick' class='fas fa-arrow-alt-circle-down fa-3x' 
                           title=\"" . __s('Link SNMP devices to existing GLPI objects', 'ocsinventoryng') . "\"></i>
                     <br>" . __('Link SNMP devices to existing GLPI objects', 'ocsinventoryng') . "
                  </a></td>";

         echo "<td class='center b' colspan='2'>
               <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsngsnmprework.sync.php'>
                  <i style='color:cornflowerblue' class='fas fa-sync-alt fa-3x' 
                     title=\"" . __s('Synchronize snmp devices already imported', 'ocsinventoryng') . "\"></i>
                  <br>" . __('Synchronize snmp devices already imported', 'ocsinventoryng') . "
               </a></td>";
         echo "</tr>";

         //SNMP device import feature
         echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
             <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsngsnmprework.import.php'>
              <i style='color:cornflowerblue' class='fas fa-plus fa-3x' 
                           title=\"" . __s('Import new SNMP devices', 'ocsinventoryng') . "\"></i>
                <br>" . __('Import new SNMP devices', 'ocsinventoryng') . "
             </a></td>";

         echo "<td></td>";
         echo "</tr>";
         echo "</table></div>";
      }
   }

   /**
    * Show OcsLink of an item
    *
    * @param $item                   CommonDBTM object
    *
    * @return void
    * @throws \GlpitestSQLError
    * @internal param int|string $withtemplate integer  withtemplate param (default '')
    */
   static function showForItem(CommonDBTM $item) {
      global $DB;

      //$target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), self::$snmptypes)) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
             && $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_view", READ)) {

            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                      WHERE `items_id` = $items_id AND `itemtype` = '" . $item->getType() . "'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetchAssoc($result);
               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

               if (count($data)) {
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr class='tab_bg_1'><th colspan='2'>" . __('OCS Inventory NG SNMP Import informations', 'ocsinventoryng') . "</th>";
                  $linked = __('Imported object', 'ocsinventoryng');
                  if ($data["linked"]) {
                     $linked = __('Linked object', 'ocsinventoryng');
                  }
                  echo "<tr class='tab_bg_1'><td>" . __('Import date in GLPI', 'ocsinventoryng');
                  echo "</td><td>" . Html::convDateTime($data["last_update"]) . " (" . $linked . ")</td></tr>";

                  $linked_ids [] = $data['ocs_id'];
                  $ocsClient     = PluginOcsinventoryngOcsServer::getDBocs($data['plugin_ocsinventoryng_ocsservers_id']);
                  $ocsResult     = $ocsClient->getSnmpRework([
                                                          'MAX_RECORDS' => 1,
                                                          'FILTER'      => [
                                                             'IDS' => $linked_ids,
                                                          ]
                                                       ]);
                  $ocsResult = null;
                  if (isset($ocsResult['SNMP'])) {
                     if (count($ocsResult['SNMP']) > 0) {
                        foreach ($ocsResult['SNMP'] as $snmp) {
                           $LASTDATE = $snmp['META']['LASTDATE'];
                           $UPTIME   = $snmp['META']['UPTIME'];

                           echo "<tr class='tab_bg_1'><td>" . __('Last OCSNG SNMP inventory date', 'ocsinventoryng');
                           echo "</td><td>" . Html::convDateTime($LASTDATE) . "</td></tr>";

                           echo "<tr class='tab_bg_1'><td>" . __('Uptime', 'ocsinventoryng');
                           echo "</td><td>" . $UPTIME . "</td></tr>";

                           echo "<tr class='tab_bg_1 center'><td colspan='2'>";
                           $target = Toolbox::getItemTypeFormURL(__CLASS__);

                           Html::showSimpleForm($target, 'delete_link',
                                                _sx('button', 'Delete link', 'ocsinventoryng'),
                                                ['items_id'                            => $items_id,
                                                 'itemtype'                            => $item->getType(),
                                                 'id'                                  => $data["id"],
                                                 'plugin_ocsinventoryng_ocsservers_id' => $data["plugin_ocsinventoryng_ocsservers_id"]]);
                           echo "</td></tr>";

                        }
                        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                           echo "</table><table class='tab_cadre_fixe'>";
                           echo "<tr class='tab_bg_1'><th colspan='2'>" . __('SNMP Debug') . "</th>";
                           echo "<tr class='tab_bg_1'>";
                           echo "<td  colspan='2'>";
                           echo "<pre>";
                           print_r($ocsResult['SNMP']);
                           echo "</pre>";
                           echo "</td></tr>";
                           echo "</table>";
                        }

                     } else {
                        echo "</table>";
                     }
                  } else {
                     echo "</table>";
                  }
               }
            }
         }
      }
   }

   /**
    * if Printer purged
    *
    * @param $print   Printer object
    **/
   static function purgePrinter(Printer $print) {
      $snmp = new self();
      $snmp->deleteByCriteria(['items_id' => $print->getField("id"),
                               'itemtype' => $print->getType()]);

      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(['items_id' => $print->getField("id"),
                                     'itemtype' => $print->getType()]);
   }

   /**
    * if Printer purged
    *
    * @param $per   Peripheral object
    **/
   static function purgePeripheral(Peripheral $per) {
      $snmp = new self();
      $snmp->deleteByCriteria(['items_id' => $per->getField("id"),
                               'itemtype' => $per->getType()]);
      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(['items_id' => $per->getField("id"),
                                     'itemtype' => $per->getType()]);
   }

   /**
    * if NetworkEquipment purged
    *
    * @param NetworkEquipment $net
    *
    * @internal param NetworkEquipment $comp object
    */
   static function purgeNetworkEquipment(NetworkEquipment $net) {
      $snmp = new self();
      $snmp->deleteByCriteria(['items_id' => $net->getField("id"),
                               'itemtype' => $net->getType()]);
      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(['items_id' => $net->getField("id"),
                                     'itemtype' => $net->getType()]);

   }

   /**
    * if Computer purged
    *
    * @param $comp   Computer object
    **/
   static function purgeComputer(Computer $comp) {
      $snmp = new self();
      $snmp->deleteByCriteria(['items_id' => $comp->getField("id"),
                               'itemtype' => $comp->getType()]);
      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(['items_id' => $comp->getField("id"),
                                     'itemtype' => $comp->getType()]);

   }

   /**
    * if Phone purged
    *
    * @param $pho   Phone object
    **/
   static function purgePhone(Phone $pho) {
      $snmp = new self();
      $snmp->deleteByCriteria(['items_id' => $pho->getField("id"),
                               'itemtype' => $pho->getType()]);
      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(['items_id' => $pho->getField("id"),
                                     'itemtype' => $pho->getType()]);
   }

   /**
    * Show simple inventory information of an item
    *
    * @param $item                   CommonDBTM object
    *
    * @return void
    * @throws \GlpitestSQLError
    */
   static function showSimpleForItem(CommonDBTM $item) {
      global $DB;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), self::$snmptypes)) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
             && $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_view", READ)) {
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                      WHERE `items_id` = $items_id AND  `itemtype` = '" . $item->getType() . "'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetchAssoc($result);

               if (count($data)) {
                  echo "<tr class='tab_bg_1'><th colspan='4'>" . __('OCS Inventory NG SNMP Import informations', 'ocsinventoryng') . "</th>";

                  echo "<tr class='tab_bg_1'><td>" . __('Import date in GLPI', 'ocsinventoryng');
                  $linked = __('Imported object', 'ocsinventoryng');
                  if ($data["linked"]) {
                     $linked = __('Linked object', 'ocsinventoryng');
                  }
                  echo "</td><td>" . Html::convDateTime($data["last_update"]) . " (" . $linked . ")</td>";
                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<td class='center' colspan='2'>";
                     Html::showSimpleForm($target, 'force_ocssnmp_resynch',
                                          _sx('button', 'Force SNMP synchronization', 'ocsinventoryng'),
                                          ['items_id'                            => $items_id,
                                           'itemtype'                            => $item->getType(),
                                           'id'                                  => $data["id"],
                                           'plugin_ocsinventoryng_ocsservers_id' => $data["plugin_ocsinventoryng_ocsservers_id"]]);
                     echo "</td></tr>";

                  } else {
                     echo "<td colspan='2'></td>";
                  }
                  echo "</tr>";

                  $linked_ids [] = $data['ocs_id'];
                  $ocsClient     = PluginOcsinventoryngOcsServer::getDBocs($data['plugin_ocsinventoryng_ocsservers_id']);
                  $ocsResult     = $ocsClient->getSnmpRework([
                                                          'MAX_RECORDS' => 1,
                                                          'FILTER'      => [
                                                             'IDS' => $linked_ids,
                                                          ]
                                                       ]);
                  $ocsResult = null;
                  if (isset($ocsResult['SNMP'])) {
                     if (count($ocsResult['SNMP']) > 0) {
                        foreach ($ocsResult['SNMP'] as $snmp) {
                           $LASTDATE = $snmp['META']['LASTDATE'];
                           $UPTIME   = $snmp['META']['UPTIME'];

                           echo "<tr class='tab_bg_1'><td>" . __('Last OCSNG SNMP inventory date', 'ocsinventoryng');
                           echo "</td><td>" . Html::convDateTime($LASTDATE) . "</td>";

                           echo "<td>" . __('Uptime', 'ocsinventoryng');
                           echo "</td><td>" . $UPTIME . "</td></tr>";
                        }
                     }
                  }
                  if ($item->getType() == 'Printer') {
                     $cartridges = [];
                     $trays      = [];
                     if (isset($ocsResult['SNMP'])) {
                        if (count($ocsResult['SNMP']) > 0) {
                           foreach ($ocsResult['SNMP'] as $snmp) {
                              $cartridges = $snmp['CARTRIDGES'];
                              $trays      = $snmp['TRAYS'];
                           }
                        }
                     }
                     if (count($cartridges) > 0) {

                        $colors = [self::$CARTRIDGE_COLOR_BLACK,
                                   self::$CARTRIDGE_COLOR_CYAN,
                                   self::$CARTRIDGE_COLOR_MAGENTA,
                                   self::$CARTRIDGE_COLOR_YELLOW];

                        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Cartridges informations', 'ocsinventoryng') . "</th>";
                        foreach ($cartridges as $cartridge) {

                           if ($cartridge['TYPE'] != "wasteToner") {
                              echo "<tr class='tab_bg_1'>";
                              echo "<td>" . $cartridge['DESCRIPTION'] . "</td>";
                              $class = 'ocsinventoryng_toner_level_other';
                              foreach ($colors as $k => $v) {
                                 foreach ($v as $color) {

                                    if (preg_match('/(' . $color . ')/i', $cartridge['DESCRIPTION'], $matches)) {
                                       $class = 'ocsinventoryng_toner_level_' . strtolower($matches[1]);
                                       if ($matches[1] == "jaune") {
                                          $class = 'ocsinventoryng_toner_level_yellow';
                                       }
                                       if ($matches[1] == "noir") {
                                          $class = 'ocsinventoryng_toner_level_black';
                                       }
                                       break;
                                    }
                                 }
                              }
                              $percent = 0;
                              if ($cartridge['LEVEL'] > 0) {
                                 $percent = ($cartridge['LEVEL'] * 100) / $cartridge['MAXCAPACITY'];
                              }
                              echo "<td colspan='2'><div class='ocsinventoryng_toner_level'><div class='ocsinventoryng_toner_level $class' style='width:" . $percent . "%'></div></div></td>";
                              echo "<td>" . $cartridge['LEVEL'] . " %</td>";
                              echo "</tr>";
                           }
                        }

                     }
                     if (count($trays) > 0) {

                        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Trays informations', 'ocsinventoryng') . "</th>";
                        foreach ($trays as $tray) {

                           if ($tray['NAME'] != "Bypass Tray") {
                              echo "<tr class='tab_bg_1'>";
                              echo "<td>" . $tray['DESCRIPTION'] . "</td>";
                              $class   = 'ocsinventoryng_toner_level_other';
                              $percent = 0;
                              if ($tray['LEVEL'] > 0) {
                                 $percent = ($tray['LEVEL'] * 100) / $tray['MAXCAPACITY'];
                              }
                              echo "<td colspan='2'><div class='ocsinventoryng_toner_level'><div class='ocsinventoryng_toner_level $class' style='width:" . $percent . "%'></div></div></td>";
                              echo "<td>" . $tray['LEVEL'] . " / " . $tray['MAXCAPACITY'] . "</td>";
                              echo "</tr>";
                           }
                        }

                     }
                  }
               }
            }
         }
      }
      //IPDiscover Links
      if (in_array($item->getType(), PluginOcsinventoryngIpdiscoverOcslink::$hardwareItemTypes)) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
             //&& $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_view", READ)) {
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                      WHERE `items_id` = $items_id AND  `itemtype` = '" . $item->getType() . "'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetchAssoc($result);

               if (count($data)) {
                  echo "<tr class='tab_bg_1'><th colspan='4'>" . __('OCS Inventory NG IPDiscover Import informations', 'ocsinventoryng') . "</th>";

                  echo "<tr class='tab_bg_1'><td>" . __('Import date in GLPI', 'ocsinventoryng');
                  echo "</td><td>" . Html::convDateTime($data["last_update"]) . "</td><td colspan='2'></td></tr>";
               }
            }
         }
      }
   }

   // SNMP PART HERE

   /**
    * @param     $ocsid
    * @param     $plugin_ocsinventoryng_ocsservers_id
    * @param int $lock
    * @param     $params
    *
    * @return array
    */
   static function processSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params) {
      global $DB;

      /*if ($DB->numrows($result_glpi_plugin_ocsinventoryng_ocslinks)) {
         $datas = $DB->fetchArray($result_glpi_plugin_ocsinventoryng_ocslinks);
         //Return code to indicates that the machine was synchronized
         //or only last inventory date changed
         return self::updateSnmp($datas["id"], $plugin_ocsinventoryng_ocsservers_id);
      }*/
      return self::importSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params);
   }

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $params
    *
    * @return array
    */
   static function importSnmp($ocsids, $plugin_ocsinventoryng_ocsservers_id, $params) {
      global $DB;

      $split = explode("_", $ocsids);
      $ocsTable = $split[0];
      $ocsId = $split[1];

      $ocs_srv = $plugin_ocsinventoryng_ocsservers_id;

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs   = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      
      $queryReconciliation = "SELECT *, (SELECT GROUP_CONCAT(glpi_col) FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` b WHERE a.ocs_snmp_type_id = b.ocs_snmp_type_id AND is_reconsiliation = 1 ) AS reconciliation FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` a WHERE ocs_snmp_type_id = $ocsTable AND ocs_srv = $ocs_srv";
      $reconciliationData = $DB->query($queryReconciliation);
      if ($DB->numrows($reconciliationData)) {
         while ($lines = $DB->fetchAssoc($reconciliationData)) {
            if (!is_null($lines['reconciliation'])) {
               $rec = explode(',', $lines['reconciliation']);
            }
            
            $reconciliationArray[$lines['ocs_snmp_type_id']]['link'][$lines['glpi_col']] = $lines['ocs_snmp_label_id'];
            $reconciliationArray[$lines['ocs_snmp_type_id']]['object'] = $lines['object'];
            $reconciliationArray[$lines['ocs_snmp_type_id']]['reconciliation'] = $rec ?? null;

         }
      }


      $data = $ocsClient->getSnmpValueByTableAndId($ocsTable, $ocsId);
      $glpiTable = $reconciliationArray[$ocsTable]['object'];

      //get database column's types of selected object
      $queryTypes = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$glpiTable'";
      $resultTypes = $DB->query($queryTypes);

      $dataTypesArray = [];
      while ($dataTypes = $DB->fetchAssoc($resultTypes)) {
         $dataTypesArray[$dataTypes['COLUMN_NAME']] = $dataTypes['DATA_TYPE'];
      }

      // Check if reconciliation exist on base
      $queryCheck = "SELECT * FROM $glpiTable";
      $where = "";
      if (isset($reconciliationArray[$ocsTable]['reconciliation'])) {
         $where .= " WHERE ";
         foreach ($reconciliationArray[$ocsTable]['reconciliation'] as $reconciliationKey => $reconciliationValue) {
            $where .= $reconciliationValue . " = '" . $data[$reconciliationArray[$ocsTable]['link'][$reconciliationValue]] . "' AND ";
         }
         $where = rtrim($where, ' AND ');
         $force = false;
      } else {
         $force = true;
      }

      if ($where) {
         $queryCheck .= $where;
      }
      $queryCheck .= ";";

      $checkResult = $DB->query($queryCheck);
      if (($checkResult && !$DB->numrows($checkResult) > 0) || $force) {
         $insert = [];
         foreach ($reconciliationArray[$ocsTable]['link'] as $reconciliationKey => $reconciliationValue) {
            if (substr($reconciliationKey, -3) == '_id') {
               $insert[$reconciliationKey] = self::getObjectOrInsert($reconciliationKey, $data[$reconciliationValue]);
            } else {
               $value = self::convertStrToSqlValueBySqlType($data[$reconciliationValue], $dataTypesArray[$reconciliationKey]);
               if (isset($value) && $value != "") {
                  $insert[$reconciliationKey] = $value;
               }
            }
         }

         $DB->insertOrDie($glpiTable, $insert);
         return ['status' => PluginOcsinventoryngOcsProcess::SNMP_IMPORTED];
      } else {
         return ['status' => PluginOcsinventoryngOcsProcess::SNMP_FAILED_IMPORT];
      }
   }

   static function getObjectOrInsert($object, $value){
      global $DB;

      $object = "glpi_" . substr($object, 0, -3);

      $query = "SELECT * FROM $object WHERE name = '$value'";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $data = $DB->fetchAssoc($result);
         return $data['id'];
      } else {
         $insert = [];
         $insert['name'] = $value;
         $DB->insertOrDie($object, $insert);
         return $DB->insert_id();
      }
   }

   static function convertStrToSqlValueBySqlType($str, $type) {
      switch ($type) {
         case 'int':
            return (int)$str;
         case 'float':
            return (float)$str;
         case 'date':
            return date('Y-m-d', strtotime($str));
         case 'datetime':
            return date('Y-m-d H:i:s', strtotime($str));
            //return $str;
         case 'bool':
            return (bool)$str;
         case 'tinyint':
            return ($str == '1' || strtoupper($str) == "TRUE") ? 1 : 0;
         case 'timestamp':
            return date('Y-m-d H:i:s', strtotime($str));
         case 'decimal':
            return (float)$str;
         default:
            return $str;
      }
   }

   /**
    * @param      $plugin_ocsinventoryng_ocsservers_id
    * @param      $itemtype
    * @param int  $ID
    * @param      $ocsSnmp
    * @param      $loc_id
    * @param      $dom_id
    * @param      $action
    * @param bool $linked
    *
    * @param      $cfg_ocs
    *
    * @return int
    * @throws \GlpitestSQLError
    */
   static function addOrUpdatePrinter($itemtype, $ID, $ocsSnmp, $loc_id, $dom_id, $action, $linked, $cfg_ocs) {
      global $DB;

      $snmpDevice = new $itemtype();

      $input = [
         "is_dynamic"    => 1,
         "entities_id"   => (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0),
         "have_ethernet" => 1,
      ];

      //TODOSNMP TO TEST:
      //'PRINTER' =>
      // array (size=1)
      //   0 =>
      //     array (size=6)
      //       'SNMP_ID' => string '4' (length=1)
      //       'NAME' => string 'MP C3003' (length=8)
      //       'SERIALNUMBER' => string 'E1543632108' (length=11)
      //       'COUNTER' => string '98631 sheets' (length=12)
      //       'STATUS' => string 'idle' (length=4)
      //       'ERRORSTATE' => string '' (length=0)

      if (($cfg_ocs['importsnmp_name'] && $action == "add")
          || ($cfg_ocs['linksnmp_name'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
         $input["name"] = $ocsSnmp['META']['NAME'];
      }
      if (($cfg_ocs['importsnmp_contact'] && $action == "add")
          || ($cfg_ocs['linksnmp_contact'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_contact'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if (($cfg_ocs['importsnmp_comment'] && $action == "add")
          || ($cfg_ocs['linksnmp_comment'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_comment'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }
      if (($cfg_ocs['importsnmp_serial'] && $action == "add")
          || ($cfg_ocs['linksnmp_serial'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)) {
         $input["serial"] = $ocsSnmp['PRINTER'][0]['SERIALNUMBER'];
      }
      if (($cfg_ocs['importsnmp_last_pages_counter'] && $action == "add")
          || ($cfg_ocs['linksnmp_last_pages_counter'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_last_pages_counter'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_last_pages_counter'] && $linked)) {
         $input["last_pages_counter"] = $ocsSnmp['PRINTER'][0]['COUNTER'];
      }

      if ($loc_id > 0) {
         $input["locations_id"] = $loc_id;
      }
      if ($dom_id > 0) {
         $input["domains_id"] = $dom_id;
      }

      $id_printer = 0;

      if ($action == "add") {
         $id_printer = $snmpDevice->add($input, ['unicity_error_message' => true], $cfg_ocs['history_hardware']);
      } else {
         $id_printer  = $ID;
         $input["id"] = $ID;
         if ($snmpDevice->getFromDB($id_printer)) {
            $input["entities_id"] = $snmpDevice->fields['entities_id'];
         }

         $snmpDevice->update($input, $cfg_ocs['history_hardware'], ['unicity_error_message' => false,
                                                                    '_no_history'           => !$cfg_ocs['history_hardware']]);
      }

      if ($id_printer > 0
          && isset($ocsSnmp['MEMORIES'])
          && (($cfg_ocs['importsnmp_printermemory'] && $action == "add")
              || ($cfg_ocs['linksnmp_printermemory'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_printermemory'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_printermemory'] && $linked))
          && count($ocsSnmp['MEMORIES']) > 0
          && $ocsSnmp['MEMORIES'][0]['CAPACITY'] > 0) {

         $dev['designation'] = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep(__("Printer Memory", 'ocsinventoryng')));

         $item   = new $itemtype();
         $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
         if ($item->getFromDB($id_printer)) {
            $entity = $item->fields['entities_id'];
         }

         $dev['entities_id'] = $entity;

         $device    = new DeviceMemory();
         $device_id = $device->import($dev);
         if ($device_id) {
            $CompDevice = new Item_DeviceMemory();

            if ($cfg_ocs['history_devices']) {
               $dbu = new DbUtils();
               $table = $dbu->getTableForItemType("Item_DeviceMemory");
               $query = "DELETE
                            FROM `$table`
                            WHERE `items_id` = $id_printer 
                            AND `itemtype` = '" . $itemtype . "'";
               $DB->query($query);
            }
            //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
            //            $CompDevice->deleteByCriteria(array('items_id' => $id_printer,
            //               'itemtype' => $itemtype), 1);
            $CompDevice->add(['items_id'          => $id_printer,
                              'itemtype'          => $itemtype,
                              'size'              => $ocsSnmp['MEMORIES'][0]['CAPACITY'],
                              'entities_id'       => $entity,
                              'devicememories_id' => $device_id,
                              'is_dynamic'        => 1], [], $cfg_ocs['history_devices']);
         }
      }

      if ($id_printer > 0
          && (($cfg_ocs['importsnmp_createport'] && $action == "add")
              || ($cfg_ocs['linksnmp_createport'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))
      ) {

         //Delete Existing network config
         $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `items_id` = $id_printer
                AND `itemtype` = '" . $itemtype . "'";

         foreach ($DB->request($query) as $networkPortID) {

            $queryPort = "SELECT `id`
             FROM `glpi_networknames`
             WHERE `items_id` = " . $networkPortID['id'] . "
               AND `itemtype` = 'NetworkPort'";

            foreach ($DB->request($queryPort) as $networkNameID) {

               $ipAddress = new IPAddress();
               $ipAddress->deleteByCriteria(['items_id' => $networkNameID['id'],
                                             'itemtype' => 'NetworkName'], 1);
            }

            $nn = new NetworkName();
            $nn->deleteByCriteria(['items_id' => $networkPortID['id'],
                                   'itemtype' => 'NetworkPort'], 1);
         }
         $np = new NetworkPort();
         $np->deleteByCriteria(['items_id' => $id_printer,
                                'itemtype' => $itemtype], 1);

         //Add network port
         $ip  = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];

         $np = new NetworkPort();
         $data = $np->find(['mac' => $mac, 'items_id' => $id_printer, 'itemtype' => $itemtype]);

         if (count($data) < 1) {

            $item   = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
            if ($item->getFromDB($id_printer)) {
               $entity = $item->fields['entities_id'];
            }

            $port_input = ['name'                     => $ocsSnmp['PRINTER'][0]['NAME'],
                           'mac'                      => $mac,
                           'items_id'                 => $id_printer,
                           'itemtype'                 => $itemtype,
                           'instantiation_type'       => "NetworkPortEthernet",
                           "entities_id"              => $entity,
                           "NetworkName__ipaddresses" => ["-100" => $ip],
                           '_create_children'         => 1,
                           //'is_dynamic'                => 1,
                           'is_deleted'               => 0];

            $np->add($port_input, [], $cfg_ocs['history_network']);
         }
      }

      return $id_printer;
   }

   /**
    * @param      $itemtype
    * @param int  $ID
    * @param      $ocsSnmp
    * @param      $loc_id
    * @param      $dom_id
    * @param      $action
    * @param bool $linked
    *
    * @param      $cfg_ocs
    * @return int
    * @throws \GlpitestSQLError
    */
   static function addOrUpdateNetworkEquipment($itemtype, $ID, $ocsSnmp, $loc_id, $dom_id, $action, $linked, $cfg_ocs) {
      global $DB;

      $snmpDevice = new $itemtype();
      $dbu = new DbUtils();
      $input = [
         "is_dynamic"   => 1,
         "entities_id"  => (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0),
         "is_recursive" => 0,
      ];

      if (($cfg_ocs['importsnmp_name'] && $action == "add")
          || ($cfg_ocs['linksnmp_name'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
         if ($ocsSnmp['META']['NAME'] != "N/A") {
            $input["name"] = $ocsSnmp['META']['NAME'];
         } else {
            $input["name"] = $ocsSnmp['META']['DESCRIPTION'];
         }
      }
      if (($cfg_ocs['importsnmp_contact'] && $action == "add")
          || ($cfg_ocs['linksnmp_contact'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_contact'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if (($cfg_ocs['importsnmp_comment'] && $action == "add")
          || ($cfg_ocs['linksnmp_comment'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_comment'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_comment'] && $linked)) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }

      if ($loc_id > 0) {
         $input["locations_id"] = $loc_id;
      }
      if ($dom_id > 0) {
         $input["domains_id"] = $dom_id;
      }

      //if($ocsSnmp['META']['TYPE'] == null){
      //   $type_id = self::checkIfExist("NetworkEquipmentType", "Network Device");
      //} else {
      //   $type_id = self::checkIfExist("network", $ocsSnmp['META']['TYPE']);
      //}

      if (!empty($ocsSnmp['SWITCH'])) {

         if (($cfg_ocs['importsnmp_manufacturer'] && $action == "add")
             || ($cfg_ocs['linksnmp_manufacturer'] && $linked)
             || ($action == "update" && $cfg_ocs['importsnmp_manufacturer'] && !$linked)
             || ($action == "update" && $cfg_ocs['linksnmp_manufacturer'] && $linked)) {
            $man_id                    = Dropdown::importExternal('Manufacturer',
                                                                  PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['SWITCH'][0]['MANUFACTURER']));
            $input['manufacturers_id'] = $man_id;
         }

         if (($cfg_ocs['importsnmp_firmware'] && $action == "add")
             || ($cfg_ocs['linksnmp_firmware'] && $linked)
             || ($action == "update" && $cfg_ocs['importsnmp_firmware'] && !$linked)
             || ($action == "update" && $cfg_ocs['linksnmp_firmware'] && $linked)) {
            $firm_id                               = Dropdown::importExternal('NetworkEquipmentFirmware',
                                                                              PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['SWITCH'][0]['FIRMVERSION']));
            $input['networkequipmentfirmwares_id'] = $firm_id;
         }

         if (($cfg_ocs['importsnmp_serial'] && $action == "add")
             || ($cfg_ocs['linksnmp_serial'] && $linked)
             || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
             || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)) {
            $input['serial'] = $ocsSnmp['SWITCH'][0]['SERIALNUMBER'];
         }
         //TODOSNMP = chassis ??
         //$mod_id = Dropdown::importExternal('NetworkEquipmentModel',
         //PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
         //$ocsSnmp['SWITCH'][0]['REFERENCE']));
         //$input['networkequipmentmodels_id'] = $mod_id;
         // TODOSNMP ?
         //$input['networkequipmenttypes_id'] = self::checkIfExist("NetworkEquipmentType", "Switch");
      }
      if (!empty($ocsSnmp['FIREWALLS'])) {

         if (($cfg_ocs['importsnmp_serial'] && $action == "add")
             || ($cfg_ocs['linksnmp_serial'] && $action == $linked)
             || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
             || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)) {
            $input['serial'] = $ocsSnmp['FIREWALLS']['SERIALNUMBER'];
         }
         // TODOSNMP ?
         //$input['networkequipmenttypes_id'] = self::checkIfExist("NetworkEquipmentType", "Firewall");
      }
      $id_network = 0;
      if ($action == "add") {
         $id_network = $snmpDevice->add($input, ['unicity_error_message' => true], $cfg_ocs['history_hardware']);
      } else {
         $input["id"] = $ID;
         $id_network  = $ID;
         if ($snmpDevice->getFromDB($id_network)) {
            $input["entities_id"] = $snmpDevice->fields['entities_id'];
         }
         $snmpDevice->update($input, $cfg_ocs['history_hardware'], ['unicity_error_message' => false,
                                                                    '_no_history'           => !$cfg_ocs['history_hardware']]);
      }

      if ($id_network > 0
         //&& $action == "add"
      ) {

         if (isset($ocsSnmp['POWERSUPPLIES'])
             && (($cfg_ocs['importsnmp_power'] && $action == "add")
                 || ($cfg_ocs['linksnmp_power'] && $linked)
                 || ($action == "update" && $cfg_ocs['importsnmp_power'] && !$linked)
                 || ($action == "update" && $cfg_ocs['linksnmp_power'] && $linked))
             && count($ocsSnmp['POWERSUPPLIES']) > 0) {

            $man_id = Dropdown::importExternal('Manufacturer',
                                               PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['POWERSUPPLIES'][0]['MANUFACTURER']));

            $pow['manufacturers_id'] = $man_id;
            $pow['designation']      = $ocsSnmp['POWERSUPPLIES'][0]['REFERENCE'];
            $pow['comment']          = $ocsSnmp['POWERSUPPLIES'][0]['DESCRIPTION'];

            $item   = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
            if ($item->getFromDB($id_network)) {
               $entity = $item->fields['entities_id'];
            }

            $pow['entities_id'] = $entity;

            $power    = new DevicePowerSupply();
            $power_id = $power->import($pow);
            if ($power_id) {
               $serial     = $ocsSnmp['POWERSUPPLIES'][0]['SERIALNUMBER'];
               $CompDevice = new Item_DevicePowerSupply();

               if ($cfg_ocs['history_devices']) {
                  $table = $dbu->getTableForItemType("Item_DevicePowerSupply");
                  $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = $id_network
                            AND `itemtype` = '" . $itemtype . "'";
                  $DB->query($query);
               }
               //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
               //               $CompDevice->deleteByCriteria(array('items_id' => $id_network,
               //                  'itemtype' => $itemtype), 1);
               $CompDevice->add(['items_id'               => $id_network,
                                 'itemtype'               => $itemtype,
                                 'entities_id'            => $entity,
                                 'serial'                 => $serial,
                                 'devicepowersupplies_id' => $power_id,
                                 'is_dynamic'             => 1], [], $cfg_ocs['history_devices']);
            }
         }

         if (isset($ocsSnmp['FANS'])
             && (($cfg_ocs['importsnmp_fan'] && $action == "add")
                 || ($cfg_ocs['linksnmp_fan'] && $linked)
                 || ($action == "update" && $cfg_ocs['importsnmp_fan'] && !$linked)
                 || ($action == "update" && $cfg_ocs['linksnmp_fan'] && $linked))
             && count($ocsSnmp['FANS']) > 0) {

            $man_id                  = Dropdown::importExternal('Manufacturer', PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['FANS'][0]['MANUFACTURER']));
            $dev['manufacturers_id'] = $man_id;

            $dev['designation'] = $ocsSnmp['FANS'][0]['REFERENCE'];
            $dev['comment']     = $ocsSnmp['FANS'][0]['DESCRIPTION'];

            $item   = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
            if ($item->getFromDB($id_network)) {
               $entity = $item->fields['entities_id'];
            }

            $dev['entities_id'] = $entity;

            $device    = new DevicePci();
            $device_id = $device->import($dev);
            if ($device_id) {
               $CompDevice = new Item_DevicePci();
               if ($cfg_ocs['history_devices']) {
                  $dbu = new DbUtils();
                  $table = $dbu->getTableForItemType("Item_DevicePci");
                  $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = $id_network
                            AND `itemtype` = '" . $itemtype . "'";
                  $DB->query($query);
               }
               //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
               //               $CompDevice->deleteByCriteria(array('items_id' => $id_network,
               //                  'itemtype' => $itemtype), 1);
               $CompDevice->add(['items_id'      => $id_network,
                                 'itemtype'      => $itemtype,
                                 'entities_id'   => $entity,
                                 'devicepcis_id' => $device_id,
                                 'is_dynamic'    => 1], [], $cfg_ocs['history_devices']);
            }
         }
      }
      if ($id_network > 0
          && (($cfg_ocs['importsnmp_createport'] && $action == "add")
              || ($cfg_ocs['linksnmp_createport'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))) {
         //Add local port
         $ip  = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];

         //if ($cfg_ocs['history_devices']) {

         //$table = getTableForItemType("NetworkPort");
         //$query = "DELETE
         //                FROM `glpi_networkports`
         //                WHERE `items_id` = '" . $id_network . "'
         //                AND `itemtype` = '" . $itemtype . "'
         //                 AND `instantiation_type` = 'NetworkPortLocal'";
         //$DB->query($query);
         //}

         $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `items_id` = $id_network
                AND `itemtype` = '" . $itemtype . "'";

         foreach ($DB->request($query) as $networkPortID) {

            $queryPort = "SELECT `id`
             FROM `glpi_networknames`
             WHERE `items_id` = " . $networkPortID['id'] . "
               AND `itemtype` = 'NetworkPort'";

            foreach ($DB->request($queryPort) as $networkNameID) {

               $ipAddress = new IPAddress();
               $ipAddress->deleteByCriteria(['items_id' => $networkNameID['id'],
                                             'itemtype' => 'NetworkName'], 1);
            }

            $nn = new NetworkName();
            $nn->deleteByCriteria(['items_id' => $networkPortID['id'],
                                   'itemtype' => 'NetworkPort'], 1);
         }
         $np = new NetworkPort();
         $np->deleteByCriteria(['items_id' => $id_network,
                                'itemtype' => $itemtype], 1);

         $np = new NetworkPort();
         $data = $np->find(['mac' => $mac, 'items_id' => $id_network, 'itemtype' => $itemtype]);
         if (count($data) < 1) {

            $item   = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
            if ($item->getFromDB($id_network)) {
               $entity = $item->fields['entities_id'];
            }

            $port_input = ['name'                     => $ocsSnmp['META']['NAME'],
                           'mac'                      => $mac,
                           'items_id'                 => $id_network,
                           'itemtype'                 => $itemtype,
                           'instantiation_type'       => "NetworkPortLocal",
                           "entities_id"              => $entity,
                           "NetworkName__ipaddresses" => ["-100" => $ip],
                           '_create_children'         => 1,
                           'is_dynamic'               => 1,
                           'is_deleted'               => 0];

            $np->add($port_input, [], $cfg_ocs['history_network']);
         }

         //All PORTS
         if ($id_network > 0
             && isset($ocsSnmp['NETWORKS'])
             && count($ocsSnmp['NETWORKS']) > 0) {

            foreach ($ocsSnmp['NETWORKS'] as $k => $net) {
               //$dev["designation"] = $net['SLOT'];
               //$dev["comment"] = $net['TYPE'];
               $mac = $net['MACADDR'];

               $net['DEVICENAME'] = str_replace("SEP", "", $net['DEVICENAME']);
               $mac_dest          = self::addMacSeparator($net['DEVICENAME']);

               $np = new NetworkPort();
               $data = $np->find(['mac' => $mac, 'items_id' => $id_network, 'itemtype' => $itemtype, 'instantiation_type' => 'NetworkPortEthernet']);
               if (count($data) < 1) {

                  $item   = new $itemtype();
                  $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
                  if ($item->getFromDB($id_network)) {
                     $entity = $item->fields['entities_id'];
                  }

                  $port_input = ['name'               => $net['SLOT'],
                                 'mac'                => $mac,
                                 'items_id'           => $id_network,
                                 'itemtype'           => $itemtype,
                                 'instantiation_type' => "NetworkPortEthernet",
                                 "entities_id"        => $entity,
                                 //"NetworkName__ipaddresses" => array("-100" => $ip),
                                 '_create_children'   => 1,
                                 'is_dynamic'         => 1,
                                 'is_deleted'         => 0];

                  $np_src = $np->add($port_input, [], $cfg_ocs['history_network']);
               }
               if ($np_src) {
                  $link    = new NetworkPort_NetworkPort();
                  $np_dest = new NetworkPort();
                  $datas = $np_dest->find(['mac' => $mac_dest]);
                  if (count($datas) > 0) {
                     foreach ($datas as $data) {
                        $link->getFromDBByCrit(['networkports_id_1' => $np_src,
                                                'networkports_id_2' => $data['id']]);
                        if (count($link->fields) < 1) {
                           $link_input = ['networkports_id_1' => $np_src,
                                          'networkports_id_2' => $data['id']];
                           $link->add($link_input, [], $cfg_ocs['history_network']);
                        }
                     }
                  }
               }
            }
         }
      }

      return $id_network;
   }


   /**
    * @param        $mac
    * @param string $separator
    *
    * @return string
    */
   public static function addMacSeparator($mac, $separator = ':') {
      return join($separator, str_split($mac, 2));
   }

   /**
    * @param      $plugin_ocsinventoryng_ocsservers_id
    * @param      $itemtype
    * @param int  $ID
    * @param      $ocsSnmp
    * @param      $loc_id
    * @param      $dom_id
    * @param      $action
    * @param bool $linked
    *
    * @param      $cfg_ocs
    * @return int
    * @throws \GlpitestSQLError
    */
   static function addOrUpdateComputer($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID, $ocsSnmp, $loc_id, $dom_id, $action, $linked, $cfg_ocs) {
      global $DB;

      $snmpDevice = new $itemtype();
      $dbu = new DbUtils();
      $input = [
         "is_dynamic"  => 1,
         "entities_id" => (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0)
      ];

      if (($cfg_ocs['importsnmp_name'] && $action == "add")
          || ($cfg_ocs['linksnmp_name'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
         $input["name"] = $ocsSnmp['META']['NAME'];
      }
      if (($cfg_ocs['importsnmp_contact'] && $action == "add")
          || ($cfg_ocs['linksnmp_contact'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if (($cfg_ocs['importsnmp_comment'] && $action == "add")
          || ($cfg_ocs['linksnmp_comment'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }

      if ($loc_id > 0) {
         $input["locations_id"] = $loc_id;
      }
      if ($dom_id > 0 && $itemtype != "Phone") {
         $input["domains_id"] = $dom_id;
      }

      $id_item = 0;

      if ($action == "add") {
         $id_item = $snmpDevice->add($input, ['unicity_error_message' => true], $cfg_ocs['history_hardware']);
      } else {
         $input["id"] = $ID;
         $id_item     = $ID;
         if ($snmpDevice->getFromDB($id_item)) {
            $input["entities_id"] = $snmpDevice->fields['entities_id'];
         }
         $snmpDevice->update($input, $cfg_ocs['history_hardware'],
                             ['unicity_error_message' => false,
                              '_no_history'           => !$cfg_ocs['history_hardware']]);
      }

      if ($id_item > 0
          && isset($ocsSnmp['MEMORIES'])
          && (($cfg_ocs['importsnmp_computermemory'] && $action == "add")
              || ($cfg_ocs['linksnmp_computermemory'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computermemory'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computermemory'] && $linked))
          && count($ocsSnmp['MEMORIES']) > 0
          && $ocsSnmp['MEMORIES'][0]['CAPACITY'] > 0) {

         $dev['designation'] = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep(__("Computer Memory", 'ocsinventoryng')));

         $item   = new $itemtype();
         $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
         if ($item->getFromDB($id_item)) {
            $entity = $item->fields['entities_id'];
         }

         $dev['entities_id'] = $entity;

         $device    = new DeviceMemory();
         $device_id = $device->import($dev);
         if ($device_id) {
            $CompDevice = new Item_DeviceMemory();
            if ($cfg_ocs['history_devices']) {
               $table = $dbu->getTableForItemType("Item_DeviceMemory");
               $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = '" . $id_item . "'
                            AND `itemtype` = '" . $itemtype . "'";
               $DB->query($query);
            }
            //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
            //            $CompDevice->deleteByCriteria(array('items_id' => $id_item,
            //               'itemtype' => $itemtype), 1);
            $CompDevice->add(['items_id'          => $id_item,
                              'itemtype'          => $itemtype,
                              'size'              => $ocsSnmp['MEMORIES'][0]['CAPACITY'],
                              'entities_id'       => $entity,
                              'devicememories_id' => $device_id,
                              'is_dynamic'        => 1], [], $cfg_ocs['history_devices']);
         }
      }

      if ($id_item > 0
          && isset($ocsSnmp['NETWORKS'])
          && (($cfg_ocs['importsnmp_computernetworkcards'] && $action == "add")
              || ($cfg_ocs['linksnmp_computernetworkcards'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computernetworkcards'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computernetworkcards'] && $linked))
          && count($ocsSnmp['NETWORKS']) > 0) {
         $CompDevice = new Item_DeviceNetworkCard();
         if ($cfg_ocs['history_devices']) {
            $table = $dbu->getTableForItemType("Item_DeviceNetworkCard");
            $query = "DELETE
                      FROM `" . $table . "`
                      WHERE `items_id` = '" . $id_item . "'
                      AND `itemtype` = '" . $itemtype . "'";
            $DB->query($query);
         }
         //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
         //         $CompDevice->deleteByCriteria(array('items_id' => $id_item,
         //                                             'itemtype' => $itemtype), 1);

         foreach ($ocsSnmp['NETWORKS'] as $k => $net) {
            $dev["designation"] = $net['SLOT'];
            $dev["comment"]     = $net['TYPE'];
            $mac                = $net['MACADDR'];
            /*$speed = 0;
            if (strstr($processor['SPEED'], "GHz")) {
               $speed = str_replace("GHz", "", $processor['SPEED']);
               $speed = $speed * 1000;
            }
            if (strstr($processor['SPEED'], "MHz")) {
               $speed = str_replace("MHz", "", $processor['SPEED']);
            }*/

            $item   = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
            if ($item->getFromDB($id_item)) {
               $entity = $item->fields['entities_id'];
            }

            $dev['entities_id'] = $entity;

            $device    = new DeviceNetworkCard();
            $device_id = $device->import($dev);

            if ($device_id) {

               $CompDevice->add(['items_id'              => $id_item,
                                 'itemtype'              => $itemtype,
                                 'mac'                   => $mac,
                                 'entities_id'           => $entity,
                                 'devicenetworkcards_id' => $device_id,
                                 'is_dynamic'            => 1], [], $cfg_ocs['history_devices']);
            }
         }
      }

      if ($id_item > 0
          && isset($ocsSnmp['SOFTWARES'])
          && (($cfg_ocs['importsnmp_computersoftwares'] && $action == "add")
              || ($cfg_ocs['linksnmp_computersoftwares'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computersoftwares'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computersoftwares'] && $linked))
          && count($ocsSnmp['SOFTWARES']) > 0) {

         $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
         if ($item->getFromDB($id_item)) {
            $entity = $item->fields['entities_id'];
         }
         PluginOcsinventoryngSoftware::updateSoftware($cfg_ocs, $id_item, $ocsSnmp, $entity, false, false);

      }
      if ($id_item > 0
          && isset($ocsSnmp['CPU'])
          && (($cfg_ocs['importsnmp_computerprocessors'] && $action == "add")
              || ($cfg_ocs['linksnmp_computerprocessors'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computerprocessors'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computerprocessors'] && $linked))
          && count($ocsSnmp['CPU']) > 0) {
         $CompDevice = new Item_DeviceProcessor();
         if ($cfg_ocs['history_devices']) {
            $table = $dbu->getTableForItemType("Item_DeviceProcessor");
            $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = '" . $id_item . "'
                            AND `itemtype` = '" . $itemtype . "'";
            $DB->query($query);
         }
         //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
         //         $CompDevice->deleteByCriteria(array('items_id' => $id_item,
         //                                             'itemtype' => $itemtype), 1);

         foreach ($ocsSnmp['CPU'] as $k => $processor) {
            $dev["designation"]      = $processor['TYPE'];
            $dev["manufacturers_id"] = Dropdown::importExternal('Manufacturer',
                                                                PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
                                                                                                                    $processor['MANUFACTURER']));
            $speed                   = 0;
            if (strstr($processor['SPEED'], "GHz")) {
               $speed = str_replace("GHz", "", $processor['SPEED']);
               $speed = $speed * 1000;
            }
            if (strstr($processor['SPEED'], "MHz")) {
               $speed = str_replace("MHz", "", $processor['SPEED']);
            }

            $item   = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
            if ($item->getFromDB($id_item)) {
               $entity = $item->fields['entities_id'];
            }

            $dev['entities_id'] = $entity;

            $device    = new DeviceProcessor();
            $device_id = $device->import($dev);

            if ($device_id) {

               $CompDevice->add(['items_id'            => $id_item,
                                 'itemtype'            => $itemtype,
                                 'frequency'           => $speed,
                                 'entities_id'         => $entity,
                                 'deviceprocessors_id' => $device_id,
                                 'is_dynamic'          => 1], [], $cfg_ocs['history_devices']);
            }
         }
      }

      if ($id_item > 0
          && isset($ocsSnmp['VIRTUALMACHINES'])
          && (($cfg_ocs['importsnmp_computervm'] && $action == "add")
              || ($cfg_ocs['linksnmp_computervm'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computervm'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computervm'] && $linked))
          && count($ocsSnmp['VIRTUALMACHINES']) > 0) {
         $already_processed = [];

         $virtualmachine = new ComputerVirtualMachine();
         foreach ($ocsSnmp['VIRTUALMACHINES'] as $k => $ocsVirtualmachine) {

            $ocsVirtualmachine  = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsVirtualmachine));
            $vm                 = [];
            $vm['name']         = $ocsVirtualmachine['NAME'];
            $vm['vcpu']         = $ocsVirtualmachine['CPU'];
            $vm['ram']          = $ocsVirtualmachine['MEMORY'];
            $vm['uuid']         = $ocsVirtualmachine['UUID'];
            $vm['computers_id'] = $id_item;
            $vm['is_dynamic']   = 1;

            $vm['virtualmachinestates_id'] = Dropdown::importExternal('VirtualMachineState', $ocsVirtualmachine['POWER']);
            //$vm['virtualmachinetypes_id'] = Dropdown::importExternal('VirtualMachineType', $ocsVirtualmachine['VMTYPE']);
            //$vm['virtualmachinesystems_id'] = Dropdown::importExternal('VirtualMachineType', $ocsVirtualmachine['SUBSYSTEM']);

            $query = "SELECT `id`
                         FROM `glpi_computervirtualmachines`
                         WHERE `computers_id`= $id_item
                            AND `is_dynamic` = 1";
            if ($ocsVirtualmachine['UUID']) {
               $query .= " AND `uuid`='" . $ocsVirtualmachine['UUID'] . "'";
            } else {
               // Failback on name
               $query .= " AND `name`='" . $ocsVirtualmachine['NAME'] . "'";
            }

            $results = $DB->query($query);
            if ($DB->numrows($results) > 0) {
               $id = $DB->result($results, 0, 'id');
            } else {
               $id = 0;
            }
            if (!$id) {
               $virtualmachine->reset();
               $id_vm = $virtualmachine->add($vm, [], $cfg_ocs['history_vm']);
               if ($id_vm) {
                  $already_processed[] = $id_vm;
               }
            } else {
               if ($virtualmachine->getFromDB($id)) {
                  $vm['id'] = $id;
                  $virtualmachine->update($vm, $cfg_ocs['history_vm']);
               }
               $already_processed[] = $id;
            }
            // Delete Unexisting Items not found in OCS
            //Look for all ununsed virtual machines
            $query = "SELECT `id`
                      FROM `glpi_computervirtualmachines`
                      WHERE `computers_id`= $id_item
                         AND `is_dynamic` = 1 ";
            if (!empty($already_processed)) {
               $query .= "AND `id` NOT IN (" . implode(',', $already_processed) . ")";
            }
            foreach ($DB->request($query) as $data) {
               //Delete all connexions
               $virtualmachine->delete(['id'             => $data['id'],
                                        '_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                        '_no_history'    => !$cfg_ocs['history_vm']], true, $cfg_ocs['history_vm']);
            }
         }
      }

      if ($id_item > 0
          && (($cfg_ocs['importsnmp_createport'] && $action == "add")
              || ($cfg_ocs['linksnmp_createport'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))) {
         //Delete Existing network config
         $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `items_id` = $id_item
                AND `itemtype` = '" . $itemtype . "'";

         foreach ($DB->request($query) as $networkPortID) {

            $queryPort = "SELECT `id`
             FROM `glpi_networknames`
             WHERE `items_id` = '" . $networkPortID['id'] . "'
               AND `itemtype` = 'NetworkPort'";

            foreach ($DB->request($queryPort) as $networkNameID) {

               $ipAddress = new IPAddress();
               $ipAddress->deleteByCriteria(['items_id' => $networkNameID['id'],
                                             'itemtype' => 'NetworkName'], 1);
            }

            $nn = new NetworkName();
            $nn->deleteByCriteria(['items_id' => $networkPortID['id'],
                                   'itemtype' => 'NetworkPort'], 1);
         }
         $np = new NetworkPort();
         $np->deleteByCriteria(['items_id' => $id_item,
                                'itemtype' => $itemtype], 1);

         //Add network port
         $ip  = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];

         $np = new NetworkPort();
         $data = $np->find(['mac' => $mac, 'items_id' => $id_item, 'itemtype' => $itemtype]);
         if (count($data) < 1) {

            $item   = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
            if ($item->getFromDB($id_item)) {
               $entity = $item->fields['entities_id'];
            }
            $port_input = ['name'                     => $ocsSnmp['META']['NAME'],
                           'mac'                      => $mac,
                           'items_id'                 => $id_item,
                           'itemtype'                 => $itemtype,
                           'instantiation_type'       => "NetworkPortEthernet",
                           "entities_id"              => $entity,
                           "NetworkName__ipaddresses" => ["-100" => $ip],
                           '_create_children'         => 1,
                           //'is_dynamic'         => 1,
                           'is_deleted'               => 0];

            $np->add($port_input, [], $cfg_ocs['history_network']);
         }
      }

      $computerDisk = new Item_Disk();
      if ($id_item > 0
          && isset($ocsSnmp['COMPUTERDISKS'])
          && (($cfg_ocs['importsnmp_computerdisks'] && $action == "add")
              || ($cfg_ocs['linksnmp_computerdisks'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computerdisks'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computerdisks'] && $linked))
          && count($ocsSnmp['COMPUTERDISKS']) > 0) {
         $already_processed = [];

         foreach ($ocsSnmp['COMPUTERDISKS'] as $k => $ocsComputerDisks) {

            $ocsComputerDisks       = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsComputerDisks));
            $disk                   = [];
            $disk['computers_id']   = $id_item;
            $disk['name']           = $ocsComputerDisks['FILESYSTEM'];
            $disk['mountpoint']     = $ocsComputerDisks['VOLUMN'];
            $disk['device']         = $ocsComputerDisks['TYPE'];
            $disk['totalsize']      = $ocsComputerDisks['TOTAL'];
            $disk['freesize']       = $ocsComputerDisks['FREE'];
            $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $ocsComputerDisks["FILESYSTEM"]);
            $disk['is_dynamic']     = 1;

            // Ok import disk
            if (isset($disk['name']) && !empty($disk["name"])) {
               $disk['totalsize'] = $ocsComputerDisks['TOTAL'];
               $disk['freesize']  = $ocsComputerDisks['FREE'];

               $query   = "SELECT `id`
                            FROM `glpi_computerdisks`
                            WHERE `computers_id`= $id_item
                               AND `name`='" . $disk['name'] . "'
                               AND `is_dynamic` = 1";
               $results = $DB->query($query);
               if ($DB->numrows($results) == 1) {
                  $id = $DB->result($results, 0, 'id');
               } else {
                  $id = false;
               }

               if (!$id) {
                  $computerDisk->reset();
                  $disk['is_dynamic']  = 1;
                  $id_disk             = $computerDisk->add($disk, [], $cfg_ocs['history_drives']);
                  $already_processed[] = $id_disk;
               } else {
                  // Only update if needed
                  if ($computerDisk->getFromDB($id)) {

                     // Update on type, total size change or variation of 5%
                     if ($computerDisk->fields['totalsize'] != $disk['totalsize']
                         || ($computerDisk->fields['filesystems_id'] != $disk['filesystems_id'])
                         || ((abs($disk['freesize'] - $computerDisk->fields['freesize']) / $disk['totalsize']) > 0.05)) {

                        $toupdate['id']             = $id;
                        $toupdate['totalsize']      = $disk['totalsize'];
                        $toupdate['freesize']       = $disk['freesize'];
                        $toupdate['filesystems_id'] = $disk['filesystems_id'];
                        $computerDisk->update($toupdate, $cfg_ocs['history_drives']);
                     }
                     $already_processed[] = $id;
                  }
               }
            }

         }
      }

      // Delete Unexisting Items not found in OCS
      //Look for all ununsed disks
      $query = "SELECT `id`
                FROM `glpi_items_disks`
                WHERE `items_id`= $id_item
                   AND `itemtype` = 'Computer'
                   AND `is_dynamic` = 1 ";
      if (!empty($already_processed)) {
         $query .= "AND `id` NOT IN (" . implode(',', $already_processed) . ")";
      }
      foreach ($DB->request($query) as $data) {
         //Delete all connexions
         $computerDisk->delete(['id'             => $data['id'],
                                '_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                '_no_history'    => !$cfg_ocs['history_drives']],
                               true, $cfg_ocs['history_drives']);
      }

      return $id_item;
   }

   /**
    * @param      $itemtype
    * @param int  $ID
    * @param      $ocsSnmp
    * @param      $loc_id
    * @param      $dom_id
    * @param      $action
    * @param bool $linked
    *
    * @param      $cfg_ocs
    * @return int
    */
   static function addOrUpdateOther($itemtype, $ID, $ocsSnmp, $loc_id, $dom_id, $action, $linked, $cfg_ocs) {
      global $DB;

      $snmpDevice = new $itemtype();

      $input = [
         "is_dynamic"  => 1,
         "entities_id" => (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0)
      ];

      if (($cfg_ocs['importsnmp_name'] && $action == "add")
          || ($cfg_ocs['linksnmp_name'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
         $input["name"] = $ocsSnmp['META']['NAME'];
      }
      if (($cfg_ocs['importsnmp_contact'] && $action == "add")
          || ($cfg_ocs['linksnmp_contact'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if (($cfg_ocs['importsnmp_comment'] && $action == "add")
          || ($cfg_ocs['linksnmp_comment'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }

      if ($loc_id > 0) {
         $input["locations_id"] = $loc_id;
      }
      if ($dom_id > 0 && $itemtype != "Phone") {
         $input["domains_id"] = $dom_id;
      }

      $id_item = 0;

      if ($action == "add") {
         $id_item = $snmpDevice->add($input, ['unicity_error_message' => true], $cfg_ocs['history_hardware']);
      } else {
         $input["id"] = $ID;
         $id_item     = $ID;
         if ($snmpDevice->getFromDB($id_item)) {
            $input["entities_id"] = $snmpDevice->fields['entities_id'];
         }

         $snmpDevice->update($input, $cfg_ocs['history_hardware'], ['unicity_error_message' => false,
                                                                    '_no_history'           => !$cfg_ocs['history_hardware']]);
      }

      if ($id_item > 0
          && (($cfg_ocs['importsnmp_createport'] && $action == "add")
              || ($cfg_ocs['linksnmp_createport'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))) {

         //Delete Existing network config
         $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `items_id` = '" . $id_item . "'
                AND `itemtype` = '" . $itemtype . "'";

         foreach ($DB->request($query) as $networkPortID) {

            $queryPort = "SELECT `id`
             FROM `glpi_networknames`
             WHERE `items_id` = '" . $networkPortID['id'] . "'
               AND `itemtype` = 'NetworkPort'";

            foreach ($DB->request($queryPort) as $networkNameID) {

               $ipAddress = new IPAddress();
               $ipAddress->deleteByCriteria(['items_id' => $networkNameID['id'],
                                             'itemtype' => 'NetworkName'], 1);
            }

            $nn = new NetworkName();
            $nn->deleteByCriteria(['items_id' => $networkPortID['id'],
                                   'itemtype' => 'NetworkPort'], 1);
         }
         $np = new NetworkPort();
         $np->deleteByCriteria(['items_id' => $id_item,
                                'itemtype' => $itemtype], 1);

         //Add network port
         $ip  = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];

         $np = new NetworkPort();
         $data = $np->find(['mac' => $mac, 'items_id' => $id_item, 'itemtype' => $itemtype]);
         if (count($data) < 1) {

            $item   = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0);
            if ($item->getFromDB($id_item)) {
               $entity = $item->fields['entities_id'];
            }
            $port_input = ['name'                     => $ocsSnmp['META']['NAME'],
                           'mac'                      => $mac,
                           'items_id'                 => $id_item,
                           'itemtype'                 => $itemtype,
                           'instantiation_type'       => "NetworkPortEthernet",
                           "entities_id"              => $entity,
                           "NetworkName__ipaddresses" => ["-100" => $ip],
                           '_create_children'         => 1,
                           //'is_dynamic'         => 1,
                           'is_deleted'               => 0];

            $np->add($port_input, [], $cfg_ocs['history_network']);
         }
      }

      return $id_item;
   }

   /**
    * @param $ID
    * @param $plugin_ocsinventoryng_ocsservers_id
    *
    * @return array
    */
   static function updateSnmp($ocsids, $plugin_ocsinventoryng_ocsservers_id) {
      global $DB;

      $split = explode("_", $ocsids);
      $ocsTable = $split[0];
      $ocsId = $split[1];

      $ocs_srv = $plugin_ocsinventoryng_ocsservers_id;

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs   = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      
      $queryReconciliation = "SELECT *, (SELECT GROUP_CONCAT(glpi_col) FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` b WHERE a.ocs_snmp_type_id = b.ocs_snmp_type_id AND is_reconsiliation = 1 ) AS reconciliation FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` a WHERE ocs_snmp_type_id = $ocsTable AND ocs_srv = $ocs_srv";
      $reconciliationData = $DB->query($queryReconciliation);
      if ($DB->numrows($reconciliationData)) {
         while ($lines = $DB->fetchAssoc($reconciliationData)) {
            if (!is_null($lines['reconciliation'])) {
               $rec = explode(',', $lines['reconciliation']);
            }
            
            $reconciliationArray[$lines['ocs_snmp_type_id']]['link'][$lines['glpi_col']] = $lines['ocs_snmp_label_id'];
            $reconciliationArray[$lines['ocs_snmp_type_id']]['object'] = $lines['object'];
            $reconciliationArray[$lines['ocs_snmp_type_id']]['reconciliation'] = $rec ?? null;

         }
      }


      $data = $ocsClient->getSnmpValueByTableAndId($ocsTable, $ocsId);
      $glpiTable = $reconciliationArray[$ocsTable]['object'];

      //get database column's types of selected object
      $queryTypes = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$glpiTable'";
      $resultTypes = $DB->query($queryTypes);

      $dataTypesArray = [];
      while ($dataTypes = $DB->fetchAssoc($resultTypes)) {
         $dataTypesArray[$dataTypes['COLUMN_NAME']] = $dataTypes['DATA_TYPE'];
      }

      // Check if reconciliation exist on base
      $queryCheck = "SELECT * FROM $glpiTable";
      $where = "";
      if (isset($reconciliationArray[$ocsTable]['reconciliation'])) {
         $where .= " WHERE ";
         foreach ($reconciliationArray[$ocsTable]['reconciliation'] as $reconciliationKey => $reconciliationValue) {
            $where .= $reconciliationValue . " = '" . $data[$reconciliationArray[$ocsTable]['link'][$reconciliationValue]] . "' AND ";
         }
         $where = rtrim($where, ' AND ');
         $force = false;
      } else {
         $force = true;
      }

      if ($where) {
         $queryCheck .= $where;
      }
      $queryCheck .= ";";

      $checkResult = $DB->query($queryCheck);
      if (($checkResult && !$DB->numrows($checkResult) == 0) || $force) {
         $insert = [];
         foreach ($reconciliationArray[$ocsTable]['link'] as $reconciliationKey => $reconciliationValue) {
            //echo "<pre>" , var_dump(substr($reconciliationKey, -3)), "</pre>";
            if (substr($reconciliationKey, -3) == '_id') {
               $insert[$reconciliationKey] = self::getObjectOrInsert($reconciliationKey, $data[$reconciliationValue]);
            } else {
               if (isset($data[$reconciliationValue]) && isset($dataTypesArray[$reconciliationKey])) {
                  $value = self::convertStrToSqlValueBySqlType($data[$reconciliationValue], $dataTypesArray[$reconciliationKey]);
                  if (isset($value) && $value != "") {
                     $insert[$reconciliationKey] = $value;
                  }
               }
            }
         }

         $DB->updateOrDie($glpiTable, $insert, ['name' => $insert['name']]);
         return ['status' => PluginOcsinventoryngOcsProcess::SNMP_SYNCHRONIZED];
      } else {
         return ['status' => PluginOcsinventoryngOcsProcess::SNMP_NOTUPDATED];
      }
   }

   /**
    * Prints search form
    *
    * @param $params
    *
    * @return void
    * @internal param the $manufacturer supplier choice
    * @internal param the $type device type
    */
   static function searchForm($params) {
      global $CFG_GLPI;

      // Default values of parameters
      $p['itemtype'] = '';
      $p['ip']       = '';
      $p['tolinked'] = 0;
      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmprework.import.php';
      if ($p['tolinked'] > 0) {
         $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmprework.link.php';
      }

      echo "<form name='form' method='post' action='" . $target . "'>";
      echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='5'>";
      echo "<tr><th colspan='6'>" . __('Filter SNMP Objects list', 'ocsinventoryng') . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>";
      echo __('By itemtype', 'ocsinventoryng');
      echo "</td><td class='center'>";
      Dropdown::showItemTypes("itemtype", self::$snmptypes, ['value' => $p['itemtype']]);
      echo "</td>";

      echo "<td class='center'>";
      echo __('By IP', 'ocsinventoryng');
      echo "</td><td class='center'>";
      echo Html::input('ip', ['type'  => 'text',
                              'value' => $p['ip']]);
      echo "</td>";

      echo "<td>";
      echo Html::submit(_sx('button', 'Post'), ['name' => 'search']);
      echo "<a class='fas fa-undo reset-search' href='"
           . $target
           . (strpos($target, '?') ? '&amp;' : '?')
           . "reset=reset' title=\"".__s('Blank')."\"
                  ><span class='sr-only'>" . __s('Blank')  ."</span></a>";
      echo "</td>";
      echo "</tr>";

      echo "</table></div>";

      Html::closeForm();
   }

   static function addSnmp(){
      global $DB, $CFG_GLPI;
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);
      $snmpTypes = $ocsClient->getSnmpTypesIfReconciliation();
      $ocs_srv = $_SESSION['plugin_ocsinventoryng_ocsservers_id'];

      $query = "SELECT DISTINCT ocs_snmp_type_id FROM glpi_plugin_ocsinventoryng_snmplinkreworks WHERE ocs_srv = $ocs_srv";
      $result = $DB->query($query);
      $snmpTypesAlreadyLinkedypes = [];
      while ($data = $DB->fetchAssoc($result)) {
         $snmpTypesAlreadyLinkedypes[] = $data['ocs_snmp_type_id'];
      }

      foreach (array_keys($snmpTypes) as $key => $value) {
         if (in_array($value, $snmpTypesAlreadyLinkedypes)) {
            unset($snmpTypes[$value]);
         }
      }

      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/snmplinkrework.form.php';

      $glpi_types = [
        'glpi_computers' => 'Computer',
        'glpi_monitors' => 'Monitor',
        'glpi_networkequipments' => 'NetworkEquipment',
        'glpi_peripherals' => 'Peripheral',
        'glpi_phones' => 'Phone',
        'glpi_printers' => 'Printer',
        'glpi_softwarelicenses' => 'SoftwareLicense',
        'glpi_certificates' => 'Certificate',
      ];

      /*echo "<pre>" , var_dump($CFG_GLPI) , "</pre>";
      $glpi_types = [];
      foreach ($CFG_GLPI['asset_types'] as $key => $value) {
         echo "<pre>" , var_dump($CFG_GLPI['glpitablesitemtype'][$value]) , "</pre>";
         
         $glpi_types[$CFG_GLPI['glpitablesitemtype'][$value]] = $value;
      }

      echo "<pre>" , var_dump($glpi_types) , "</pre>";*/

      $options = [];

      if (!empty($snmpTypes)) {
         echo "<div align='center'>";
         echo "<form name='form' method='get' action='" . $target . "'>";
         echo "<table class='tab_cadrehov'>";
         echo "<tr>";
         echo "<td>" . __('Object', 'ocsinventoryng') . "</td>";
         echo "<td>";
         //echo Html::select("SelectGLPI", $glpi_types, $options);
         Dropdown::showFromArray('SelectGLPI', $glpi_types, $options);
         echo "</td>";
         echo "<td>" . __('OCS snmp Type', 'ocsinventoryng') . "</td>";
         echo "<td>";
         //echo Html::select("SelectOCS", $snmpTypes, $options);
         Dropdown::showFromArray('SelectOCS', $snmpTypes, $options);
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         echo "<table class='tab_cadrehov'>";
         echo "<tr>";
         echo "<td class='center'>";
         echo Html::submit(_sx('button', 'Post'), ['name' => 'search']);
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         echo "</form>";
         echo "</div>";
      } else {
         echo "<div align='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<td class='center b'>";
         echo __('No SNMP object to link', 'ocsinventoryng');
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         echo "</div>";
      }

   }

   static function addSnmpLinks($params = []){
      global $DB, $CFG_GLPI;
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);

      $snmpLabels['0'] = __("Choose a label", "ocsinventoryng");
      $snmpLabels = array_merge($snmpLabels, $ocsClient->getSnmpLabelByType($_GET['SelectOCS']));

      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/snmplinkrework.form.php';

      $query = "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`=Database() AND `TABLE_NAME`='" . $_GET['SelectGLPI'] . "'";
      $result_glpi = $DB->query($query);

      $snmpLinks = [];

      $exclud = ['id', 'entities_id', 'is_deleted', 'is_global', 'is_template', 'groups_id', 'users_id', 'is_dynamic'];

      if ($DB->numrows($result_glpi) > 0) {
         $i = 0;
         while ($data = $DB->fetchArray($result_glpi)) {
            if (!in_array($data['COLUMN_NAME'], $exclud)) {
               $snmpLinks[$i] = $data['COLUMN_NAME'];
               $i++;
            }
         }
      }

      echo "<div align='center'>";
      echo "<form name='form' method='get' action='" . $target . "'>";
      echo "<table class='tab_cadrehov'>";
      echo "<tr>";
      echo "<th>" . __('GLPI field', 'ocsinventoryng') . "</td>";
      echo "<th>" . __('OCS field', 'ocsinventoryng') . "</td>";
      echo "</tr>";
      foreach ($snmpLinks as $key => $value) {
         echo "<tr>";
         echo "<td>$value</td>";
         echo "<td>";
         if (isset($params[$value])) {
            //echo Html::select("$value", $snmpLabels, ['selected' => $params[$value]]);
            Dropdown::showFromArray("$value", $snmpLabels, ['value' => $params[$value]]);
         } else {
            //echo Html::select("$value", $snmpLabels);
            Dropdown::showFromArray("$value", $snmpLabels);
         }
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      echo "<table class='tab_cadrehov'>";
      echo "<tr>";
      echo "<td class='center'>";
      echo Html::hidden('device', ['value' => $_GET['SelectGLPI']]);
      echo Html::hidden('snmptype', ['value' => $_GET['SelectOCS']]);
      echo Html::submit(_sx('button', 'Post'), ['name' => 'valid']);
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</form>";
      echo "</div>";
   }

   static function createSnmpLinks($params){
      global $DB, $CFG_GLPI;

      $device = $params['device'];
      $ocs_srv = $_SESSION['plugin_ocsinventoryng_ocsservers_id'];

      $query = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` WHERE `ocs_snmp_type_id`='" . $params['snmptype'] . "' AND ocs_srv = $ocs_srv";
      $result = $DB->query($query);
      $oldData = [];
      while ($data = $DB->fetchArray($result)) {
         $oldData[] = $data;
      }

      foreach ($oldData as $key => $value) {
         $DB->delete(
            'glpi_plugin_ocsinventoryng_snmplinkreworks', [
               'id' => $value['id']
            ]
         );
      }
      
      foreach ($params as $key => $value) {
         if ($key != "device" && $key != "valid" && $key != "snmptype") {
            $ocsSplit = explode("_", $value);
            if (array_key_exists(1, $ocsSplit) && $ocsSplit[1] != 0) {
               $glpiSplit = explode("_", $key);
               $test1 = $ocsSplit[0];
               $test2 = $ocsSplit[1];
               $test3 = $ocsSplit[2] == "Yes" ? 1 : 0;
               $DB->insertOrDie(
                  'glpi_plugin_ocsinventoryng_snmplinkreworks', [
                     'object' => $device,
                     'glpi_col' => $key,
                     'ocs_snmp_type_id' => $ocsSplit[0],
                     'ocs_snmp_label_id' => $ocsSplit[1],
                     'is_reconsiliation' => $ocsSplit[2] == "Yes" ? 1 : 0,
                     'ocs_srv' => $ocs_srv,
   
                  ]
               );
   
            }
         }
      }
   }
   
   static function showSnmpLinks(){
      global $DB, $CFG_GLPI;

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);
      $ocs_srv = $_SESSION['plugin_ocsinventoryng_ocsservers_id'];

      $query = "SELECT `object`, `ocs_snmp_type_id` FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` WHERE ocs_srv = $ocs_srv GROUP BY `ocs_snmp_type_id`;";
      $result_glpi = $DB->query($query);
      
      $snmpTypes = $ocsClient->getSnmpTypes();

      $snmpLinks = [];

      if ($DB->numrows($result_glpi) > 0) {
         $i = 0;
         while ($data = $DB->fetchArray($result_glpi)) {
            $snmpLinks[$i]['object'] = $CFG_GLPI["glpiitemtypetables"][$data["object"]];
            $snmpLinks[$i]['type'] = $snmpTypes[$data['ocs_snmp_type_id']];
            $snmpLinks[$i]['typeId'] = $data['ocs_snmp_type_id'];
            $i++;
         }
      }
      //echo "<pre>", var_dump($CFG_GLPI), "</pre>";
 
      echo "<table class='tab_cadrehov'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>";
      echo "<a class='vsubmit' href='snmplinkrework.form.php'>" . __('Add') . "</a>";
      echo "</td></tr></table>";
      echo "<div align='center'><table class='tab_cadrehov'>";
      echo "<thead>";
      echo "<th>". __('Object', 'ocsinventoryng') . "</th>";
      echo "<th>". __('OCS snmp Type', 'ocsinventoryng') . "</th>";
      echo "<th>actions</th>";
      echo "</thead>";
      echo "<tbody>";
      foreach ($snmpLinks as $snmpLink) {
         echo "<tr>";
         echo "<td>" . __($snmpLink['object']) . "</td>";
         echo "<td>" . $snmpLink['type'] . "</td>";
         echo "<td>
            <a href='snmplinkrework.form.php?update=true&snmpocstype=". $snmpLink['typeId'] . "'><i class=\"fa fa-wrench\" aria-hidden=\"true\"></i></a>
            <a href='snmplinkrework.form.php?delete_link=true&snmpocstype=". $snmpLink['typeId'] . "'><i class=\"fa fa-times\" aria-hidden=\"true\"></i></a>
         </td>";
         echo "</tr>";
      }
      echo "</tbody>";
      echo "</table></div>";
      echo "<table class='tab_cadrehov'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>";
      echo "<a class='vsubmit' href='snmplinkrework.form.php'>" . __('Add') . "</a>";
      echo "</td></tr></table>";
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $target = $this->getFormURL();
      if (isset($options['target'])) {
        $target = $options['target'];
      }

      if (!Session::haveRight("plugin_ocsinventoryng",READ)) {
         return false;
      }

      $canedit = Session::haveRight("plugin_ocsinventoryng", UPDATE);
      if ($ID){
         $this->getFromDB($ID);
      }

      echo "<form action='".$target."' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2' class='center b'>".sprintf(__('%1$s %2$s'), ('gestion des droits :'),
                                                           Dropdown::getDropdownName("glpi_profiles",
                                                                                     $this->fields["id"]));
      echo "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>Utiliser Mon Plugin</td><td>";
      Profile::dropdownNoneReadWrite("right", $this->fields["right"], 1, 1, 1);
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center' colspan='2'>";
         echo "<input type='hidden' name='id' value=$ID>";
         echo "<input type='submit' name='update_user_profile' value='Mettre à jour'
                class='submit'>";
         echo "</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
   }

   /**
    * @param $params
    */
   static function showSnmpDeviceToAdd($params) {
      global $DB, $CFG_GLPI;

      // Default values of parameters
      $p['link']                                = [];
      $p['field']                               = [];
      $p['contains']                            = [];
      $p['searchtype']                          = [];
      $p['sort']                                = '1';
      $p['order']                               = 'ASC';
      $p['start']                               = 0;
      $p['export_all']                          = 0;
      $p['link2']                               = '';
      $p['contains2']                           = '';
      $p['field2']                              = '';
      $p['itemtype2']                           = '';
      $p['searchtype2']                         = '';
      $p['itemtype']                            = '';
      $p['ip']                                  = '';
      $p['tolinked']                            = 0;
      $p['check']                               = 'all';
      $p['plugin_ocsinventoryng_ocsservers_id'] = 0;

      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      $tolinked                            = $p['tolinked'];
      $start                               = $p['start'];
      $plugin_ocsinventoryng_ocsservers_id = $p['plugin_ocsinventoryng_ocsservers_id'];

      $title = __('Import new SNMP devices', 'ocsinventoryng');
      if ($tolinked) {
         $title = __('Import new SNMP devices into glpi', 'ocsinventoryng');
      }
      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmprework.import.php';
      if ($tolinked) {
         $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmprework.link.php';
      }

      if (!$start) {
         $start = 0;
      }
      
      $glpi_types = [
         'glpi_computers' => 'Computer',
         'glpi_monitors' => 'Monitor',
         'glpi_networkequipments' => 'NetworkEquipment',
         'glpi_peripherals' => 'Peripheral',
         'glpi_phones' => 'Phone',
         'glpi_printers' => 'Printer',
         'glpi_softwarelicenses' => 'SoftwareLicense',
         'glpi_certificates' => 'Certificate',
       ];

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);
      $ocsResult = $ocsClient->getSnmpRework($_SESSION['plugin_ocsinventoryng_ocsservers_id']);
      //$ocsImported = $ocsClient->getSnmpReworkAlreadyImported();

      //echo "<pre>" , var_dump($ocsImported) , "</pre>";
      if ($ocsResult["TOTAL_COUNT"] == 0) {
         echo "<div class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') . "</div>";
         return;
      }

      if (isset($ocsResult['SNMP'])) {
         if (count($ocsResult['SNMP'])) {
            $output_type = Search::HTML_OUTPUT;
            $begin_display = $start;
            $end_display   = $start + $_SESSION["glpilist_limit"];
            $numrows = $ocsResult['TOTAL_COUNT'];

            echo "<div class='center'>";
            echo "<h2>" . __('Snmp device imported from OCSNG', 'ocsinventoryng') . "</h2>";
            echo "</div>";

            if ($numrows) {
               $parameters = "";
               Html::printPager($start, $numrows, $target, $parameters);


               echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
               if (!$tolinked) {
                  echo "<div class='center'>";
                  PluginOcsinventoryngOcsServer::checkBox($target);
                  echo "</div>";
               }

               echo "<table class='tab_cadrehov'>";
               echo "<tr class='tab_bg_1'><td colspan='10' class='center'>";
               if (!$tolinked) {
                  echo Html::submit(_sx('button', 'Import'), ['name' => 'import_ok']);
               } else {
                  echo Html::submit(_sx('button', 'Link', 'ocsinventoryng'), ['name' => 'import_ok']);
               }
               echo "</td></tr>\n";

               $header_num = 1;
              
               foreach ($ocsResult['SNMP'] as $keysnmp => $valuesnmp) {
                  if (isset($valuesnmp) && !empty($valuesnmp)) {
                     $nbcols = count($valuesnmp[0]);
                     $keys = array_keys($valuesnmp[0]);

                     if (($keyId = array_search('ID', $keys)) !== false) {
                        unset($keys[$keyId]);
                     }

                     echo Search::showHeader($output_type, $end_display - $begin_display + 1, $nbcols);
                     echo Search::showNewLine($output_type);

                     echo Search::showHeaderItem($output_type, __('Object', 'ocsinventoryng'), $header_num);
                     foreach ($keys as $key => $value) {
                        echo Search::showHeaderItem($output_type, __($value), $header_num);
                     }
                     echo Search::showHeaderItem($output_type, "", $header_num);

                     // End Line for column headers
                     echo Search::showEndLine($output_type);

                     $row_num = 1;

                     echo "<br>";
                     echo "<div class='center'>";
                     //echo "<h3>" . $ocsClient->getSnmpTypeById($keysnmp) . " -> " . __($CFG_GLPI['glpiitemtypetables'][self::getGlpiObjectByOcsTypes($keysnmp)]) . "</h3>";
                     echo "<h3>" . sprintf(__('Transfer of OCS object %s to GLPI object %s',
                                 'ocsinventoryng'),
                                 $ocsClient->getSnmpTypeById($keysnmp), __($glpi_types[self::getGlpiObjectByOcsTypes($keysnmp)]))
                                 . "</h3>";
                     
                     echo "</div>";

                     foreach ($valuesnmp as $ID => $tab) {
                        $row_num++;
                        $item_num = 1;
      
                        echo Search::showNewLine($output_type, $row_num % 2);
                        echo Search::showItem($output_type, __($glpi_types[self::getGlpiObjectByOcsTypes($keysnmp)]), $item_num, $row_num);
                        
                        foreach ($tab as $colName => $colValue) {
                           if ($colName != 'ID') {
                              echo Search::showItem($output_type, $colValue, $item_num, $row_num);
                           }
                        }
                        
                        echo "<td width='10'>";
                        echo "<input type='checkbox' name='toimport[" . $keysnmp . "_" . $tab["ID"] . "]' " .
                              ($p['check'] == "all" ? "checked" : "") . ">";
                        
                        echo "</td></tr>\n";
                     }
                  }
                  
               }

               echo "</table><table class='tab_cadrehov'>";
               echo "<tr class='tab_bg_1'><td colspan='10' class='center'>";
               if (!$tolinked) {
                  echo Html::submit(_sx('button', 'Import'), ['name' => 'import_ok']);
               } else {
                  echo Html::submit(_sx('button', 'Link', 'ocsinventoryng'), ['name' => 'import_ok']);
               }
               echo Html::hidden('plugin_ocsinventoryng_ocsservers_id',
                                 ['value' => $plugin_ocsinventoryng_ocsservers_id]);
               echo "</td></tr>";
               echo "</table>\n";
               Html::closeForm();

               if (!$tolinked) {
                  echo "<div class='center'>";
                  PluginOcsinventoryngOcsServer::checkBox($target);
                  echo "</div>";
               }

               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr><th>" . $title . "</th></tr>\n";
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
                    "</td></tr>\n";
               echo "</table>";
            }
            echo "</div>";
         } else {
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . $title . "</th></tr>\n";
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
                 "</td></tr>\n";
            echo "</table></div>";
         }
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>" . $title . "</th></tr>\n";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
              "</td></tr>\n";
         echo "</table></div>";
      }
   }

   static function getGlpiObjectByOcsTypes($ocsTypes)
   {
      global $DB, $CFG_GLPI;
      $ocs_srv = $_SESSION['plugin_ocsinventoryng_ocsservers_id'];

      $query = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` WHERE `ocs_snmp_type_id` = $ocsTypes AND ocs_srv = $ocs_srv;";
      $result = $DB->query($query);

      $glpiObject = "";

      if ($DB->numrows($result)) {
         while ($data = $DB->fetchAssoc($result)) {
            $glpiObject = $data['object'];
         }
      }

      return $glpiObject;
   }

   /**
    * @param $itemtype
    * @param $name
    *
    * @return itemtype
    */
   function getFromDBbyName($itemtype, $name) {
      $dbu = new DbUtils();
      $item  = $dbu->getItemForItemtype($itemtype);
      $field = "`" . $dbu->getTableForItemType($itemtype) . "`.`name`";
      $item->getFromDBByCrit([$field => $name]);
      return $item;
   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $check
    * @param $start
    *
    * @return bool|void
    */
   static function showSnmpDeviceToUpdate($plugin_ocsinventoryng_ocsservers_id, $check, $start) {
      global $DB, $CFG_GLPI;

      if(!PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)){
         return false;
      }
      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         return false;
      }

      $p['link']                                = [];
      $p['field']                               = [];
      $p['contains']                            = [];
      $p['searchtype']                          = [];
      $p['sort']                                = '1';
      $p['order']                               = 'ASC';
      $p['start']                               = 0;
      $p['export_all']                          = 0;
      $p['link2']                               = '';
      $p['contains2']                           = '';
      $p['field2']                              = '';
      $p['itemtype2']                           = '';
      $p['searchtype2']                         = '';
      $p['itemtype']                            = '';
      $p['ip']                                  = '';
      $p['tolinked']                            = 0;
      $p['check']                               = 'all';
      $p['plugin_ocsinventoryng_ocsservers_id'] = 0;

      // Get linked computer ids in GLPI
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);
      $ocsImported = $ocsClient->getSnmpReworkAlreadyImported($_SESSION['plugin_ocsinventoryng_ocsservers_id']);

      if ($ocsImported["TOTAL_COUNT"] == 0) {
         echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
         return;
      }

      if (isset($ocsImported['SNMP'])) {
         if (count($ocsImported['SNMP']) > 0) {
            // Get all ids of the returned items
            $ocs_snmp_ids = [];
            $hardware     = [];
            $output_type = Search::HTML_OUTPUT;
            $begin_display = $start;
            $end_display   = $start + $_SESSION["glpilist_limit"];

            $snmps = array_slice($ocsImported['SNMP'], $start, $_SESSION['glpilist_limit']);
            
            $glpi_types = [
               'glpi_computers' => 'Computer',
               'glpi_monitors' => 'Monitor',
               'glpi_networkequipments' => 'NetworkEquipment',
               'glpi_peripherals' => 'Peripheral',
               'glpi_phones' => 'Phone',
               'glpi_printers' => 'Printer',
               'glpi_softwarelicenses' => 'SoftwareLicense',
               'glpi_certificates' => 'Certificate',
             ];

            echo "<div class='center'>";
            echo "<h2>" . __('Snmp device updated from OCSNG', 'ocsinventoryng') . "</h2>";

            $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmprework.sync.php';
            if (($numrows = $ocsImported['TOTAL_COUNT']) > 0) {
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $target . "'>";
               PluginOcsinventoryngOcsServer::checkBox($target);

               echo "<table class='tab_cadrehov'>";
               echo "<tr class='tab_bg_1'><td class='center'>";
               echo Html::submit(_sx('button', 'Synchronize'), ['name' => 'update_ok']);
               echo "<button type='submit' value='" . __('Delete') . "' name='delete' class='vsubmit'>" . __('Delete') . "</button>";
               //echo Html::submit(_sx('button', 'Delete'), ['name' => 'delete']);
               echo "</td></tr>\n";

               foreach ($ocsImported['SNMP'] as $keysnmp => $valuesnmp) {
                  if (isset($valuesnmp) && !empty($valuesnmp)) {
                     $nbcols = count($valuesnmp[0]);
                     $keys = array_keys($valuesnmp[0]);
   
                     if (($keyId = array_search('ID', $keys)) !== false) {
                        unset($keys[$keyId]);
                     }
   
                     echo Search::showHeader($output_type, $end_display - $begin_display + 1, $nbcols);
                     echo Search::showNewLine($output_type);
                     
                     echo "<br>";
                     echo "<div class='center'>";
                     echo "<h3>" . sprintf(__('Transfer of OCS object %s to GLPI object %s',
                                 'ocsinventoryng'),
                                 $ocsClient->getSnmpTypeById($keysnmp), __($glpi_types[self::getGlpiObjectByOcsTypes($keysnmp)]))
                                 . "</h3>";
                     echo "</div>";
   
                     echo Search::showHeaderItem($output_type, __('Object', 'ocsinventoryng'), $header_num);
                     foreach ($keys as $key => $value) {
                        echo Search::showHeaderItem($output_type, __($value), $header_num);
                     }
                     echo Search::showHeaderItem($output_type, "", $header_num);
   
                     // End Line for column headers
                     echo Search::showEndLine($output_type);
   
                     $row_num = 1;
   
                     foreach ($valuesnmp as $ID => $tab) {
                        $row_num++;
                        $item_num = 1;
      
                        echo Search::showNewLine($output_type, $row_num % 2);
                        echo Search::showItem($output_type, __($glpi_types[self::getGlpiObjectByOcsTypes($keysnmp)]), $item_num, $row_num);
                        
                        foreach ($tab as $colName => $colValue) {
                           if ($colName != 'ID') {
                              echo Search::showItem($output_type, $colValue, $item_num, $row_num);
                           }
                        }
                        
                        echo "<td width='10'>";
                        echo "<input type='checkbox' name='toupdate[" . $keysnmp . "_" . $tab["ID"] . "]' " .
                              ($p['check'] == "all" ? "checked" : "") . ">";
                        
                        echo "</td></tr>\n";
                     }
                  }
                  
               }
               echo "</table><table class='tab_cadrehov'>";

               echo "<tr class='tab_bg_1'><td class='center'>";
               echo Html::submit(_sx('button', 'Synchronize'), ['name' => 'update_ok']);
               echo "<button type='submit' value='" . __('Delete') . "' name='delete' class='vsubmit'>" . __('Delete') . "</button>";
               //echo Html::submit(_sx('button', 'Delete'), ['name' => 'delete']);
               echo Html::hidden('plugin_ocsinventoryng_ocsservers_id',
                                 ['value' => $plugin_ocsinventoryng_ocsservers_id]);
               echo "</td></tr>";
               echo "</table>\n";

               echo "<div class='center'>";
               PluginOcsinventoryngOcsServer::checkBox($target);
               echo "</div>";
               Html::closeForm();
               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<br><span class='b'>" . __('Update SNMP device', 'ocsinventoryng') . "</span>";
            }
            echo "</div>";
         } else {
            echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
         }
      } else {
         echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
      }
   }

   /**
    * Make the item link between glpi and ocs.
    *
    * This make the database link between ocs and glpi databases
    *
    * @param $ocsid integer : ocs item unique id.
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $items_id
    * @param $itemtype
    * return int : link id.
    *
    * @internal param int $glpi_computers_id : glpi computer id
    * @return bool|item
    */
   static function ocsSnmpLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $items_id, $itemtype) {
      global $DB;

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      //$ocsSnmp = $ocsClient->getSnmpDevice($ocsid);
      $ocsSnmp = null;

      if (is_null($ocsSnmp)) {
         return false;
      }

      $query  = "INSERT INTO `glpi_plugin_ocsinventoryng_snmpocslinks`
                       (`items_id`, `ocs_id`, `itemtype`,
                        `last_update`, `plugin_ocsinventoryng_ocsservers_id`, `linked`)
                VALUES ('$items_id', '$ocsid', '" . $itemtype . "',
                        '" . $_SESSION["glpi_currenttime"] . "', $plugin_ocsinventoryng_ocsservers_id, 1)";
      $result = $DB->query($query);

      if ($result) {
         return ($DB->insertId());
      }

      return false;
   }

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $params
    *
    * @return array|bool
    * @internal param $computers_id
    *
    */
   static function linkSnmpDevice($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params) {
      //TODO enlever pour en conserver 1 seul
      if(!PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)){
         return false;
      }
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      //TODOSNMP entites_id ?

      $p['itemtype'] = -1;
      $p['items_id'] = -1;
      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      $ocs_id_change = true;

      if ($p['itemtype'] != -1 && $p['items_id'] > 0 &&
         ($idlink = self::ocsSnmpLink($ocsid, $plugin_ocsinventoryng_ocsservers_id,$p['items_id'], $p['itemtype']))) {
         self::updateSnmp($idlink, $plugin_ocsinventoryng_ocsservers_id);
         return ['status' => PluginOcsinventoryngOcsProcess::SNMP_LINKED];
      }
      return false;
   }

   static function deleteSnmp($id, $plugin_ocsinventoryng_ocsservers_id) {
      global $DB;

      $ocs_srv = $_SESSION['plugin_ocsinventoryng_ocsservers_id'];

      $split = explode("_", $id);
      $ocsTable = $split[0];
      $ocsId = $split[1];

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);
      $data = $ocsClient->getSnmpValueByTableAndId($ocsTable, $ocsId);

      $queryReconciliation = "SELECT *, (SELECT GROUP_CONCAT(glpi_col) FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` b WHERE a.ocs_snmp_type_id = b.ocs_snmp_type_id AND is_reconsiliation = 1 ) AS reconciliation FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` a WHERE ocs_snmp_type_id = $ocsTable AND ocs_srv = $ocs_srv";
      $reconciliationData = $DB->query($queryReconciliation);
      if ($DB->numrows($reconciliationData)) {
         while ($lines = $DB->fetchAssoc($reconciliationData)) {
            if (!is_null($lines['reconciliation'])) {
               $rec = explode(',', $lines['reconciliation']);
            }
            
            $reconciliationArray[$lines['ocs_snmp_type_id']]['link'][$lines['glpi_col']] = $lines['ocs_snmp_label_id'];
            $reconciliationArray[$lines['ocs_snmp_type_id']]['object'] = $lines['object'];
            $reconciliationArray[$lines['ocs_snmp_type_id']]['reconciliation'] = $rec ?? null;

         }
      }
      $glpiTable = $reconciliationArray[$ocsTable]['object'];

      //get database column's types of selected object
      $queryTypes = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$glpiTable'";
      $resultTypes = $DB->query($queryTypes);

      $dataTypesArray = [];
      while ($dataTypes = $DB->fetchAssoc($resultTypes)) {
         $dataTypesArray[$dataTypes['COLUMN_NAME']] = $dataTypes['DATA_TYPE'];
      }

      $queryCheck = "SELECT * FROM $glpiTable";
      $where = "";
      if (isset($reconciliationArray[$ocsTable]['reconciliation'])) {
         $where .= " WHERE ";
         foreach ($reconciliationArray[$ocsTable]['reconciliation'] as $reconciliationKey => $reconciliationValue) {
            $where .= $reconciliationValue . " = '" . $data[$reconciliationArray[$ocsTable]['link'][$reconciliationValue]] . "' AND ";
         }
         $where = rtrim($where, ' AND ');
         $force = false;
      } else {
         $force = true;
      }
      
      if ($where) {
         $queryCheck .= $where;
      }
      $queryCheck .= ";";

      $checkResult = $DB->query($queryCheck);
      if (($checkResult && !$DB->numrows($checkResult) == 0) || $force) {
         $insert = [];
         foreach ($reconciliationArray[$ocsTable]['link'] as $reconciliationKey => $reconciliationValue) {
            //echo "<pre>" , var_dump(substr($reconciliationKey, -3)), "</pre>";
            if (substr($reconciliationKey, -3) == '_id') {
               $insert[$reconciliationKey] = self::getObjectOrInsert($reconciliationKey, $data[$reconciliationValue]);
            } else {
               $value = self::convertStrToSqlValueBySqlType($data[$reconciliationValue], $dataTypesArray[$reconciliationKey]);
               if (isset($value) && $value != "") {
                  $insert[$reconciliationKey] = $value;
               }
            }
         }

         $DB->deleteOrDie($glpiTable, ['name' => $insert['name']]);
         return true;
      }
   }
}
