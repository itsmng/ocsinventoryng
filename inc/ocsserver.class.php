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

/// OCS config class

/**
 * Class PluginOcsinventoryngOcsServer
 */
class PluginOcsinventoryngOcsServer extends CommonDBTM {

   static $types     = ['Computer'];
   static $rightname = "plugin_ocsinventoryng";
   // From CommonDBTM
   public $dohistory = true;

   // Connection types
   const CONN_TYPE_DB           = 0;
   const CONN_TYPE_SOAP         = 1;
   const OCS_VERSION_LIMIT      = 4020;
   const OCS1_3_VERSION_LIMIT   = 5004;
   const OCS2_VERSION_LIMIT     = 6000;
   const OCS2_1_VERSION_LIMIT   = 7006;
   const OCS2_2_VERSION_LIMIT   = 7009;
   const OCS2_7_VERSION_LIMIT   = 7023;//https://github.com/OCSInventory-NG/OCSInventory-ocsreports/tree/2.6/files/update
   const OCS2_8_VERSION_LIMIT   = 7029;//https://github.com/OCSInventory-NG/OCSInventory-ocsreports/tree/2.7/files/update
   const ACTION_PURGE_COMPUTER  = 0; // Action cronCleanOldAgents : Purge computer
   const ACTION_DELETE_COMPUTER = 1; // Action cronCleanOldAgents : delete computer

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return _n('OCSNG server', 'OCSNG servers', $nb, 'ocsinventoryng');
   }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               //If connection to the OCS DB  is ok, and all rights are ok too
               $ong[1] = __('Test');

               try {

                  if (self::checkOCSconnection($item->getID())
                        && self::checkVersion($item->getID())
                        && self::checkTraceDeleted($item->getID())
                  ) {
                     $ong[2] = __('Datas to import', 'ocsinventoryng');
                     $ong[3] = __('Import options', 'ocsinventoryng');
                     $ong[4] = __('General history', 'ocsinventoryng');
                  }
                  if ($item->getField('ocs_url')) {
                     $ong[5] = __('OCSNG console', 'ocsinventoryng');
                  }
               } catch (Exception $e) {
                  // Nothing to do
               }

               return $ong;
         }

      }
      return '';
   }

   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showDBConnectionStatus($item->getID());
               break;

            case 2 :
               $item->ocsFormConfig($item->getID());
               break;

            case 3 :
               $item->ocsFormImportOptions($item->getID());
               break;

            case 4 :
               $item->ocsHistoryConfig($item->getID());
               break;

            case 5 :
               self::showOcsReportsConsole($item->getID());
               break;
         }
      }
      return true;
   }

   /**
    * @param array $options
    *
    * @return array
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('PluginOcsinventoryngSnmpOcslink', $ong, $options);
      $this->addStandardTab('PluginOcsinventoryngIpdiscoverOcslinkrework', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;

   }

   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(2)
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'massiveaction' => false,
         'itemlink_type' => $this->getType()
      ];

      $tab[] = [
         'id'    => '3',
         'table' => $this->getTable(),
         'field' => 'ocs_db_host',
         'name'  => __('Server')
      ];

      $tab[] = [
         'id'       => '6',
         'table'    => $this->getTable(),
         'field'    => 'is_active',
         'name'     => __('Active'),
         'datatype' => 'bool'
      ];

      $tab[] = [
         'id'            => '19',
         'table'         => $this->getTable(),
         'field'         => 'date_mod',
         'name'          => __('Last update'),
         'datatype'      => 'datetime',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'       => '16',
         'table'    => $this->getTable(),
         'field'    => 'comment',
         'name'     => __('Comments'),
         'datatype' => 'text'
      ];

      $tab[] = [
         'id'       => '17',
         'table'    => $this->getTable(),
         'field'    => 'use_massimport',
         'name'     => __('Expert sync mode', 'ocsinventoryng'),
         'datatype' => 'bool'
      ];

      $tab[] = [
         'id'       => '18',
         'table'    => $this->getTable(),
         'field'    => 'ocs_db_utf8',
         'name'     => __('Database in UTF8', 'ocsinventoryng'),
         'datatype' => 'bool'
      ];

      $tab[] = [
         'id'       => '30',
         'table'    => $this->getTable(),
         'field'    => 'id',
         'name'     => __('ID'),
         'datatype' => 'number'
      ];

      return $tab;
   }

   /**
    * Print ocs menu
    *
    * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
    *
    * @return void (display)
    * @throws \GlpitestSQLError
    */
   static function setupMenu($plugin_ocsinventoryng_ocsservers_id) {
      global $CFG_GLPI, $DB;
      $name       = "";
      $ocsservers = [];

      $dbu                 = new DbUtils();
      $numberActiveServers = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]);
      if ($numberActiveServers > 0) {
         $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`, `glpi_plugin_ocsinventoryng_ocsservers`.`name`
            FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
            LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
               ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` 
               = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
            WHERE `profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . " 
            AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`= 1
            ORDER BY `name` ASC";
         foreach ($DB->request($query) as $data) {
            $ocsservers[$data['id']] = $data['name'];
         }

         $form = [
            'actions' => $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php",
            'buttons' => [[]],
            'content' => [
               __('Choice of an OCSNG server', 'ocsinventoryng') => [
                  'visible' => true,
                  'inputs' => [
                     __('Name') => [
                        'type' => 'select',
                        'name' => 'PluginOcsinventoryngOcsServer',
                        'values' => [Dropdown::EMPTY_VALUE] + $ocsservers,
                        'value' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                        'actions' => getItemActionButtons(['info'], self::class),
                        'col_lg' => 12,
                        'col_md' => 12,
                        'hooks' => [
                           'change' => "this.form.submit()"
                        ]
                     ],
                     '' => [
                        'content' => "<div class='text-center text-danger'>" . 
                           __('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng') .
                           "</div>",
                        'col_lg' => 12,
                        'col_md' => 12,
                     ]
                  ]
               ]
            ]
         ];
         renderTwigForm($form);
      }
      $sql      = "SELECT `name`, `is_active`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                  ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
              WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`id` = '" . $plugin_ocsinventoryng_ocsservers_id . "' 
              AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id`= '" . $_SESSION["glpiactiveprofile"]['id'] . "'";
      $result   = $DB->query($sql);
      $isactive = 0;
      if ($DB->numrows($result) > 0) {
         $datas    = $DB->fetchArray($result);
         $name     = " : " . $datas["name"];
         $isactive = $datas["is_active"];
      }

      $usemassimport = self::useMassImport();

      echo "<div class='center'><table class='tab_cadre_fixe' width='40%' cellpadding='10'>";
      if (Session::haveRight("plugin_ocsinventoryng", READ)) {
         echo "<tr><th colspan='4'>";
         printf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name);
         echo "</th></tr>";
         //config server
         if ($isactive) {
            echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
                  <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsserver.form.php?id=$plugin_ocsinventoryng_ocsservers_id'>
                    <i style='color:steelblue' class='fas fa-cogs fa-3x' 
                        title=\"" . __s('Configuration of OCSNG server', 'ocsinventoryng') . "\"></i>
                   <br><br>" . sprintf(__('Configuration of OCSNG server %s', 'ocsinventoryng'), $name) . "
                  </a></td>";

            if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
               //config massimport
               echo "<td class='center b' colspan='2'>
                     <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/config.form.php'>
                      <i style='color:steelblue' class='fas fa-sync-alt fa-3x' 
                        title=\"" . __s('Automatic synchronization\'s configuration', 'ocsinventoryng') . "\"></i>
                        <br><br>" . __("Automatic synchronization's configuration", 'ocsinventoryng') . "
                     </a></td>";
            } else {
               echo "<td colspan='2'></td>";
            }
            echo "</tr>\n";

         }

      }

      echo "<tr><th colspan='4'>";
      echo __('Setup rules engine', 'ocsinventoryng');
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
            <a href='" . $CFG_GLPI["root_doc"] . "/front/ruleimportentity.php'>
            <i style='color:firebrick' class='fas fa-book fa-3x' 
                        title=\"" . __s('Rules for assigning an item to an entity') . "\"></i><br><br>" . __('Rules for assigning an item to an entity') . "</a>";
      echo "<br><span style='color:firebrick'>";
      echo __('Setup rules for choose entity on items import', 'ocsinventoryng');
      echo "</span></td>";

      echo "<td class='center b' colspan='2'>
            <a href='" . $CFG_GLPI["root_doc"] . "/front/ruleimportcomputer.php'>
         <i style='color:firebrick' class='fas fa-book fa-3x' 
                        title=\"" . __s('Rules for import and link computers') . "\"></i><br><br>" . __('Rules for import and link computers') . "</a>";
      echo "<br><span style='color:firebrick'>";
      echo __('Setup rules for select criteria for items link', 'ocsinventoryng');
      echo "</span></td>";

      echo "</tr>\n";

      echo "</table></div>";
   }


   /**
    * Print ocs menu
    *
    * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
    *
    * @return void (display)
    * @throws \GlpitestSQLError
    */
   static function importMenu($plugin_ocsinventoryng_ocsservers_id) {
      global $CFG_GLPI, $DB;
      $name                = "";
      $ocsservers          = [];
      $dbu                 = new DbUtils();
      $numberActiveServers = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers',
                                                        ["is_active" => 1]);
      if ($numberActiveServers > 0) {
         $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`, `glpi_plugin_ocsinventoryng_ocsservers`.`name`
            FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
            LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
               ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
            WHERE `profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . " AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`='1'
            ORDER BY `name` ASC";
         foreach ($DB->request($query) as $data) {
            $ocsservers[$data['id']] = $data['name'];
         }

         $form = [
            'action' => $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php",
            'buttons' => [[]],
            'content' => [
               __('Choice of an OCSNG server', 'ocsinventoryng') => [
                  'visible' => true,
                  'inputs' => [
                     __('Name') => [
                        'type' => 'select',
                        'name' => 'PluginOcsinventoryngOcsServer',
                        'values' => [Dropdown::EMPTY_VALUE] + $ocsservers,
                        'value' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                        'actions' => getItemActionButtons(['info'], self::class),
                        'col_lg' => 12,
                        'col_md' => 12,
                        'hooks' => [
                           'change' => "this.form.submit()"
                        ]
                     ],
                     '' => [
                        'content' => "<div class='text-center text-danger'>" . 
                           __('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng') .
                           "</div>",
                        'col_lg' => 12,
                        'col_md' => 12,
                     ]
                  ]
               ]
            ]
         ];
         renderTwigForm($form);
      }
      $sql      = "SELECT `name`, `is_active`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                  ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
              WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`id` = " . $plugin_ocsinventoryng_ocsservers_id . " 
              AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id`= '" . $_SESSION["glpiactiveprofile"]['id'] . "'";
      $result   = $DB->query($sql);
      $isactive = 0;
      if ($DB->numrows($result) > 0) {
         $datas    = $DB->fetchArray($result);
         $name     = " : " . $datas["name"];
         $isactive = $datas["is_active"];
      }

      $usemassimport = self::useMassImport();

      echo "<div class='center'><table class='tab_cadre_fixe' width='40%' cellpadding='10'>";
      echo "<tr><th colspan='4'>";
      printf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name);
      echo "<br>";
      if (Session::haveRight("plugin_ocsinventoryng", READ)
          && $isactive) {
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsserver.form.php?id=" . $plugin_ocsinventoryng_ocsservers_id . "&forcetab=PluginOcsinventoryngOcsServer\$2'>";
         echo __('See Setup : Datas to import before', 'ocsinventoryng');
         echo "</a>";
         echo "</th></tr>";
      }

      if (Session::haveRight("plugin_ocsinventoryng", READ)) {
         //config server
         if ($isactive) {

            if (Session::haveRight("plugin_ocsinventoryng_import", READ)) {
               //manual import
               echo "<tr class='tab_bg_1'>";
               if (Session::haveRight("plugin_ocsinventoryng_import", READ)) {
                  echo "<td class='center b' colspan='2'>
                  <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.import.php'>";
                  echo "<i style='color:steelblue' class='fas fa-plus fa-3x' 
                           title=\"" . __s('Import new computers', 'ocsinventoryng') . "\"></i>";
                  echo "<br>" . __('Import new computers', 'ocsinventoryng') . "
                  </a></td>";
               } else {
                  echo "<td class='center b' colspan='2'></td>";
               }
               if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                  //threads
                  echo "<td class='center b' colspan='2'>
                     <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/thread.php?plugin_ocsinventoryng_ocsservers_id=" . $plugin_ocsinventoryng_ocsservers_id . "'>
                      <i style='color:steelblue' class='fas fa-play fa-3x' 
                           title=\"" . __s('Scripts execution of automatic actions', 'ocsinventoryng') . "\"></i>
                        <br>" . __('Scripts execution of automatic actions', 'ocsinventoryng') . "
                     </a></td>";
               } else {
                  echo "<td colspan='2'></td>";
               }
               echo "</tr>\n";
            }
            //manual synchro
            if (Session::haveRight("plugin_ocsinventoryng_sync", READ)) {
               echo "<tr class='tab_bg_1'>";
               if (Session::haveRight("plugin_ocsinventoryng_sync", READ) && $isactive) {
                  echo "<td class='center b' colspan='2'>
                  <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.sync.php'>
                  <i style='color:steelblue' class='fas fa-sync-alt fa-3x' 
                           title=\"" . __s('Synchronize computers already imported', 'ocsinventoryng') . "\"></i>
                     <br>" . __('Synchronize computers already imported', 'ocsinventoryng') . "
                  </a></td>";
               } else {
                  echo "<td class='center b' colspan='2'></td>";
               }
               $log = PluginOcsinventoryngConfig::logProcessedComputers();
               if ($log && Session::haveRight("plugin_ocsinventoryng_import", READ) && $usemassimport) {
                  //host imported by thread
                  echo "<td class='center b' colspan='2'>
                     <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/detail.php'>
                     <i style='color:green' class='fas fa-check fa-3x' 
                           title=\"" . __s('Computers imported by automatic actions', 'ocsinventoryng') . "\"></i>
                        <br>" . __('Computers imported by automatic actions', 'ocsinventoryng') . "
                     </a></td>";
               } else {
                  echo "<td class='center b' colspan='2'></td>";
               }
               echo "</tr>\n";
            }
            //link
            if (Session::haveRight("plugin_ocsinventoryng_link", READ)
                || Session::haveRight("plugin_ocsinventoryng", READ)) {
               echo "<tr class='tab_bg_1'>";

//               if (Session::haveRight("plugin_ocsinventoryng_link", READ) && $isactive) {
//                  echo "<td class='center b' colspan='2'>
//                  <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.link.php'>
//                  <i style='color:steelblue' class='fas fa-arrow-alt-circle-down fa-3x'
//                           title=\"" . __s('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng') . "\"></i>
//                     <br>" . __('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng') . "
//                  </a></td>";
//               } else {
                  echo "<td class='center b' colspan='2'></td>";
//               }

               if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                  //host not imported by thread
                  echo "<td class='center b' colspan='2'>
                     <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/notimportedcomputer.php'>
                     <i style='color:firebrick' class='fas fa-times fa-3x' 
                           title=\"" . __s('Computers not imported by automatic actions', 'ocsinventoryng') . "\"></i>
                        <br>" . __('Computers not imported by automatic actions', 'ocsinventoryng') . "
                     </a></td>";
               } else {
                  echo "<td colspan='2'></td>";
               }
               echo "</tr>\n";
            }
         } else {
            echo "<tr class='tab_bg_2'><td class='center red' colspan='2'>";
            echo __('The selected server is not active. Import and synchronisation is not available', 'ocsinventoryng');
            echo "</td></tr>\n";
         }
      }

      if ((Session::haveRight("plugin_ocsinventoryng_clean", UPDATE)
           || Session::haveRight(static::$rightname, UPDATE))
          && $isactive) {
         echo "<tr class='tab_bg_1'>";
         if (Session::haveRight(static::$rightname, UPDATE)) {
            echo "<td class='center b' colspan='2'>";

            echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/deleted_equiv.php'>
            <i style='color:steelblue' class='fas fa-trash fa-3x' 
                           title=\"" . __s('Clean OCSNG deleted computers', 'ocsinventoryng') . "\"></i>
                  <br>" . __('Clean OCSNG deleted computers', 'ocsinventoryng') . "
               </a>";
            $ocsClient   = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
            $deleted_pcs = $ocsClient->getTotalDeletedComputers();
            if ($deleted_pcs > 0) {
               echo "<br><span style='color:firebrick'>" . $deleted_pcs . " " . __('Pc deleted', 'ocsinventoryng');
               echo "</span>";
            }
            echo "<br><span style='color:grey'>" . __('Update ID of deleted computers of OCSNG', 'ocsinventoryng');
            echo "</span>";
            echo "</td>";
         } else {
            echo "<td colspan='2'></td>";
         }
         echo "<td class='center b' colspan='2'>
               <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.clean.php'>
               <i style='color:steelblue' class='fas fa-trash fa-3x' 
                           title=\"" . __s('Clean OCSNG deleted computers', 'ocsinventoryng') . "\"></i>
                  <br>" . __('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "
               </a>";
         echo "<br><span style='color:grey'>" . __('Drop links for not present computers into OCSNG', 'ocsinventoryng');
         echo "</span>";
         echo "</td><tr>";
      }
      echo "</table></div>";
   }

   /**
    * Print ocs config form
    *
    * @param $ID Integer : Id of the ocs config
    *
    * @return bool
    * @internal param form $target target
    */
   function ocsFormConfig($ID) {

      if (!Session::haveRight("plugin_ocsinventoryng", READ)) {
         return false;
      }
      $this->getFromDB($ID);

      $import_array = ["0" => __('No import'),
                       "1" => __('Global import', 'ocsinventoryng'),
                       "2" => __('Unit import', 'ocsinventoryng')];

      $serial_import_array = ["0" => __('No import'),
                        "1" => __('Global import', 'ocsinventoryng'),
                        "2" => __('Unit import', 'ocsinventoryng'),
                        "3" => __('Unit import on serial number', 'ocsinventoryng'),
                        "4" => __('Unit import serial number only', 'ocsinventoryng')];

      $opt                 = PluginOcsinventoryngOcsAdminInfosLink::getColumnListFromAccountInfoTable($ID, 'accountinfo');
      $oserial             = $opt;
      $oserial['ASSETTAG'] = "ASSETTAG";
      
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      
      $values = [
         'serialValue' => 'otherserial',
         'locationValue' => 'locations_id',
         'groupValue' => 'groups_id',
         'contactNumValue' => 'contact_num',
         'networkValue' => 'networks_id',
         'useDateValue' => 'use_date',
      ];
      foreach ($values as $key => $val) {
         $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, $val);
         $values[$key] = (isset($link->fields["ocs_column"]) ? $link->fields["ocs_column"] : "");
      }

      $form = [
         'action' => Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer"),
         'attributes' => [
            'name' => 'formconfig',
            'id' => 'formconfig',
            'method' => 'post'
         ],
         'buttons' => [
            Session::haveRight("plugin_ocsinventoryng", UPDATE) ? [
               'name' => 'update',
               'value' => __('Save'),
               'type' => 'submit',
               'class' => 'btn btn-secondary',
            ] : []
         ],
         'content' => [
            __('All') => [
               'visible' => 'true',
               'inputs' => [
                  '' => [
                     'type' => 'select',
                     'name' => 'init_all',
                     'values' => [-1 => Dropdown::EMPTY_VALUE, 0 => __('No'), 1 => __('Yes')],
                     'col_lg' => 12,
                     'col_md' => 12,
                     'value' => -1,
                     'hooks' => [
                        'change' => <<<JS
                           const value = this.value;
                           if(value != -1) {
                              const names = [
                                 "init_all",
                                 "import_otherserial",
                                 "import_location",
                                 "import_group",
                                 "import_contact_num",
                                 "import_use_date",
                                 "import_network",
                              ];
                              const checkboxes = $("form[id='formconfig'] input[type='checkbox']");
                              $.each(checkboxes, function(index, checkbox){
                                 if (!names.includes(checkbox.name)) {
                                    $(checkbox).prop('checked', value == 1)
                                 }
                              });
                              const selects = $("form[id='formconfig'] select");
                              $.each(selects, function(index, select){
                                 if (!names.includes(select.name)) {
                                    $(select).val(value)
                                 }
                              });
                           }
                        JS
                     ]
                  ]
               ]
            ],
            __('General information', 'ocsinventoryng') => [
               'visible' => 'true',
               'inputs' => [
                  [
                     'type' => 'hidden',
                     'name' => 'id',
                     'value' => $ID
                  ],
                  '' => [
                     'content' => "<span style='color:red;'>" . __('Warning : the import entity rules depends on selected fields', 'ocsinventoryng') . "</span>",
                     'col_lg' => 12,   
                     'col_md' => 12,   
                  ],
                  __('Name') => [
                     'type' => 'checkbox',
                     'name' => 'import_general_name',
                     'value' => $this->fields["import_general_name"],
                  ],
                  __('Operating system') => [
                     'type' => 'checkbox',
                     'name' => 'import_general_os',
                     'value' => $this->fields["import_general_os"],
                  ],
                  __('Serial of the operating system') => [
                     'type' => 'checkbox',
                     'name' => 'import_os_serial',
                     'value' => $this->fields["import_os_serial"],
                  ],
                  __('Serial number') => [
                     'type' => 'checkbox',
                     'name' => 'import_general_serial',
                     'value' => $this->fields["import_general_serial"],
                     'title' => nl2br(__('Depends on Bios import', 'ocsinventoryng')),
                  ],
                  __('Model') => [
                     'type' => 'checkbox',
                     'name' => 'import_general_model',
                     'value' => $this->fields["import_general_model"],
                     'title' => nl2br(__('Depends on Bios import', 'ocsinventoryng')),
                  ],
                  _n('Manufacturer', 'Manufacturers', 1) => [
                     'type' => 'checkbox',
                     'name' => 'import_general_manufacturer',
                     'value' => $this->fields["import_general_manufacturer"],
                     'title' => nl2br(__('Depends on Bios import', 'ocsinventoryng')),
                  ],
                  __('Type') => [
                     'type' => 'checkbox',
                     'name' => 'import_general_type',
                     'value' => $this->fields["import_general_type"],
                     'title' => nl2br(__('Depends on Bios import', 'ocsinventoryng')),
                  ],
                  __('Domain') => [
                     'type' => 'checkbox',
                     'name' => 'import_general_domain',
                     'value' => $this->fields["import_general_domain"],
                  ],
                  __('Comments') => [
                     'type' => 'checkbox',
                     'name' => 'import_general_comment',
                     'value' => $this->fields["import_general_comment"],
                  ],
                  __('UUID') => self::checkVersion($ID) ? [
                     'type' => 'checkbox',
                     'name' => 'import_general_uuid',
                     'value' => $this->fields["import_general_uuid"],
                  ] : [
                     'type' => 'hidden',
                     'name' => 'import_general_uuid',
                     'value' => 0,
                  ],
                  __('IP') => [
                     'type' => 'checkbox',
                     'name' => 'import_ip',
                     'value' => $this->fields["import_ip"],
                  ],
               ]
            ],
            __('User informations', 'ocsinventoryng') => [
               'visible' => 'true',
               'inputs' => [
                  __('Alternate username') => [
                     'type' => 'checkbox',
                     'name' => 'import_general_contact',
                     'value' => $this->fields["import_general_contact"],
                  ],
                  __('Affect default group of user by default', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_user_group_default',
                     'value' => $this->fields["import_user_group_default"],
                     'title' => nl2br(__('Depends on contact import', 'ocsinventoryng'))
                  ],
                  __('Affect user location by default', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_user_location',
                     'value' => $this->fields["import_user_location"],
                     'title' => nl2br(__('Depends on contact import', 'ocsinventoryng'))
                  ],
                  __('Affect first group of user by default', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_user_group',
                     'value' => $this->fields["import_user_group"],
                     'title' => nl2br(__('Depends on contact import', 'ocsinventoryng'))
                  ],
               ]
            ],
            _n('Component', 'Components', 2) => [
               'visible' => true,
               'inputs' => [
                  __('Processor') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_processor',
                     'value' => $this->fields["import_device_processor"],
                     'title' => nl2br(__('After 7006 version of OCS Inventory NG', 'ocsinventoryng'))
                  ],
                  __('Memory') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_memory',
                     'value' => $this->fields["import_device_memory"],
                  ],
                  __('Hard drive') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_hdd',
                     'value' => $this->fields["import_device_hdd"],
                  ],
                  __('Network card') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_iface',
                     'value' => $this->fields["import_device_iface"],
                  ],
                  __('Graphics card') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_gfxcard',
                     'value' => $this->fields["import_device_gfxcard"],
                  ],
                  __('Soundcard') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_sound',
                     'value' => $this->fields["import_device_sound"],
                  ],
                  _n('Drive', 'Drives', 2) => [
                     'type' => 'checkbox',
                     'name' => 'import_device_drive',
                     'value' => $this->fields["import_device_drive"],
                  ],
                  __('Modems') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_modem',
                     'value' => $this->fields["import_device_modem"],
                  ],
                  _n('Port', 'Ports', 2) => [
                     'type' => 'checkbox',
                     'name' => 'import_device_port',
                     'value' => $this->fields["import_device_port"],
                  ],
                  __('Bios') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_bios',
                     'value' => $this->fields["import_device_bios"],
                  ],
                  __('System board') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_motherboard',
                     'value' => $this->fields["import_device_motherboard"],
                     'title' => nl2br(__('After 7009 version of OCS Inventory NG && Depends on Bios import', 'ocsinventoryng'))
                  ],
                  _n('Controller', 'Controllers', 2) => [
                     'type' => 'checkbox',
                     'name' => 'import_device_controller',
                     'value' => $this->fields["import_device_controller"],
                  ],
                  __('Other component', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_device_slot',
                     'value' => $this->fields["import_device_slot"],
                  ],
               ]
            ],
            __('Linked objects', 'ocsinventoryng') => [
               'visible' => true,
               'inputs' => [
                  _n('Device', 'Devices', 2) => [
                     'type' => 'select',
                     'name' => 'import_periph',
                     'values' => $import_array,
                     'value' => $this->fields["import_periph"],
                  ],
                  _n('Monitor', 'Monitors', 2) => [
                     'type' => 'select',
                     'name' => 'import_monitor',
                     'values' => $serial_import_array,
                     'value' => $this->fields["import_monitor"],
                  ],
                  __('Comments') . " " . _n('Monitor', 'Monitors', 2) => [
                     'type' => 'checkbox',
                     'name' => 'import_monitor_comment',
                     'value' => $this->fields["import_monitor_comment"],
                  ],
                  _n('Printer', 'Printers', 2) => [
                     'type' => 'select',
                     'name' => 'import_printer',
                     'values' => $import_array,
                     'value' => $this->fields["import_printer"],
                  ],
                  _n('Software', 'Software', 2) => [
                     'type' => 'select',
                     'name' => 'import_software',
                     'values' => ["0" => __('No import'), "1" => __('Unit import', 'ocsinventoryng')],
                     'value' => $this->fields["import_software"],
                  ],
                  _n('Volume', 'Volumes', 2) => [
                     'type' => 'checkbox',
                     'name' => 'import_disk',
                     'value' => $this->fields["import_disk"],
                  ],
                  __('Registry', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_registry',
                     'value' => $this->fields["import_registry"],
                  ],
                  _n('Virtual machine', 'Virtual machines', 2) => self::checkVersion($ID) ? [
                     'type' => 'checkbox',
                     'name' => 'import_vms',
                     'value' => $this->fields["import_vms"],
                  ] : [
                     'type' => 'hidden',
                     'name' => 'import_vms',
                     'value' => 0,
                  ],
                  '' => [
                     'content' => '<p class="text-info">' .
                        __('No import: the plugin will not import these elements', 'ocsinventoryng') . '<br>'
                        . __('Global import: everything is imported but the material is globally managed (without duplicate)', 'ocsinventoryng') . '<br>'
                        . __("Unit import: everything is imported as it is", 'ocsinventoryng') . '</p>',
                     'col_lg' => 12,
                     'col_md' => 12,
                  ]
               ]
            ],
            __('OCS Inventory NG plugins', 'ocsinventoryng') => [
               'visible' => true,
               'inputs' => [
                  __('Microsoft Office licenses', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_officepack',
                     'value' => $this->fields["import_officepack"],
                     'title' => nl2br(__('Depends on software import and OfficePack Plugin for (https://github.com/PluginsOCSInventory-NG/officepack) OCSNG must be installed', 'ocsinventoryng')),
                  ],
                  __('Antivirus', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_antivirus',
                     'value' => $this->fields["import_antivirus"],
                     'title' => nl2br(__('Security Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/security) must be installed', 'ocsinventoryng')),
                  ],
                  __('Uptime', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_uptime',
                     'value' => $this->fields["import_uptime"],
                     'title' => nl2br(__('Uptime Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/uptime) must be installed', 'ocsinventoryng')),
                  ],
                  __('Windows Update State', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_winupdatestate',
                     'value' => $this->fields["import_winupdatestate"],
                     'title' => nl2br(__('Winupdate Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/winupdate) must be installed', 'ocsinventoryng')),
                  ],
                  __('Teamviewer', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_teamviewer',
                     'value' => $this->fields["import_teamviewer"],
                     'title' => nl2br(__('Teamviewer Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/teamviewer) must be installed', 'ocsinventoryng')),
                  ],
                  __('Proxy Settings', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_proxysetting',
                     'value' => $this->fields["import_proxysetting"],
                     'title' => nl2br(__('Navigator Proxy Setting Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/navigatorproxysetting) must be installed', 'ocsinventoryng')),
                  ],
                  __('Windows Users', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_winusers',
                     'value' => $this->fields["import_winusers"],
                     'title' => nl2br(__('Winusers Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/winusers) must be installed', 'ocsinventoryng')),
                  ],
                  __('OS Informations', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_osinstall',
                     'value' => $this->fields["import_osinstall"],
                     'title' => nl2br(__('OSInstall Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/osinstall) must be installed', 'ocsinventoryng')),
                  ],
                  __('Network shares', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_networkshare',
                     'value' => $this->fields["import_networkshare"],
                     'title' => nl2br(__('Networkshare Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/networkshare) must be installed', 'ocsinventoryng')),
                  ],
                  __('Service', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_service',
                     'value' => $this->fields["import_service"],
                     'title' => nl2br(__('Service Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/services) must be installed', 'ocsinventoryng')),
                  ],
                  __('Running Process', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_runningprocess',
                     'value' => $this->fields["import_runningprocess"],
                     'title' => nl2br(__('Running Process Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/runningProcess) must be installed', 'ocsinventoryng')),
                  ],
                  __('Customapp', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_customapp',
                     'value' => $this->fields["import_customapp"],
                     'title' => nl2br(__('Customapp Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/customapp) must be installed', 'ocsinventoryng')),
                  ],
                  __('Bitlocker', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'import_bitlocker',
                     'value' => $this->fields["import_bitlocker"],
                     'title' => nl2br(__('Bitlocker Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/bitlocker) must be installed', 'ocsinventoryng')),
                  ]
               ]
            ],
            __('OCSNG administrative information', 'ocsinventoryng') => [
               'visible' => true,
               'inputs' => [
                  __('Inventory number') => [
                     'type' => 'select',
                     'name' => 'import_otherserial',
                     'values' => $oserial,
                     'value' => $values['serialValue'],
                  ],
                  __('Location') => [
                     'type' => 'select',
                     'name' => 'import_location',
                     'values' => $opt,
                     'value' => $values['locationValue'],
                  ],
                  __('Group') => [
                     'type' => 'select',
                     'name' => 'import_group',
                     'values' => $opt,
                     'value' => $values['groupValue'],
                  ],
                  __('Alternate username number') => [
                     'type' => 'select',
                     'name' => 'import_contact_num',
                     'values' => $opt,
                     'value' => $values['contactNumValue'],
                  ],
                  __('Network') => [
                     'type' => 'select',
                     'name' => 'import_network',
                     'values' => $opt,
                     'value' => $values['networkValue'],
                  ],
                  __('Startup date') => [
                     'type' => 'select',
                     'name' => 'import_use_date',
                     'values' => $opt,
                     'value' => $values['useDateValue'],
                  ],
               ]
            ]
         ]
      ];
      renderTwigForm($form);
   }

   static function getHistoryValues() {

      $values = [__('None'),
                 __('Installation / Update / Uninstallation', 'ocsinventoryng'),
                 __('Installation / Update', 'ocsinventoryng'),
                 __('Uninstallation', 'ocsinventoryng')];

      return $values;
   }

   function showHistoryDropdown($name) {

      $value  = $this->fields[$name];
      $values = self::getHistoryValues();
      return Dropdown::showFromArray($name, $values, ['value' => $value]);

   }


   /**
    * Print ocs history form
    *
    * @param $ID Integer : Id of the ocs config
    *
    *
    * @return bool
    */
   function ocsHistoryConfig($ID) {

      if (!Session::haveRight("plugin_ocsinventoryng", READ)) {
         return false;
      }
      $this->getFromDB($ID);

      $form = [
         'action' => Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer"),
         'attributes' => [
            'name' => 'historyconfig',
            'id' => 'historyconfig',
            'method' => 'post'
         ],
         'buttons' => [
            Session::haveRight("plugin_ocsinventoryng", UPDATE) ? [
               'name' => 'update',
               'value' => __('Save'),
               'type' => 'submit',
               'class' => 'btn btn-secondary',
            ] : []
         ],
         'content' => [
            '' => [
               'visible' => 'true',
               'inputs' => [
                  [
                     'type' => 'hidden',
                     'name' => 'id',
                     'value' => $ID
                  ],
                  __('All') => [
                     'type' => 'select',
                     'name' => 'init_all',
                     'values' => [-1 => Dropdown::EMPTY_VALUE, 0 => __('No'), 1 => __('Yes')],
                     'value' => -1,
                     'col_lg' => 12,
                     'col_md' => 12,
                     'hooks' => [
                        'change' => <<<JS
                           const value = this.value;
                           if(value != -1) {
                              const selects = $("form[id='historyconfig'] select");
                              $.each(selects, function(index, select){
                                 if (select.name != "init_all") {
                                    $(select).val(value);
                                 }
                              });
                              const checkboxes = $("form[id='historyconfig'] input[type='checkbox']");
                              $.each(checkboxes, function(index, checkbox){
                                 if (checkbox.name != "init_all") {
                                    $(checkbox).prop('checked', value == 1)
                                 }
                              });
                           }
                        JS
                     ]
                  ],
               ]
            ],
            __('General history', 'ocsinventoryng') => [
               'visible' => 'true',
               'inputs' => [
                  __('Do history', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'dohistory',
                     'value' => $this->fields["dohistory"],
                  ],
                  __('System history', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'history_hardware',
                     'value' => $this->fields["history_hardware"],
                     'title' => nl2br(__('Depends on Bios import', 'ocsinventoryng')),
                  ],
                  __('Bios history', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'history_bios',
                     'value' => $this->fields["history_bios"],
                     'title' => nl2br(__('Depends on Bios import', 'ocsinventoryng')),
                  ],
                  __('Devices history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_devices',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_devices"],
                  ],
                  __('Volumes history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_drives',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_drives"],
                  ],
                  __('Network history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_network',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_network"],
                  ],
                  __('Monitor connection history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_monitor',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_monitor"],
                  ],
                  __('Printer connection history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_printer',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_printer"],
                  ],
                  __('Peripheral connection history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_peripheral',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_peripheral"],
                  ],
                  __('Software connection history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_software',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_software"],
                  ],
                  __('Virtual machines history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_vm',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_vm"],
                  ],
                  __('Administrative infos history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_admininfos',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_admininfos"],
                  ],
                  __('Plugins history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_plugins',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_plugins"],
                  ],
                  __('OS history', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'history_os',
                     'values' => self::getHistoryValues(),
                     'value' => $this->fields["history_os"],
                  ],
               ],
            ]
         ]
      ];
      renderTwigForm($form);
   }

   /**
    * @param $ID
    *
    * @internal param $withtemplate (default '')
    * @internal param $templateid (default '')
    */
   function ocsFormImportOptions($ID) {

      $this->getFromDB($ID);

      $actions[0] = Dropdown::EMPTY_VALUE;
      $actions[1] = __('Put in trashbin');
      $dbu        = new DbUtils();
      foreach ($dbu->getAllDataFromTable('glpi_states') as $state) {
         $actions['STATE_' . $state['id']] = sprintf(__('Change to state %s', 'ocsinventoryng'), $state['name']);
      }

      $form = [
         'actions' => Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer"),
         'buttons' => [
            Session::haveRight("plugin_ocsinventoryng", UPDATE) ? [
               'name' => 'update',
               'value' => __('Save'),
               'type' => 'submit',
               'class' => 'btn btn-secondary',
            ] : []
         ],
         'content' => [
            '' => [
               'visible' => true,
               'inputs' => [
                  [
                     'type' => 'hidden',
                     'name' => 'id',
                     'value' => $ID
                  ],
                  __('Web address of the OCSNG console', 'ocsinventoryng') => [
                     'type' => 'text',
                     'name' => 'ocs_url',
                     'value' => $this->fields["ocs_url"],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ]
               ]
            ],
            __('Import options', 'ocsinventoryng') => [
               'visible' => true,
               'inputs' => [
                  __('Limit the import to the following tags (separator $, nothing for all)', 'ocsinventoryng') => [
                     'type' => 'text',
                     'name' => 'tag_limit',
                     'value' => $this->fields["tag_limit"],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
                  __('Exclude the following tags (separator $, nothing for all)', 'ocsinventoryng') => [
                     'type' => 'text',
                     'name' => 'tag_exclude',
                     'value' => $this->fields["tag_exclude"],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
                  __('Behavior when disconnecting', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'deconnection_behavior',
                     'values' => [
                        '' => __('Preserve link', 'ocsinventoryng'),
                        "trash" => __('Put the link in dustbin and add a lock', 'ocsinventoryng'),
                        "delete" => __('Delete  the link permanently', 'ocsinventoryng')
                     ],
                     'value' => $this->fields["deconnection_behavior"],
                     'col_lg' => 12,
                     'col_md' => 12,
                     'title' => __("Define the action to do on link with other objects when computer is disconnecting from them", 'ocsinventoryng'),
                  ],
                  __('Use the OCSNG software dictionary', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'use_soft_dict',
                     'values' => [0 => __('No'), 1 => __('Yes')],
                     'value' => $this->fields["use_soft_dict"],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
                  __('Number of items to synchronize via the automatic OCSNG action', 'ocsinventoryng') => [
                     'type' => 'number',
                     'name' => 'cron_sync_number',
                     'value' => $this->fields["cron_sync_number"],
                     'min' => 1,
                     'title' => "0 = " . __('None'),
                     'col_lg' => 12,
                     'col_md' => 12,
                     'title' => __("The automatic task ocsng is launched only if server is not in expert mode", 'ocsinventoryng') . '. '
                        . __("The automatic task ocsng only synchronize existant computers, it doesn't import new computers", 'ocsinventoryng') . '. '
                        . __("If you want to import new computers, disable this parameter, change to expert mode and use script from system", 'ocsinventoryng') . '.',
                  ],
                  __('Behavior to the deletion of a computer in OCSNG', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'deleted_behavior',
                     'values' => $actions,
                     'value' => $this->fields["deleted_behavior"],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
               ]
            ]
         ]
      ];
      renderTwigForm($form);
   }

   /**
    *
    */
   function post_getEmpty() {
      $this->fields['ocs_db_host'] = "localhost";
      $this->fields['ocs_db_name'] = "ocsweb";
      $this->fields['ocs_db_user'] = "ocsuser";
      $this->fields['ocs_db_utf8'] = 1;
      $this->fields['is_active']   = 1;
      $this->fields['use_locks']   = 1;
   }

   /**
    * Print simple ocs config form (database part)
    *
    * @param $ID        integer : Id of the ocs config
    * @param $options   array
    *     - target form target
    *
    * @return void (display)
    */
   function showForm($ID, $options = []) {
      $form = [
         'action' => $this->getFormURL(),
         'buttons' => [
            ($this->canUpdateItem() ? [
               'type' => 'submit',
               'name' => $this->isNewID($ID) ? 'add' : 'update',
               'value' => $this->isNewID($ID) ? __('Add') : __('Update'),
               'class' => 'btn btn-secondary'
            ] : []),
            (!$this->isNewID($ID) && self::canPurge() ? [
               'type' => 'submit',
               'name' => 'purge',
               'value' => __('Delete permanently'),
               'class' => 'btn btn-danger'
            ] : []),
         ],
         'content' => [
            $this->getTypeName() => [
               'visible' => true,
               'inputs' => [
                  [
                     'name' => 'id',
                     'type' => 'hidden',
                     'value' => $ID
                  ],
                  __('Connection type', 'ocsinventoryng') => [
                     'type' => 'select',
                     'name' => 'conn_type',
                     'values' => [
                        0 => __('Database', 'ocsinventoryng'),
                        //1 => __('Webservice (SOAP)', 'ocsinventoryng'),               
                     ],
                     'value' => $this->fields['conn_type'],
                  ],
                  __("Active") => [
                     'type' => 'checkbox',
                     'name' => 'is_active',
                     'value' => $this->isActive()
                  ],
                  __("Name") => [
                     'type' => 'text',
                     'name' => 'name',
                     'value' => $this->fields['name']
                  ],
                  _n("Version", "Versions", 1) => $ID ? [
                     'content' => $this->fields["ocs_version"]
                  ] : [],
                  __("Checksum") => $ID ? [
                     'content' => $this->fields["checksum"] . <<<HTML
                     <a class="btn btn-secondary" onclick="submitGetLink('{$this->getFormURL()}',
                        {'force_checksum': 'force_checksum', 'id': '{$ID}', '_glpi_csrf_token': '{$_SESSION['_glpi_csrf_token']}', '_glpi_simple_form': '1'})">
                        <i class="fas fa-redo-alt"></i>
                     </a>
                     HTML,
                  ] : [],
                  __("Host", "ocsinventoryng") => [
                     'type' => 'text',
                     'name' => 'ocs_db_host',
                     'value' => $this->fields["ocs_db_host"],
                     'title' => nl2br(__('Like http://127.0.0.1 for SOAP method', 'ocsinventoryng'))
                  ],
                  __("Synchronisation method", "ocsinventoryng") => [
                     'type' => 'select',
                     'name' => 'use_massimport',
                     'values' => [
                        0 => __("Standard (allow manual actions)", "ocsinventoryng"),
                        1 => __("Expert (Fully automatic, for large configuration)", "ocsinventoryng")
                     ],
                     'value' => $this->fields['use_massimport']
                  ],
                  __("Database") => ($this->fields["conn_type"] == self::CONN_TYPE_SOAP) ? [] : [
                     'type' => 'text',
                     'name' => 'ocs_db_name',
                     'value' => $this->fields["ocs_db_name"]
                  ],
                  __("Database in UTF8", "ocsinventoryng") => ($this->fields["conn_type"] == self::CONN_TYPE_SOAP) ? [] : [
                     'type' => 'checkbox',
                     'name' => 'ocs_db_utf8',
                     'value' => $this->fields["ocs_db_utf8"]
                  ],
                  _n("User", "Users", 1) => ($this->fields["conn_type"] == self::CONN_TYPE_SOAP) ? [] : [
                     'type' => 'text',
                     'name' => 'ocs_db_user',
                     'value' => $this->fields["ocs_db_user"]
                  ],
                  __("Password") => ($this->fields["conn_type"] == self::CONN_TYPE_SOAP) ? [] : [
                     'type' => 'password',
                     'name' => 'ocs_db_passwd',
                  ],
                  __('Use automatic action for clean old agents & drop from OCSNG software', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'use_cleancron',
                     'value' => $this->fields["use_cleancron"],
                     'hooks' => [
                        'change' => <<<JS
                           const checked = $(this).is(':checked');
                           $('#action_cleancron').prop('disabled', !checked);
                           $('#cleancron_nb_days').prop('disabled', !checked);
                        JS,
                     ],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
                  __("Action") => [
                     'type' => 'select',
                     'name' => 'action_cleancron',
                     'id' => 'action_cleancron',
                     'values' => self::getValuesActionCron(),
                     'value' => $this->fields["action_cleancron"],
                     $this->fields["use_cleancron"] ? '' : 'disabled' => 'disabled',
                  ],
                  __('Number of days without inventory for cleaning', 'ocsinventoryng') => [
                     'type' => 'number',
                     'id' => 'cleancron_nb_days',
                     'name' => 'cleancron_nb_days',
                     'value' => $this->fields["cleancron_nb_days"],
                     'min' => 1,
                     'max' => 365,
                     $this->fields["use_cleancron"] ? '' : 'disabled' => 'disabled'
                  ],
                  __('Use automatic action restore deleted computer', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'use_restorationcron',
                     'value' => $this->fields["use_restorationcron"],
                     'hooks' => [
                        'change' => <<<JS
                           const checked = $(this).is(':checked');
                           $('#delay_restorationcron').prop('disabled', !checked);
                        JS,
                     ],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
                  __("Number of days for the restoration of computers from the date of last inventory", "ocsinventoryng") => [
                     'type' => 'number',
                     'id' => 'delay_restorationcron',
                     'name' => 'delay_restorationcron',
                     'value' => $this->fields["delay_restorationcron"],
                     'min' => 1,
                     'max' => 365,
                     $this->fields["use_restorationcron"] ? '' : 'disabled' => 'disabled'
                  ],
                  __('Use automatic action to check entity assignment rules', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'use_checkruleimportentity',
                     'value' => $this->fields["use_checkruleimportentity"],
                     'title' => __('Use automatic action to check and send a notification for machines that no longer respond the entity and location assignment rules', 'ocsinventoryng'),
                  ],
                  __('Use automatic locks', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'use_locks',
                     'value' => $this->fields["use_locks"],
                  ],
                  __("Comments") => [
                     'type' => 'textarea',
                     'name' => 'comment',
                     'value' => $this->fields["comment"],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
               ]
            ],
         ]
      ];
      renderTwigForm($form);
   }

   /**
    * check is one of the servers use_mass_import sync mode
    *
    * @return boolean
    * */
   static function useMassImport() {
      $dbu = new DbUtils();
      return $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', ["use_massimport" => 1]);
   }

   /**
    * @param $ID
    * */
   function showDBConnectionStatus($ID) {

      $out = "<br><div class='center'>\n";
      $out .= "<table class='tab_cadre_fixe'>";
      $out .= "<tr><th>" . __('Connecting to the database', 'ocsinventoryng') . "</th></tr>\n";
      $out .= "<tr class='tab_bg_2'>";
      if ($ID != -1) {
         try {
            if (!self::checkOCSconnection($ID)) {
               $out .= "<td class='center red'>" . __('Connection to the database failed', 'ocsinventoryng');
            } else if (!self::checkVersion($ID)) {
               $out .= "<td class='center red'>" . __('Invalid OCSNG Version: RC3 is required', 'ocsinventoryng');
            } else if (!self::checkTraceDeleted($ID)) {
               $out .= "<td class='center red'>" . __('Invalid OCSNG configuration (TRACE_DELETED must be active)', 'ocsinventoryng');
            } else {
               $out .= "<td class='center'>" . __('Connection to database successful', 'ocsinventoryng');
               $out .= "</td></tr>\n<tr class='tab_bg_2'>" .
                       "<td class='center'>" . __('Valid OCSNG configuration and version', 'ocsinventoryng');
            }
         } catch (Exception $e) {
            $out .= "<td class='center red'>" . __('Connection to the database failed', 'ocsinventoryng');
         }
      }
      $out .= "</td></tr>\n";
      $out .= "</table></div>";
      echo $out;
   }

   /**
    * @param datas $input
    *
    * @return datas
    */
   function prepareInputForUpdate($input) {

      $adm = new PluginOcsinventoryngOcsAdminInfosLink();
      $adm->updateAdminInfo($input);
      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
         $input["ocs_db_passwd"] = Toolbox::sodiumEncrypt(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }

      if (isset($input["_blank_passwd"]) && $input["_blank_passwd"]) {
         $input['ocs_db_passwd'] = '';
      }

      return $input;
   }

   /**
    *
    */
   function pre_updateInDB() {

      // Update checksum
      $checksum = 0;
      if (//$this->fields["import_device_processor"] ||
         $this->fields["import_general_contact"]
         || $this->fields["import_general_comment"]
         || $this->fields["import_general_domain"]
         || $this->fields["import_general_os"]
         || $this->fields["import_general_name"]) {

         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::HARDWARE_FL);
      }
      if ($this->fields["import_device_bios"]) {
         $checksum |= $checksum + pow(2, PluginOcsinventoryngOcsProcess::BIOS_FL);
      }
      if ($this->fields["import_device_memory"]) {
         $checksum |= $checksum + pow(2, PluginOcsinventoryngOcsProcess::MEMORIES_FL);
      }
      if ($this->fields["import_registry"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::REGISTRY_FL);
      }
      if ($this->fields["import_device_controller"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::CONTROLLERS_FL);
      }
      if ($this->fields["import_device_slot"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::SLOTS_FL);
      }
      if ($this->fields["import_monitor"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::MONITORS_FL);
      }
      if ($this->fields["import_device_port"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::PORTS_FL);
      }
      if ($this->fields["import_device_hdd"] || $this->fields["import_device_drive"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::STORAGES_FL);
      }
      if ($this->fields["import_disk"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::DRIVES_FL);
      }
      if ($this->fields["import_periph"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::INPUTS_FL);
      }
      if ($this->fields["import_device_modem"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::MODEMS_FL);
      }
      if ($this->fields["import_ip"]
          || $this->fields["import_device_iface"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::NETWORKS_FL);
      }
      if ($this->fields["import_printer"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::PRINTERS_FL);
      }
      if ($this->fields["import_device_sound"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::SOUNDS_FL);
      }
      if ($this->fields["import_device_gfxcard"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::VIDEOS_FL);
      }
      if ($this->fields["import_software"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::SOFTWARES_FL);
      }
      if ($this->fields["import_vms"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::VIRTUALMACHINES_FL);
      }
      if ($this->fields["import_device_processor"]) {
         $checksum |= pow(2, PluginOcsinventoryngOcsProcess::CPUS_FL);
      }

      $this->updates[]          = "checksum";
      $this->fields["checksum"] = $checksum;
   }

   /**
    * @param datas $input
    *
    * @return bool|datas
    */
   function prepareInputForAdd($input) {
      global $DB;

      // Check if server config does not exists
      $query  = "SELECT *
                FROM `" . $this->getTable() . "`
                WHERE `name` = '" . $input['name'] . "';";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         Session::addMessageAfterRedirect(__('Unable to add. The OCSNG server already exists.', 'ocsinventoryng'), false, ERROR);
         return false;
      }

      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
         $input["ocs_db_passwd"] = Toolbox::sodiumEncrypt(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }
      return $input;
   }

   /**
    *
    */
   function post_addItem() {
      global $DB;

      $query = "INSERT INTO  `glpi_plugin_ocsinventoryng_ocsservers_profiles` (`id` ,`plugin_ocsinventoryng_ocsservers_id` , `profiles_id`)
                 VALUES (NULL ,  '" . $this->fields['id'] . "',  '" . $_SESSION["glpiactiveprofile"]['id'] . "');";
      $DB->query($query);
   }

   /**
    *
    */
   function cleanDBonPurge() {

      $link = new PluginOcsinventoryngOcslink();
      $link->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      $admin = new PluginOcsinventoryngOcsAdminInfosLink();
      $admin->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      $server = new PluginOcsinventoryngServer();
      $server->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      $detail = new PluginOcsinventoryngDetail();
      $detail->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      $notimported = new PluginOcsinventoryngNotimportedcomputer();
      $notimported->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      $iplink = new PluginOcsinventoryngIpdiscoverOcslink();
      $iplink->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      $snmplink = new PluginOcsinventoryngSnmpOcslink();
      $snmplink->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      $prof = new PluginOcsinventoryngOcsserver_Profile();
      $prof->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      $thread = new PluginOcsinventoryngThread();
      $thread->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

      unset($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);

      // ocsservers_id for RuleImportComputer, OCS_SERVER for RuleImportEntity
      Rule::cleanForItemCriteria($this);
      Rule::cleanForItemCriteria($this, 'OCS_SERVER');
   }


   /**
    * Get the ocs server id of a machine, by giving the machine id
    *
    * @param $ID the machine ID
    *
    * return the ocs server id of the machine
    *
    * @return int
    */
   static function getServerByComputerID($ID) {
      global $DB;

      $sql    = "SELECT `plugin_ocsinventoryng_ocsservers_id`
              FROM `glpi_plugin_ocsinventoryng_ocslinks`
              WHERE `computers_id` = $ID";
      $result = $DB->query($sql);
      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetchArray($result);
         return $datas["plugin_ocsinventoryng_ocsservers_id"];
      }
      return -1;
   }

   /**
    * Get the ocs id of a machine, by giving the machine id
    *
    * @param $ID the machine ID
    *
    * return the ocs server id of the machine
    *
    * @param $ocsservers_id
    *
    * @return int
    * @throws \GlpitestSQLError
    */
   static function getOCSIDByComputerID($ID, $ocsservers_id) {
      global $DB;

      $sql    = "SELECT `ocsid`
              FROM `glpi_plugin_ocsinventoryng_ocslinks`
              WHERE `computers_id` = '" . $ID . "' AND `plugin_ocsinventoryng_ocsservers_id` = $ocsservers_id";
      $result = $DB->query($sql);
      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetchArray($result);
         return $datas["ocsid"];
      }
      return -1;
   }

   /**
    * Get a random plugin_ocsinventoryng_ocsservers_id
    * use for standard sync server selection
    *
    * return an ocs server id
    * */
   static function getRandomServerID() {
      global $DB;

      $sql    = "SELECT `id`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              WHERE `is_active` = 1
                AND `use_massimport` = 0
              ORDER BY RAND()
              LIMIT 1";
      $result = $DB->query($sql);

      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetchArray($result);
         return $datas["id"];
      }
      return -1;
   }

   /**
    * @return int|Value
    */
   static function getFirstServer() {
      global $DB;

      $query   = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                ON `glpi_plugin_ocsinventoryng_ocsservers`.`id` = `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id`
                WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`= 1
                ORDER BY `glpi_plugin_ocsinventoryng_ocsservers`.`id` ASC LIMIT 1 ";
      $results = $DB->query($query);
      if ($DB->numrows($results) > 0) {
         return $DB->result($results, 0, 'id');
      }
      return -1;
   }

   /**
    * Get OCSNG mode configuration
    *
    * Get all config of the OCSNG mode
    *
    * @param $id int : ID of the OCS config (default value 1)
    *
    * @return int|null|string[] of $confVar fields or false if unfound.
    * @throws \GlpitestSQLError
    */
   static function getConfig($id) {
      global $DB;

      $data = 0;

      if (!empty($id)) {
         $query  = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocsservers`
                WHERE `id` = $id";
         $result = $DB->query($query);

         if ($result) {
            $data = $DB->fetchAssoc($result);
         }
      }

      return $data;
   }


   /**
    * @param $ocs_config
    * @param $itemtype
    *
    * @return mixed
    */
   static function getDevicesManagementMode($ocs_config, $itemtype) {

      switch ($itemtype) {
         case 'Monitor':
            return $ocs_config["import_monitor"];

         case 'Printer':
            return $ocs_config["import_printer"];

         case 'Peripheral':
            return $ocs_config["import_periph"];
      }
   }


   /**
    * Check if OCS connection is still valid
    * If not, then establish a new connection on the good server
    *
    * @param $serverId
    *
    * @return bool
    * @internal param the $plugin_ocsinventoryng_ocsservers_id ocs server id
    *
    */
   static function checkOCSconnection($serverId) {
      return self::getDBocs($serverId)->checkConnection();
   }

   /**
    * Get a connection to the OCS server
    *
    * @param $serverId the ocs server id
    *
    * @return PluginOcsinventoryngOcsClient the ocs client (database or soap)
    */
   static function getDBocs($serverId) {

      if ($serverId) {
         $config = self::getConfig($serverId);

         if ($config['conn_type'] == self::CONN_TYPE_DB) {
            return new PluginOcsinventoryngOcsDbClient(
               $serverId, $config['ocs_db_host'], $config['ocs_db_user'],
               Toolbox::sodiumDecrypt($config['ocs_db_passwd']), $config['ocs_db_name']
            );
         } else {
            return new PluginOcsinventoryngOcsSoapClient(
               $serverId, $config['ocs_db_host'], $config['ocs_db_user'],
               Toolbox::sodiumDecrypt($config['ocs_db_passwd'])
            );
         }
      }
   }

   /**
    * get true if the server is active
    *
    * @param $serverID
    *
    * @return bool
    */
   static function serverIsActive($serverID) {
      $ocsserver = new self();
      if ($ocsserver->getFromDB($serverID)) {
         return $ocsserver->isActive();
      }
      return false;
   }


   /**
    * @param $cfg_ocs
    *
    * @return string
    */
   static function getTagLimit($cfg_ocs) {

      $WHERE = "";
      if (!empty($cfg_ocs["tag_limit"])) {
         $splitter = explode("$", trim($cfg_ocs["tag_limit"]));
         if (count($splitter)) {
            $WHERE = " `accountinfo`.`TAG` = '" . $splitter[0] . "' ";
            for ($i = 1; $i < count($splitter); $i++) {
               $WHERE .= " OR `accountinfo`.`TAG` = '" . $splitter[$i] . "' ";
            }
         }
      }

      if (!empty($cfg_ocs["tag_exclude"])) {
         $splitter = explode("$", $cfg_ocs["tag_exclude"]);
         if (count($splitter)) {
            if (!empty($WHERE)) {
               $WHERE .= " AND ";
            }
            $WHERE .= " `accountinfo`.`TAG` <> '" . $splitter[0] . "' ";
            for ($i = 1; $i < count($splitter); $i++) {
               $WHERE .= " AND `accountinfo`.`TAG` <> '" . $splitter[$i] . "' ";
            }
         }
      }

      return $WHERE;
   }


   /**
    * @param $ID
    *
    * @return bool
    */
   public static function checkVersion($ID) {
      $client  = self::getDBocs($ID);
      $version = $client->getTextConfig('GUI_VERSION');

      if ($version) {
         $server = new self();
         $server->update([
                            'id'          => $ID,
                            'ocs_version' => $version
                         ]);
      }
      return true;
      if (!$version || ($version < self::OCS_VERSION_LIMIT && strpos($version, '2.0') !== 0)) { // hack for 2.0 RC
         return false;
      } else {
         return true;
      }
   }


   /**
    * @param $ID
    *
    * @return int
    */
   public static function checkTraceDeleted($ID) {
      $client = self::getDBocs($ID);
      return $client->getIntConfig('TRACE_DELETED');
   }


   /**
    * Displays a list of computers that can be cleaned.
    *
    * @param $plugin_ocsinventoryng_ocsservers_id int : id of ocs server in GLPI
    * @param $check string : parameter for HTML input checkbox
    * @param $start int : parameter for Html::printPager method
    *
    *
    * @return bool
    */
   static function showComputersToClean($show_params) {
      global $DB, $CFG_GLPI;

      $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];
      $check                               = $show_params["check"];
      $start                               = $show_params["start"];

      if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
         return false;
      }

      if (!Session::haveRight("plugin_ocsinventoryng_clean", READ)) {
         return false;
      }
      $canedit = Session::haveRight("plugin_ocsinventoryng_clean", UPDATE);

      // ocslinks ocsid
      $query  =
         "SELECT `ocsid` " .
         "FROM `glpi_plugin_ocsinventoryng_ocslinks` " .
         "WHERE `plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id" .
         (new DbUtils())->getEntitiesRestrictRequest(" AND", "glpi_plugin_ocsinventoryng_ocslinks");
      $result = $DB->query($query);
      $ocsids = [];
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetchAssoc($result)) {
            $ocsids[] = $data['ocsid'];
         }
      }

      // Select unexisting OCS hardware
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $computerOptions = [
         'COMPLETE' => '0',
         'FILTER'   => [
            'IDS' => $ocsids,
         ]
      ];

      $computers = $ocsClient->getComputers($computerOptions);

      if (isset($computers['COMPUTERS'])
          && count($computers['COMPUTERS']) > 0) {
         $hardware = $computers['COMPUTERS'];
         unset($computers);
      } else {
         $hardware = [];
      }

      //      $query  = "SELECT `ocsid`
      //                FROM `glpi_plugin_ocsinventoryng_ocslinks`
      //                WHERE `plugin_ocsinventoryng_ocsservers_id`
      //                           = $plugin_ocsinventoryng_ocsservers_id";
      //      $result = $DB->query($query);

      $ocs_missing = [];
      //      if ($DB->numrows($result) > 0) {
      //         while ($data = $DB->fetchArray($result)) {
      //            $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));
      //            if (!isset($hardware[$data["ocsid"]])) {
      //               $ocs_missing[$data["ocsid"]] = $data["ocsid"];
      //            }
      //         }
      //      }

      foreach ($ocsids as $id) {
         if (!isset($hardware[$id])) {
            $ocs_missing[$id] = $id;
         }
      }


      //Select unexisting computers
      $query_glpi = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`entities_id` AS entities_id,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`ocs_deviceid` AS ocs_deviceid,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` AS last_update,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`id`,
                            `glpi_computers`.`name` AS name
                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                     LEFT JOIN `glpi_computers`
                           ON `glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                     WHERE ((`glpi_computers`.`id` IS NULL
                             AND `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                    = $plugin_ocsinventoryng_ocsservers_id)";

      if (count($ocs_missing)) {
         $query_glpi .= " OR `ocsid` IN ('" . implode("','", $ocs_missing) . "')";
      }
      $dbu        = new DbUtils();
      $query_glpi .= ") " . $dbu->getEntitiesRestrictRequest(" AND", "glpi_plugin_ocsinventoryng_ocslinks");

      $result_glpi = $DB->query($query_glpi);

      // fetch all links missing between glpi and OCS
      $already_linked = [];
      if ($DB->numrows($result_glpi) > 0) {
         while ($data = $DB->fetchAssoc($result_glpi)) {
            $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

            $already_linked[$data["ocsid"]]["entities_id"]  = $data["entities_id"];
            $already_linked[$data["ocsid"]]["ocs_deviceid"] = $data["ocs_deviceid"];
            $already_linked[$data["ocsid"]]["date"]         = $data["last_update"];
            $already_linked[$data["ocsid"]]["id"]           = $data["id"];
            $already_linked[$data["ocsid"]]["in_ocs"]       = isset($hardware[$data["ocsid"]]);

            if ($data["name"] == null) {
               $already_linked[$data["ocsid"]]["in_glpi"] = 0;
            } else {
               $already_linked[$data["ocsid"]]["in_glpi"] = 1;
            }
         }
      }

      echo "<div class='center'>";
      echo "<h2>" . __('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "</h2>";

      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsng.clean.php';
      if (($numrows = count($already_linked)) > 0) {
         $parameters = "check=$check";
         Html::printPager($start, $numrows, $target, $parameters);

         // delete end
         array_splice($already_linked, $start + $_SESSION['glpilist_limit']);

         // delete begin
         if ($start > 0) {
            array_splice($already_linked, 0, $start);
         }

         echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $target . "'>";
         if ($canedit) {
            self::checkBox($target);
         }
         echo "<table class='tab_cadre'>";
         echo "<tr><th>" . __('Item') . "</th><th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
         echo "<th>" . __('Existing in GLPI', 'ocsinventoryng') . "</th>";
         echo "<th>" . __('Existing in OCSNG', 'ocsinventoryng') . "</th>";
         if (Session::isMultiEntitiesMode()) {
            echo "<th>" . __('Entity') . "</th>";
         }
         if ($canedit) {
            echo "<th>&nbsp;</th>";
         }
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
         if ($canedit) {
            echo Html::submit(_sx('button', 'Clean'), ['name' => 'clean_ok']);
         }
         echo "</td></tr>\n";

         foreach ($already_linked as $ID => $tab) {
            echo "<tr class='tab_bg_2 center'>";
            echo "<td>" . $tab["ocs_deviceid"] . "</td>\n";
            echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
            echo "<td>" . Dropdown::getYesNo($tab["in_glpi"]) . "</td>\n";
            echo "<td>" . Dropdown::getYesNo($tab["in_ocs"]) . "</td>\n";
            if (Session::isMultiEntitiesMode()) {
               echo "<td>" . Dropdown::getDropdownName('glpi_entities', $tab['entities_id']) . "</td>\n";
            }
            if ($canedit) {
               echo "<td><input type='checkbox' name='toclean[" . $tab["id"] . "]' " .
                    (($check == "all") ? "checked" : "") . "></td>";
            }
            echo "</tr>\n";
         }

         echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
         if ($canedit) {
            echo Html::submit(_sx('button', 'Clean'), ['name' => 'clean_ok']);
         }
         echo "</td></tr>";
         echo "</table>\n";
         Html::closeForm();
         Html::printPager($start, $numrows, $target, $parameters);
      } else {
         echo "<div class='center b '>" . __('No item to clean', 'ocsinventoryng') . "</div>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
         echo __('Back');
         echo "</a>";
      }
      echo "</div>";
   }


   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $check
    * @param $start
    *
    * @return bool|void
    
   static function showComputersToSynchronize($show_params) {
      global $DB, $CFG_GLPI;

      $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];
      $check                               = $show_params["check"];
      $start                               = $show_params["start"];

      if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
         return false;
      }
      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)
          && !Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
         return false;
      }

      $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);

      // Get linked computer ids in GLPI
      //      $already_linked_query  = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid
      //                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
      //                               WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
      //                                            = $plugin_ocsinventoryng_ocsservers_id" .
      //                               (new DbUtils())->getEntitiesRestrictRequest(" AND", "glpi_plugin_ocsinventoryng_ocslinks");
      //      $already_linked_result = $DB->query($already_linked_query);

      //      if ($DB->numrows($already_linked_result) == 0) {
      //         echo "<div class='center b'>" . __('No new computer to be updated', 'ocsinventoryng');
      //         echo "<br><a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
      //         echo __('Back');
      //         echo "</a>";
      //         echo "</div>";
      //         return;
      //      }

      //      $already_linked_ids = [];
      //      while ($data = $DB->fetchAssoc($already_linked_result)) {
      //         $already_linked_ids [] = $data['ocsid'];
      //      }

      $server = new PluginOcsinventoryngServer();
      $server->getFromDBbyOcsServer($plugin_ocsinventoryng_ocsservers_id);
      $max_date = $server->fields["max_glpidate"];

      // Fetch linked computers from ocs
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $ocsResult = $ocsClient->getComputers([
                                               'OFFSET'      => $start,
                                               'MAX_RECORDS' => $_SESSION['glpilist_limit'],
                                               'ORDER'       => 'LASTDATE',
                                               'COMPLETE'    => '0',
                                               'FILTER'      => [
                                                  //                                                  'IDS'               => $already_linked_ids,
                                                  'CHECKSUM'          => $cfg_ocs["checksum"],
                                                  //'INVENTORIED_BEFORE' => 'NOW()',
//                                                  'INVENTORIED_SINCE' => $max_date,
                                               ]
                                            ]);

      if (isset($ocsResult['COMPUTERS'])) {
         if (count($ocsResult['COMPUTERS']) > 0) {
            // Get all ids of the returned computers
            $ocs_computer_ids = [];
            $hardware         = [];
            //            $computers        = array_slice($ocsResult['COMPUTERS'], $start, $_SESSION['glpilist_limit']);
            $computers = $ocsResult['COMPUTERS'];
            foreach ($computers as $computer) {
               $ID                  = $computer['META']['ID'];
               $ocs_computer_ids [] = $ID;

               $query_glpi  = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` AS last_update,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`last_ocs_update` AS last_ocs_update,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` AS computers_id,
                                  `glpi_computers`.`serial` AS serial,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid,
                                  `glpi_computers`.`name` AS name,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update`,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`id`
                           FROM `glpi_plugin_ocsinventoryng_ocslinks`
                           LEFT JOIN `glpi_computers` ON (`glpi_computers`.`id`= `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`)
                           WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                       = $plugin_ocsinventoryng_ocsservers_id
                                  AND `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` = $ID
                           ORDER BY `glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update` DESC,
                                    `last_update`,
                                    `name`";
               $result_glpi = $DB->query($query_glpi);
               if ($DB->numrows($result_glpi) > 0) {
                  while ($data = $DB->fetchAssoc($result_glpi)) {
                     if (strtotime($computer['META']["LASTDATE"]) > strtotime($data["last_update"])) {
                        $hardware[$ID]["date"] = $computer['META']["LASTDATE"];
                        $hardware[$ID]["checksum"] = $computer['META']["CHECKSUM"];
                        $hardware[$ID]["tag"] = $computer['META']["TAG"];
                        $hardware[$ID]["name"] = addslashes($computer['META']["NAME"]);
                        $hardware[$ID]["computers_id"] = $data["computers_id"];
                        $hardware[$ID]["serial"] = $data["serial"];
                        $hardware[$ID]["ocsid"] = $data["ocsid"];
                        $hardware[$ID]["last_update"] = $data["last_update"];
                        $hardware[$ID]["id"] = $data["id"];
                     }
                  }
               }
            }

            echo "<div class='center'>";
            echo "<h2>" . __('Computers updated in OCSNG', 'ocsinventoryng') . "</h2>";

            $target  = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsng.sync.php';
            $numrows = count($hardware);
            if ($numrows > 0) {
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $target . "'>";
               self::checkBox($target);

               echo "<table class='tab_cadre_fixe'>";
               $colspan = 6;
               if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                  $colspan = 7;
               }
               echo "<tr class='tab_bg_1'><td colspan='$colspan' class='center'>";
               echo Html::submit(_sx('button', 'Synchronize'),
                                 ['name' => 'update_ok']);
               echo "</td></tr>\n";

               echo "<tr><th>" . __('Update computers', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Serial number') . "</th>";
               echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Last OCSNG inventory date', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('OCSNG TAG', 'ocsinventoryng') . "</th>";
               if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                  echo "<th>" . __('DEBUG') . "</th>";
               }
               echo "<th>&nbsp;</th></tr>\n";
               foreach ($hardware as $ID => $tab) {
                  echo "<tr class='tab_bg_2 center'>";
                  echo "<td><a href='" . $CFG_GLPI["root_doc"] . "/front/computer.form.php?id=" .
                       $tab["computers_id"] . "'>" . $tab["name"] . "</a></td>\n";
                  echo "<td>" . $tab["serial"] . "</td>\n";
                  echo "<td>" . Html::convDateTime($tab["last_update"]) . "</td>\n";
                  echo "<td>" . Html::convDateTime($hardware[$tab["ocsid"]]["date"]) . "</td>\n";
                  echo "<td>" . $hardware[$tab["ocsid"]]["tag"] . "</td>\n";

                  if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                     echo "<td>";
                     $checksum_server = intval($cfg_ocs["checksum"]);
                     $checksum_client = intval($hardware[$tab["ocsid"]]["checksum"]);
                     if ($checksum_server > 0
                         && $checksum_client > 0
                     ) {
                        $result = $checksum_server & $checksum_client;
                        echo intval($result);
                     }
                     echo "</td>";
                  }

                  echo "<td><input type='checkbox' name='toupdate[" . $tab["id"] . "]' " .
                       (($check == "all") ? "checked" : "") . "></td></tr>\n";
               }

               echo "<tr class='tab_bg_1'><td colspan='$colspan' class='center'>";
               echo Html::submit(_sx('button', 'Synchronize'), ['name' => 'update_ok']);
               echo Html::hidden('plugin_ocsinventoryng_ocsservers_id',
                                 ['value' => $plugin_ocsinventoryng_ocsservers_id]);
               echo "</td></tr>";

               echo "<tr class='tab_bg_1'><td colspan='$colspan' class='center'>";
               self::checkBox($target);
               echo "</table>\n";
               Html::closeForm();
               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<br><span class='b'>" . __('Update computers', 'ocsinventoryng') . "</span>";
               echo "<div class='center b'>" . __('No new computer to be updated', 'ocsinventoryng');
               echo "<br><a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
               echo __('Back');
               echo "</a>";
               echo "</div>";

            }
            echo "</div>";
         } else {
            echo "<div class='center b'>" . __('No new computer to be updated', 'ocsinventoryng');
            echo "<br><a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
            echo __('Back');
            echo "</a>";
            echo "</div>";
         }
      } else {
         echo "<div class='center b'>" . __('No new computer to be updated', 'ocsinventoryng');
         echo "<br><a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
         echo __('Back');
         echo "</a>";
         echo "</div>";
      }
   }
   */
   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $check
    * @param $start
    *
    * @return bool|void
    */
   static function showComputersToSynchronize($show_params) {
      global $CFG_GLPI;

      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/datatables.min.css");
      //      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/css/buttons.dataTables.min.css");
      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/ColReorder-1.5.2/css/colReorder.dataTables.min.css");
      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/Responsive-2.2.3/css/responsive.dataTables.min.css");
      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/Select-1.3.1/css/select.dataTables.min.css");

      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/datatables.min.js");
      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Responsive-2.2.3/js/dataTables.responsive.min.js");
      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Select-1.3.1/js/dataTables.select.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/js/dataTables.buttons.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/js/buttons.html5.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/js/buttons.print.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/js/buttons.colVis.min.js");
      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/ColReorder-1.5.2/js/dataTables.colReorder.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/JSZip-2.5.0/jszip.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/pdfmake-0.1.36/pdfmake.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/pdfmake-0.1.36/vfs_fonts.js");


      $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];

      if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
         return false;
      }
      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)
          && !Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
         return false;
      }

      $title      = __('Computers updated in OCSNG', 'ocsinventoryng');
      $target     =  $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.sync.php";
      $languages  = json_encode(self::getJsLanguages());
      $columnDefs = "[ {
               orderable: false,
               className: 'select-checkbox',
               targets:0,
               },{
               targets: [1],
                visible: false
               }]";

      $columns = "[
                     { 'data': 'checked' },
                      { 'data': 'ocsid' },
                      { 'data': 'name' },
                      { 'data': 'serial' },
                      { 'data': 'last_update' },
                      { 'data': 'date' },
                      { 'data': 'TAG' },
                      { 'data': 'checksum_debug' },
                       ]";

      echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
      echo "</div>";
      //      $entities = json_encode($entities_id);
      echo "<script>
            $('#ajax_loader').show();
            $(document).ready(function () {
                   var table = $('#OcsTable').DataTable({
                      ajax: {
                          url: '../ajax/loadcomputerstosynchronize.php',
                          type:'POST',
                          data: {plugin_ocsinventoryng_ocsservers_id: $plugin_ocsinventoryng_ocsservers_id
                                },
                          datatype: 'json'
                      },
                      paging: true,
                      pageLength: 25,
                      stateSave: true,
                      columnDefs: $columnDefs,
                     'select': {
                        'style': 'multi',
                        'selector': 'td:first-child'
                     },
                     'order': [[1, 'asc']],
                       columns: $columns,
                       processing: true,
                       responsive: true,
                       initComplete: function () {
                            $('#ajax_loader').hide();
                      },
                     'language': $languages,
                   });
                   table.on('click', 'th.select-checkbox', function() {
                   if ($('th.select-checkbox').hasClass('selected')) {
                       table.rows().deselect();
                       $('th.select-checkbox').removeClass('selected');
                   } else {
                       table.rows().select();
                       $('th.select-checkbox').addClass('selected');
                   }
                  }).on('select deselect', function() {
                   ('Some selection or deselection going on')
                   if (table.rows({
                           selected: true
                       }).count() !== table.rows().count()) {
                       $('th.select-checkbox').removeClass('selected');
                   } else {
                       $('th.select-checkbox').addClass('selected');
                   }
                  });
                  // Handle form submission event 
                  $('#ocsng_form').on('submit', function(e){
                     var form = this;
                     var rows_selected = table.rows('.selected').data();
                     // Iterate over all selected checkboxes
                     rows_selected.each( function ( value, index ) {
                        // Create a hidden element 
                        $(form).append(
                            $('<input>')
                               .attr('type', 'hidden')
                               .attr('name', 'toupdate[]')
                               .val(value.id)
                        );
                     });
                  });
               });
            </script>";

      echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
      echo "<div class='center'>";
      echo "<table id='OcsTable' class='display' cellspacing='0' style='width: 100%!important;'>";
      echo "<thead>";
      $nbcols = 8;
      // Set display type for export if define
      $output_type = Search::HTML_OUTPUT;
      if (isset($values["display_type"])) {
         $output_type = $values["display_type"];
      }

      $header_num = 1;
      echo "<tr><th colspan='" . $nbcols . "'>" . $title . "</th></tr>";
      //Column title
      echo Search::showNewLine($output_type);
      echo Search::showHeaderItem($output_type, __('All', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('ID'), $header_num);
      echo Search::showHeaderItem($output_type, __('Name'), $header_num);
      echo Search::showHeaderItem($output_type, __('Serial number'), $header_num);
      echo Search::showHeaderItem($output_type, __('Import date in GLPI', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Last OCSNG inventory date', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('OCSNG TAG', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Checksum', 'ocsinventoryng'), $header_num);
      echo "</thead>";
      echo Search::showFooter($output_type, $title);

      echo Html::submit(_sx('button', 'Synchronize'), ['name' => 'update_ok', 'class' => 'btn btn-primary']);
      Html::closeForm();

      if (isset($_SESSION["ocs_update"]['statistics'])) {
         echo "<br>";
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_update"]['statistics'], true);
      }
   }
   
   
   /**
    * Display a list of computers to add or to link
    *
    * @param $show_params
    *
    * @return bool
    * @throws \GlpitestSQLError
    * @internal param the $plugin_ocsinventoryng_ocsservers_id ID of the ocs server
    * @internal param display $advanced detail about the computer import or not (target entity, matched rules, etc.)
    * @internal param indicates $check if checkboxes are checked or not
    * @internal param display $start a list of computers starting at rowX
    * @internal param a $entities_id list of entities in which computers can be added or linked
    * @internal param false $tolinked for an import, true for a link
    *
    
   static function showComputersToAdd($show_params) {
      global $DB, $CFG_GLPI;

      $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];
      $advanced                            = $show_params["import_mode"];
      $check                               = $show_params["check"];
      $start                               = $show_params["start"];
      $entities_id                         = $show_params["entities_id"];
      $tolinked                            = $show_params["tolinked"];

      if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
         return false;
      }

      if (!Session::haveRight("plugin_ocsinventoryng", READ)
          && !Session::haveRight("plugin_ocsinventoryng_import", READ)
          && !Session::haveRight("plugin_ocsinventoryng_link", READ)) {
         return false;
      }

      $caneditimport = Session::haveRight('plugin_ocsinventoryng_import', UPDATE);
      $caneditlink   = Session::haveRight('plugin_ocsinventoryng_link', UPDATE);
      $usecheckbox   = ($tolinked && $caneditlink) || (!$tolinked && $caneditimport);

      $title = __('Import new computers', 'ocsinventoryng');
      if ($tolinked) {
         $title = __('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng');
      }
      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsng.import.php';
      if ($tolinked) {
         $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsng.link.php';
      }

      // Get all links between glpi and OCS
      $query_glpi     = "SELECT ocsid
                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                     WHERE `plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id";
      $result_glpi    = $DB->query($query_glpi);
      $already_linked = [];
      if ($DB->numrows($result_glpi) > 0) {
         while ($data = $DB->fetchArray($result_glpi)) {
            $already_linked [] = $data["ocsid"];
         }
      }

      //first pass for exclude ID if no linked
      $cfg_ocs         = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
      $computerOptions = ['ORDER'    => 'LASTDATE',
                          'COMPLETE' => '0',
                          'FILTER'   => [
                             'EXCLUDE_IDS' => $already_linked
                          ],
                          'DISPLAY'  => [
                             'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS | PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS
                          ],
      ];
      if ($cfg_ocs["tag_limit"] and $tag_limit = explode("$", trim($cfg_ocs["tag_limit"]))) {
         $computerOptions['FILTER']['TAGS'] = $tag_limit;
      }
      if ($cfg_ocs["tag_exclude"] and $tag_exclude = explode("$", trim($cfg_ocs["tag_exclude"]))) {
         $computerOptions['FILTER']['EXCLUDE_TAGS'] = $tag_exclude;
      }
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsResult = $ocsClient->getComputers($computerOptions);

      $computers  = (isset($ocsResult['COMPUTERS']) ? $ocsResult['COMPUTERS'] : []);
      $hardware   = [];
      $exlude_ids = [];
      if (isset($computers)) {
         if (count($computers)) {

            foreach ($computers as $data) {

               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

               $id                    = $data['META']['ID'];
               $hardware[$id]["date"] = $data['META']["LASTDATE"];
               $hardware[$id]["name"] = $data['META']["NAME"];
               $hardware[$id]["TAG"]  = $data['META']["TAG"];
               $hardware[$id]["id"]   = $data['META']["ID"];
               $hardware[$id]["UUID"] = $data['META']["UUID"];
               $contact               = $data['META']["USERID"];

               if (!empty($contact)) {
                  $query                         = "SELECT `id`
                            FROM `glpi_users`
                            WHERE `name` = '" . $contact . "';";
                  $result                        = $DB->query($query);
                  $hardware[$id]["locations_id"] = 0;
                  if ($DB->numrows($result) == 1) {
                     $user_id = $DB->result($result, 0, 0);
                     $user    = new User();
                     $user->getFromDB($user_id);
                     $hardware[$id]["locations_id"] = $user->fields["locations_id"];
                  }
               }
               if (isset($data['BIOS']) && count($data['BIOS'])) {
                  $hardware[$id]["serial"]       = $data['BIOS']["SSN"];
                  $hardware[$id]["model"]        = $data['BIOS']["SMODEL"];
                  $hardware[$id]["manufacturer"] = $data['BIOS']["SMANUFACTURER"];
               } else {
                  $hardware[$id]["serial"]       = '';
                  $hardware[$id]["model"]        = '';
                  $hardware[$id]["manufacturer"] = '';
               }

               if (isset($data['NETWORKS']) && count($data['NETWORKS'])) {
                  $hardware[$id]["NETWORKS"] = $data["NETWORKS"];

               }
            }
            if ($tolinked) {
               foreach ($hardware as $ID => $tab) {
                  $tab['entities_id'] = $entities_id;
                  $rulelink           = new RuleImportComputerCollection();
                  $params             = ['entities_id' => $entities_id,
                                         'plugin_ocsinventoryng_ocsservers_id'
                                                       => $plugin_ocsinventoryng_ocsservers_id];
                  $rulelink_results   = $rulelink->processAllRules(Toolbox::stripslashes_deep($tab), [], $params);
                  if (!isset($rulelink_results['action'])
                      || ($rulelink_results['action'] != PluginOcsinventoryngOcsProcess::LINK_RESULT_LINK
                          && $rulelink_results['action'] != PluginOcsinventoryngOcsProcess::LINK_RESULT_NO_IMPORT)
                  ) {
                     $exlude_ids[] = $ID;
                  }
               }
            } else {
               foreach ($hardware as $ID => $tab) {
                  $tab['entities_id'] = $entities_id;
                  $rulelink           = new RuleImportComputerCollection();
                  $params             = ['entities_id' => $entities_id,
                                         'plugin_ocsinventoryng_ocsservers_id'
                                                       => $plugin_ocsinventoryng_ocsservers_id];
                  $rulelink_results   = $rulelink->processAllRules(Toolbox::stripslashes_deep($tab), [], $params);
                  if (isset($rulelink_results['found_computers'])
                      && is_array($rulelink_results['found_computers'])
                      && count($rulelink_results['found_computers']) > 0) {
                     $exlude_ids[] = $ID;
                  }
               }
            }
         }
      }
      //end first pass for exclude ID if no linked
      $computerOptions = ['COMPLETE' => '0',
                          'FILTER'   => [
                             'EXCLUDE_IDS' => array_merge($already_linked, $exlude_ids)
                          ],
                          'DISPLAY'  => [
                             'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS | PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS
                          ],
      ];

      if ($cfg_ocs["tag_limit"] and $tag_limit = explode("$", trim($cfg_ocs["tag_limit"]))) {
         $computerOptions['FILTER']['TAGS'] = $tag_limit;
      }

      if ($cfg_ocs["tag_exclude"] and $tag_exclude = explode("$", trim($cfg_ocs["tag_exclude"]))) {
         $computerOptions['FILTER']['EXCLUDE_TAGS'] = $tag_exclude;
      }

      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $numrows   = $ocsClient->countComputers($computerOptions);

      if ($start != 0) {
         $computerOptions['OFFSET'] = $start;
      }
      $computerOptions['MAX_RECORDS'] = $_SESSION['glpilist_limit'];
      $ocsResult                      = $ocsClient->getComputers($computerOptions);

      $computers = (isset($ocsResult['COMPUTERS']) ? $ocsResult['COMPUTERS'] : []);

      $hardware = [];
      if (isset($computers)) {
         if (count($computers)) {
            // Get all hardware from OCS DB

            foreach ($computers as $data) {

               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

               $id                    = $data['META']['ID'];
               $hardware[$id]["date"] = $data['META']["LASTDATE"];
               $hardware[$id]["name"] = $data['META']["NAME"];
               $hardware[$id]["TAG"]  = $data['META']["TAG"];
               $hardware[$id]["id"]   = $data['META']["ID"];
               $hardware[$id]["UUID"] = $data['META']["UUID"];
               $contact               = $data['META']["USERID"];

               if (!empty($contact)) {
                  $query                         = "SELECT `id`
                            FROM `glpi_users`
                            WHERE `name` = '" . $contact . "';";
                  $result                        = $DB->query($query);
                  $hardware[$id]["locations_id"] = 0;
                  if ($DB->numrows($result) == 1) {
                     $user_id = $DB->result($result, 0, 0);
                     $user    = new User();
                     $user->getFromDB($user_id);
                     $hardware[$id]["locations_id"] = $user->fields["locations_id"];
                  }
               }
               if (isset($data['BIOS']) && count($data['BIOS'])) {
                  $hardware[$id]["serial"]       = $data['BIOS']["SSN"];
                  $hardware[$id]["model"]        = $data['BIOS']["SMODEL"];
                  $hardware[$id]["manufacturer"] = $data['BIOS']["SMANUFACTURER"];
               } else {
                  $hardware[$id]["serial"]       = '';
                  $hardware[$id]["model"]        = '';
                  $hardware[$id]["manufacturer"] = '';
               }

               if (isset($data['NETWORKS']) && count($data['NETWORKS'])) {
                  $hardware[$id]["NETWORKS"] = $data["NETWORKS"];
               }
            }

            if ($tolinked && count($hardware)) {
               echo "<div class='center b'>" .
                    __('Caution! The imported data (see your configuration) will overwrite the existing one', 'ocsinventoryng') . "</div>";
            }
            echo "<div class='center'>";

            if ($numrows) {

               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               //Show preview form only in import even in multi-entity mode because computer import
               //can be refused by a rule
               if (!$tolinked) {
                  echo "<div class='firstbloc'>";
                  echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode'
                         action='$target'>\n";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th>" . __('Manual import mode', 'ocsinventoryng') . "</th></tr>\n";
                  echo "<tr class='tab_bg_1'><td class='center'>";
                  if ($advanced) {
                     Html::showSimpleForm($target, 'change_import_mode', __('Disable preview', 'ocsinventoryng'), ['id' => 'false']);
                  } else {
                     Html::showSimpleForm($target, 'change_import_mode', __('Enable preview', 'ocsinventoryng'), ['id' => 'true']);
                  }
                  echo "</td></tr>";
                  echo "<tr class='tab_bg_1'><td class='center b'>" .
                       __('Check first that duplicates have been correctly managed in OCSNG', 'ocsinventoryng') . "</td>";
                  echo "</tr></table>";
                  Html::closeForm();
                  echo "</div>";
               }

               echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
               if ($usecheckbox) {
                  self::checkBox($target);
               }
               echo "<table class='tab_cadrehov'>";

               if ($usecheckbox) {
                  $nb_cols = 7;
                  if ($advanced && !$tolinked) {
                     $nb_cols += 3;
                  }
                  if ($tolinked) {
                     $nb_cols += 1;
                  }
                  if (($tolinked && $caneditlink) || (!$tolinked && $caneditimport)) {
                     $nb_cols += 1;
                  }

                  echo "<tr class='tab_bg_1'><td colspan='" . $nb_cols . "' class='center'>";
                  if (($tolinked && $caneditlink)) {
                     echo Html::submit(_sx('button', 'Link', 'ocsinventoryng'), ['name' => 'import_ok']);
                     echo "&nbsp;";
                     echo Html::submit(_sx('button', 'Delete link', 'ocsinventoryng'), ['name' => 'delete_link']);
                  } else if (!$tolinked && $caneditimport) {
                     echo Html::submit(_sx('button', 'Import'), ['name' => 'import_ok']);
                  }
               }
               echo "</td></tr>\n";
               echo "<tr>";
               if ($usecheckbox) {
                  echo "<th width='5%'>&nbsp;</th>";
               }
               echo "<th>" . __('Name') . "</th>\n";
               echo "<th>" . __('Manufacturer') . "</th>\n";
               echo "<th>" . __('Model') . "</th>\n";
               echo "<th>" . _n('Information', 'Informations', 2) . "</th>\n";
               echo "<th>" . __('Last OCSNG inventory date', 'ocsinventoryng') . "</th>\n";
               echo "<th>" . __('OCSNG TAG', 'ocsinventoryng') . "</th>\n";
               echo "<th>" . __('Override unicity check ?', 'ocsinventoryng') . "</th>\n";
               if ($advanced && !$tolinked) {
                  echo "<th>" . __('Match the rule ?', 'ocsinventoryng') . "</th>\n";
                  echo "<th>" . __('Destination entity') . "</th>\n";
                  echo "<th>" . __('Child entities') . "</th>\n";
               }
               if ($tolinked) {
                  echo "<th width='30%'>" . __('Item to link', 'ocsinventoryng') . "</th>";
               }
               echo "</tr>\n";

               $rule = new RuleImportEntityCollection();
               foreach ($hardware as $ID => $tab) {

                  $data = [];

                  echo "<tr class='tab_bg_2'>";
                  if ($usecheckbox) {
                     echo "<td>";
                     echo "<input type='checkbox' name='toimport[" . $tab["id"] . "]' " .
                          ($check == "all" ? "checked" : "") . ">";
                     echo "</td>";
                  }
                  if ($advanced && !$tolinked) {
                     $recursive = isset($tab["is_recursive"]) ? $tab["is_recursive"] : 0;
                     $data      = $rule->processAllRules(['ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                                          '_source'       => 'ocsinventoryng',
                                                          'is_recursive'  => $recursive,
                                                         ], ['is_recursive' => $recursive], ['ocsid' => $tab["id"]]);
                  }
                  echo "<td>" . $tab["name"] . "</td>\n";
                  echo "<td>" . $tab["manufacturer"] . "</td>";
                  echo "<td>" . $tab["model"] . "</td>";

                  echo "<td>";
                  $ssnblacklist = Blacklist::getSerialNumbers();
                  $ok           = 1;
                  if (!in_array($tab['serial'], $ssnblacklist)) {
                     printf(__('%1$s : %2$s'), __('Serial number'), $tab["serial"]);
                  } else {
                     echo "<span class='red'>";
                     printf(__('%1$s : %2$s'), __('Blacklisted serial number', 'ocsinventoryng'), $tab["serial"]);
                     echo "</span>";
                     $ok = 0;
                  }
                  $uuidblacklist = Blacklist::getUUIDs();

                  if (!in_array($tab['UUID'], $uuidblacklist)) {
                     echo "<br>";
                     printf(__('%1$s : %2$s'), __('UUID'), $tab["UUID"]);
                  } else {
                     echo "<br>";
                     echo "<span class='red'>";
                     printf(__('%1$s : %2$s'), __('Blacklisted UUID', 'ocsinventoryng'), $tab["UUID"]);
                     echo "</span>";
                     $ok = 0;
                  }
                  if (isset($tab['NETWORKS'])) {
                     $networks = $tab['NETWORKS'];

                     $ipblacklist  = Blacklist::getIPs();
                     $macblacklist = Blacklist::getMACs();

                     foreach ($networks as $opt) {

                        if (isset($opt['MACADDR'])) {
                           if (!in_array($opt['MACADDR'], $macblacklist)) {
                              echo "<br>";
                              printf(__('%1$s : %2$s'), __('MAC'), $opt['MACADDR']);
                           } else {
                              echo "<br>";
                              echo "<span class='red'>";
                              printf(__('%1$s : %2$s'), __('Blacklisted MAC', 'ocsinventoryng'), $opt['MACADDR']);
                              echo "</span>";
                              //$ok = 0;
                           }
                           if (!in_array($opt['IPADDRESS'], $ipblacklist)) {
                              echo " - ";
                              printf(__('%1$s : %2$s'), __('IP'), $opt['IPADDRESS']);
                           } else {
                              echo " - ";
                              echo "<span class='red'>";
                              printf(__('%1$s : %2$s'), __('Blacklisted IP', 'ocsinventoryng'), $opt['IPADDRESS']);
                              echo "</span>";
                              //$ok = 0;
                           }
                        }
                     }
                  }
                  echo "</td>";

                  echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
                  echo "<td>" . $tab["TAG"] . "</td>\n";
                  echo "<td>";
                  $rec = "toimport_disable_unicity_check[" . $tab["id"] . "]";
                  Dropdown::showYesNo($rec);
                  echo "</td>\n";

                  if ($advanced && !$tolinked) {
                     if (!isset($data['entities_id']) || $data['entities_id'] == -1) {
                        echo "<td class='center'><i style='color:firebrick' class='fas fa-times-circle'></i></td>\n";
                        $data['entities_id'] = -1;
                     } else {
                        echo "<td  width='15%' class='center'>";
                        $tmprule = new RuleImportEntity();
                        if ($tmprule->can($data['_ruleid'], READ)) {
                           echo "<a href='" . $tmprule->getLinkURL() . "'>" . $tmprule->getName() . "</a>";
                        } else {
                           echo $tmprule->getName();
                        }
                        echo "</td>\n";
                     }
                     echo "<td width='20%'>";
                     $ent = "toimport_entities[" . $tab["id"] . "]";
                     Entity::dropdown(['name'     => $ent,
                                       'value'    => $data['entities_id'],
                                       'comments' => 0]);
                     echo "</td>\n";
                     echo "<td width='20%'>";
                     if (!isset($data['is_recursive'])) {
                        $data['is_recursive'] = 0;
                     }
                     $rec = "toimport_recursive[" . $tab["id"] . "]";
                     Dropdown::showYesNo($rec, $data['is_recursive']);
                     echo "</td>\n";
                  }

                  if ($tolinked) {
                     $ko = 0;
                     echo "<td  width='30%'>";
                     $tab['entities_id'] = $entities_id;
                     $rulelink           = new RuleImportComputerCollection();
                     $params             = ['entities_id' => $entities_id,
                                            'plugin_ocsinventoryng_ocsservers_id'
                                                          => $plugin_ocsinventoryng_ocsservers_id];
                     $rulelink_results   = $rulelink->processAllRules(Toolbox::stripslashes_deep($tab), [], $params);

                     //Look for the computer using automatic link criterias as defined in OCSNG configuration
                     $options       = ['name' => "tolink[" . $tab["id"] . "]"];
                     $show_dropdown = true;
                     //If the computer is not explicitly refused by a rule
                     if (!isset($rulelink_results['action'])
                         || $rulelink_results['action'] != PluginOcsinventoryngOcsProcess::LINK_RESULT_NO_IMPORT
                            && $ok
                     ) {

                        if (!empty($rulelink_results['found_computers'])) {
                           $options['value']  = $rulelink_results['found_computers'][0];
                           $options['entity'] = $entities_id;
                        }
                        $options['width'] = "100%";

                        if (isset($options['value']) && $options['value'] > 0) {

                           $query = "SELECT *
                                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                                     WHERE `computers_id` = '" . $options['value'] . "' ";

                           $result = $DB->query($query);
                           if ($DB->numrows($result) > 0) {
                              $ko = 1;
                           }
                        }
                        $options['comments'] = false;
                        Computer::dropdown($options);
                        if ($ko > 0) {
                           echo "<div class='red'>";
                           echo __('Warning ! This computer is already linked with another OCS computer.', 'ocsinventoryng');
                           echo "</br>";
                           echo __('Check first that duplicates have been correctly managed in OCSNG', 'ocsinventoryng');
                           echo "</div>";
                        }
                     } else {
                        echo "<i style='color:firebrick' class='fas fa-times-circle'></i>";
                     }
                     echo "</td>";
                  }
                  echo "</tr>\n";
               }
               if ($usecheckbox) {
                  echo "<tr class='tab_bg_1'><td colspan='" . $nb_cols . "' class='center'>";
                  if ($tolinked) {
                     echo Html::submit(_sx('button', 'Link', 'ocsinventoryng'), ['name' => 'import_ok']);
                     echo "&nbsp;";
                     echo Html::submit(_sx('button', 'Delete link', 'ocsinventoryng'), ['name' => 'delete_link']);
                  } else {
                     echo Html::submit(_sx('button', 'Import'), ['name' => 'import_ok']);
                  }
                  echo Html::hidden('plugin_ocsinventoryng_ocsservers_id',
                                    ['value' => $plugin_ocsinventoryng_ocsservers_id]);
                  echo "</td></tr>";
               }
               echo "</table>\n";
               Html::closeForm();

               if ($usecheckbox) {
                  self::checkBox($target);
               }

               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr><th>" . $title . "</th></tr>\n";
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center b'>" . __('No new computer to be imported', 'ocsinventoryng') .
                    "</td></tr>\n";
               echo "</table>";
               echo "<br><div class='center'>";
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
               echo __('Back');
               echo "</a>";
               echo "</div>";
            }
            echo "</div>";
         } else {
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . $title . "</th></tr>\n";
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center b'>" . __('No new computer to be imported', 'ocsinventoryng') .
                 "</td></tr>\n";
            echo "</table></div>";
            echo "<br><div class='center'>";
            echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
            echo __('Back');
            echo "</a>";
            echo "</div>";
         }
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>" . $title . "</th></tr>\n";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center b'>" . __('No new computer to be imported', 'ocsinventoryng') .
              "</td></tr>\n";
         echo "</table></div>";
         echo "<br><div class='center'>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
         echo __('Back');
         echo "</a>";
         echo "</div>";
      }
   }
*/
   
   /**
    * Display a list of computers to add or to link
    *
    * @param $show_params
    *
    * @return bool
    * @throws \GlpitestSQLError
    * @internal param the $plugin_ocsinventoryng_ocsservers_id ID of the ocs server
    * @internal param display $advanced detail about the computer import or not (target entity, matched rules, etc.)
    * @internal param indicates $check if checkboxes are checked or not
    * @internal param display $start a list of computers starting at rowX
    * @internal param a $entities_id list of entities in which computers can be added or linked
    *
    */
   static function showComputersToAdd($show_params) {
      global $CFG_GLPI;

      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/datatables.min.css");
      //      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/css/buttons.dataTables.min.css");
      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/ColReorder-1.5.2/css/colReorder.dataTables.min.css");
      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/Responsive-2.2.3/css/responsive.dataTables.min.css");
      echo Html::css("/plugins/ocsinventoryng/lib/DataTables/Select-1.3.1/css/select.dataTables.min.css");

      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/datatables.min.js");
      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Responsive-2.2.3/js/dataTables.responsive.min.js");
      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Select-1.3.1/js/dataTables.select.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/js/dataTables.buttons.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/js/buttons.html5.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/js/buttons.print.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/Buttons-1.6.1/js/buttons.colVis.min.js");
      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/ColReorder-1.5.2/js/dataTables.colReorder.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/JSZip-2.5.0/jszip.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/pdfmake-0.1.36/pdfmake.min.js");
      //      echo Html::script("/plugins/ocsinventoryng/lib/DataTables/pdfmake-0.1.36/vfs_fonts.js");

      $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];
      $advanced                            = $show_params["import_mode"] ?? 0;
      $entities_id                         = $show_params["entities_id"];

      if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
         return false;
      }

      if (!Session::haveRight("plugin_ocsinventoryng", READ)
          && !Session::haveRight("plugin_ocsinventoryng_import", READ)
          && !Session::haveRight("plugin_ocsinventoryng_link", READ)) {
         return false;
      }

      $caneditimport = Session::haveRight('plugin_ocsinventoryng_import', UPDATE);
      //      $caneditlink   = Session::haveRight('plugin_ocsinventoryng_link', UPDATE);
      $usecheckbox  = $caneditimport;
      $title        = __('Import or link new computers', 'ocsinventoryng');
      $target     =  $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.import.php";
      $languages    = json_encode(self::getJsLanguages());
      $nbcols = 9;
      $colsadvanced = "false";
      if ($advanced) {
         $colsadvanced = "true";
         $nbcols = 14;
      }

      $columnDefs = "[ {
               orderable: false,
               className: 'select-checkbox',
               targets: [0],
               },{
               targets: [1],
                visible: false
               }]";

      $columns = "[
                      { 'data': 'checked' },
                      { 'data': 'id'},
                      { 'data': 'name' },
                      { 'data': 'serial' },
                      { 'data': 'manufacturer' },
                      { 'data': 'model' },
                      { 'data': 'infos' },
                      { 'data': 'date' },
                      { 'data': 'TAG' },
                      { 'data': 'toimport_disable_unicity_check' },
                      { 'data': 'rule_matched'},
                      { 'data': 'toimport_entities'},
                      { 'data': 'toimport_recursive'},
                      { 'data': 'computers_id_founded'},
                       ]";

      echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
      echo "</div>";
      $entities = json_encode($entities_id);
      echo "<script>
            $('#ajax_loader').show();
            $(document).ready(function () {
                   var table = $('#OcsTable').DataTable({
                      ajax: {
                          url: '../ajax/loadcomputerstoimport.php',
                          type:'POST',
                          data: {plugin_ocsinventoryng_ocsservers_id: $plugin_ocsinventoryng_ocsservers_id,
                                 entities_id: $entities,
                                 advanced: '$advanced'
                                },
                          datatype: 'json'
                      },
                      paging: true,
                      pageLength: 25,
                      stateSave: true,
                      columnDefs: $columnDefs,
                     'select': {
                        'style': 'multi',
                        'selector': 'td:first-child'
                     },
                     'order': [[1, 'asc']],
                       columns: $columns,
                       processing: true,
                       responsive: true,
                       initComplete: function () {
                            $('#ajax_loader').hide();
                      },
                     'language': $languages,
                   });
                   table.columns( [1] ).visible( false );
                   table.columns( [9, 10, 11, 12, 13] ).visible( $colsadvanced );
                   table.on('click', 'th.select-checkbox', function() {
                   if ($('th.select-checkbox').hasClass('selected')) {
                       table.rows().deselect();
                       $('th.select-checkbox').removeClass('selected');
                   } else {
                       table.rows().select();
                       $('th.select-checkbox').addClass('selected');
                   }
                  }).on('select deselect', function() {
                   ('Some selection or deselection going on')
                   if (table.rows({
                           selected: true
                       }).count() !== table.rows().count()) {
                       $('th.select-checkbox').removeClass('selected');
                   } else {
                       $('th.select-checkbox').addClass('selected');
                   }
                  });
                  // Handle form submission event 
                  $('#ocsng_form').on('submit', function(e){
                     var form = this;
                     var rows_selected = table.rows('.selected').data();
                     // Iterate over all selected checkboxes
                     rows_selected.each( function ( value, index ) {
                        // Create a hidden element 
                        $(form).append(
                            $('<input>')
                               .attr('type', 'hidden')
                               .attr('name', 'toadd[]')
                               .val(value.id)
                        );
                     });
                  });
               });
            </script>";

      //Show preview form only in import even in multi-entity mode because computer import
      //can be refused by a rule
      echo "<div class='firstbloc'>";
      echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode'
                               action='$target'>\n";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th class='center'>" . __('Manual import mode', 'ocsinventoryng') . "</th></tr>\n";
      echo "<tr class='tab_bg_1'><td class='center'>";
      if ($advanced) {
         Html::showSimpleForm($target, 'change_import_mode', __('Disable preview', 'ocsinventoryng'), ['id' => 'false']);
      } else {
         Html::showSimpleForm($target, 'change_import_mode', __('Enable preview', 'ocsinventoryng'), ['id' => 'true']);
      }
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td class='center b'>" .
           __('Check first that duplicates have been correctly managed in OCSNG', 'ocsinventoryng') . "</td>";
      echo "</tr></table>";
      Html::closeForm();
      echo "</div>";

      echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
      echo "<div class='center'>";
      echo "<table id='OcsTable' class='display' cellspacing='0' style='width: 100%!important;'>";
      echo "<thead>";

      // Set display type for export if define
      $output_type = Search::HTML_OUTPUT;
      if (isset($values["display_type"])) {
         $output_type = $values["display_type"];
      }

      $header_num = 1;
      echo "<tr><th colspan='" . $nbcols . "'>" . $title . "</th></tr>";
      //Column title
      echo Search::showNewLine($output_type);
      echo Search::showHeaderItem($output_type, __('All', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('ID'), $header_num);
      echo Search::showHeaderItem($output_type, __('Name'), $header_num);
      echo Search::showHeaderItem($output_type, __('Serial number'), $header_num);
      echo Search::showHeaderItem($output_type, __('Manufacturer'), $header_num);
      echo Search::showHeaderItem($output_type, __('Model'), $header_num);
      echo Search::showHeaderItem($output_type, _n('Information', 'Informations', 2), $header_num);
      echo Search::showHeaderItem($output_type, __('Last OCSNG inventory date', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('OCSNG TAG', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Override unicity check ?', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Match the rule ?', 'ocsinventoryng'), $header_num);
      echo Search::showHeaderItem($output_type, __('Destination entity'), $header_num);
      echo Search::showHeaderItem($output_type, __('Child entities'), $header_num);
      echo Search::showHeaderItem($output_type, __('Item to link', 'ocsinventoryng'), $header_num);
      echo "</thead>";
      echo Search::showFooter($output_type, $title);

      echo Html::submit($title, ['name' => 'import_ok', 'class' => 'btn btn-primary']);
      Html::closeForm();

      if (isset($_SESSION["ocs_import"]['statistics'])) {
         echo "<br>";
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_import"]['statistics'], true);
      }
   }
   
   /**
    * Get a direct link to the computer in ocs console
    *
    * @param $plugin_ocsinventoryng_ocsservers_id the ID of the OCS server
    * @param $ocsid ID of the computer in OCS hardware table
    * @param $todisplay the link's label to display
    * @param $only_url
    *
    *
    * return the html link to the computer in ocs console
    *
    * @return string
    */
   static function getComputerLinkToOcsConsole($plugin_ocsinventoryng_ocsservers_id, $ocsid, $todisplay, $only_url = false) {

      $ocs_config = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
      $url        = '';

      if ($ocs_config["ocs_url"] != '') {
         //Display direct link to the computer in ocsreports
         $url = $ocs_config["ocs_url"];
         if (!preg_match("/\/$/i", $ocs_config["ocs_url"])) {
            $url .= '/';
         }
         if ($ocs_config['ocs_version'] > self::OCS2_VERSION_LIMIT) {
            $url = $url . "index.php?function=computer&amp;head=1&amp;systemid=$ocsid";
         } else {
            $url = $url . "machine.php?systemid=$ocsid";
         }

         if ($only_url) {
            return $url;
         }
         return "<a class='vsubmit' target='_blank' href='$url'>" . $todisplay . "</a>";
      }
      return $url;
   }

   /**
    *
    * @return array
    */
   static function getValuesActionCron() {
      $values                               = [];
      $values[self::ACTION_PURGE_COMPUTER]  = self::getActionCronName(self::ACTION_PURGE_COMPUTER);
      $values[self::ACTION_DELETE_COMPUTER] = self::getActionCronName(self::ACTION_DELETE_COMPUTER);

      return $values;
   }

   /**
    *
    * @param type $value
    *
    * @return type
    */
   static function getActionCronName($value) {
      switch ($value) {
         case self::ACTION_PURGE_COMPUTER :
            return __('Purge computers in OCSNG', 'ocsinventoryng');

         case self::ACTION_DELETE_COMPUTER :
            return __('Delete computers', 'ocsinventoryng');
         default :
            // Return $value if not define
            return $value;
      }
   }

   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name) {
      // no translation for the name of the project
      switch ($name) {
         case 'ocsng':
            return ['description' => __('OCSNG', 'ocsinventoryng') . " - " .
                                     __('Launch OCSNG synchronization script', 'ocsinventoryng')];
            break;
         case 'CleanOldAgents':
            return ['description' => __('OCSNG', 'ocsinventoryng') . " - " .
                                     __('Clean old agents & drop from OCSNG software', 'ocsinventoryng')];
            break;
         case 'RestoreOldAgents':
            return ['description' => __('OCSNG', 'ocsinventoryng') . " - " .
                                     __('Restore computers from the date of last inventory', 'ocsinventoryng')];
            break;
      }
   }

   /**
    * cron Clean Old Agents
    *
    * @param $task
    *
    * @return int
    */
   static function cronCleanOldAgents($task) {
      global $DB;

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsServer", "CleanOldAgents")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      $cron_status                         = 0;
      $plugin_ocsinventoryng_ocsservers_id = 0;
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers",
                            "`is_active` = 1 AND `use_cleancron` = 1") as $config) {
         $plugin_ocsinventoryng_ocsservers_id = $config["id"];
         if ($plugin_ocsinventoryng_ocsservers_id > 0) {

            $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
            $agents    = $ocsClient->getOldAgents($config["cleancron_nb_days"]);

            if ($config['action_cleancron'] == self::ACTION_PURGE_COMPUTER) {
               //action purge agents OCSNG

               $computers  = [];
               $can_update = PluginOcsinventoryngConfig::canUpdateOCS();
               if (count($agents) > 0 && $can_update) {

                  $nb = $ocsClient->deleteOldAgents($agents);
                  if ($nb) {
                     $cron_status = 1;
                     if ($task) {
                        $task->addVolume($nb);
                        $task->log(__('Clean old agents OK', 'ocsinventoryng'));
                     }
                  } else {
                     $task->log(__('Clean old agents failed', 'ocsinventoryng'));
                  }
               }
            } else {
               //action delete computers

               if (count($agents) > 0) {
                  $nb = PluginOcsinventoryngOcslink::deleteComputers($plugin_ocsinventoryng_ocsservers_id, $agents);
                  if ($nb) {
                     $cron_status = 1;
                     if ($task) {
                        $task->addVolume($nb);
                        $task->log(__('Delete computers OK', 'ocsinventoryng'));
                     }
                  } else {
                     $task->log(__('Delete computers failed', 'ocsinventoryng'));
                  }
               }
            }
         }
      }

      return $cron_status;
   }

   /**
    * cron Restore Old Agents
    *
    * @param type  $task
    *
    * @return int
    * @global type $DB
    * @global type $CFG_GLPI
    *
    */
   static function cronRestoreOldAgents($task) {
      global $DB;

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsServer", "RestoreOldAgents")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      $cron_status                         = 0;
      $plugin_ocsinventoryng_ocsservers_id = 0;
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers",
                            "`is_active` = 1 AND `use_cleancron` = 1 AND `use_restorationcron` = 1") as $config) {
         $plugin_ocsinventoryng_ocsservers_id = $config["id"];
         if ($plugin_ocsinventoryng_ocsservers_id > 0) {
            $delay = $config['delay_restorationcron'];

            $query = "SELECT `glpi_computers`.`id`
                     FROM `glpi_computers` 
                     INNER JOIN `glpi_plugin_ocsinventoryng_ocslinks` AS ocslink 
                        ON ocslink.`computers_id` = `glpi_computers`.`id`
                     WHERE `glpi_computers`.`is_deleted` = 1 
                     AND ( unix_timestamp(ocslink.`last_ocs_update`) >= UNIX_TIMESTAMP(NOW() - INTERVAL $delay DAY))";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $computer = new Computer();
               while ($data = $DB->fetchAssoc($result)) {
                  $computer->fields['id']         = $data['id'];
                  $computer->fields['is_deleted'] = 0;
                  $computer->fields['date_mod']   = $_SESSION['glpi_currenttime'];
                  $computer->updateInDB(['is_deleted', 'date_mod']);
                  //add history
                  $changes[0] = 0;
                  $changes[2] = "";
                  $changes[1] = "";
                  Log::history($data['id'], 'Computer', $changes, 0,
                               Log::HISTORY_RESTORE_ITEM);
                  //                  $computer->update(array('id' => $data['id'], 'is_deleted' => 0));
               }
               $cron_status = 1;
               if ($task) {
                  $task->addVolume($DB->numrows($result));
                  $task->log(__('Restore computers OK', 'ocsinventoryng'));
               }
            } else {
               $task->log(__('Restore computers failed', 'ocsinventoryng'));
            }
         }
      }

      return $cron_status;
   }


   /**
    * @param $task
    *
    * @return int
    */
   static function cronocsng($task) {
      global $DB;

      $original_glpiname = $_SESSION['glpiname'] ?? null;
      $_SESSION["glpiname"] = 'ocsinventoryng';

      try {
         //Get a randon server id
         $plugin_ocsinventoryng_ocsservers_id = self::getRandomServerID();

         if ($plugin_ocsinventoryng_ocsservers_id > 0) {
            //Initialize the server connection
            $PluginOcsinventoryngDBocs = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

            $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
            $task->log(__('Launch OCSNG synchronization script from server', 'ocsinventoryng') . " " . $cfg_ocs['name'] . "\n");

            if (!$cfg_ocs["cron_sync_number"]) {
               return 0;
            }
            PluginOcsinventoryngOcsProcess::manageDeleted($plugin_ocsinventoryng_ocsservers_id, false);

            //         $query    = "SELECT MAX(`last_ocs_update`)
            //                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
            //                   WHERE `plugin_ocsinventoryng_ocsservers_id`= $plugin_ocsinventoryng_ocsservers_id";
            //         $max_date = "0000-00-00 00:00:00";
            //         if ($result = $DB->query($query)) {
            //            if ($DB->numrows($result) > 0) {
            //               $max_date = $DB->result($result, 0, 0);

            // Get linked computer ids in GLPI
            $already_linked_query  =
               "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid " .
               "FROM `glpi_plugin_ocsinventoryng_ocslinks` " .
               "INNER JOIN `glpi_computers` on `glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` " .
               "WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id";
            $already_linked_result = $DB->query($already_linked_query);

            $already_linked_ids = [];
            if ($DB->numrows($already_linked_result) > 0)
               while ($data = $DB->fetchAssoc($already_linked_result))
                  $already_linked_ids [] = $data['ocsid'];

            // Fetch linked computers from ocs
            $res = [];
            if (count($already_linked_ids) > 0) {
               $ocsResult = $PluginOcsinventoryngDBocs->getComputers([
                                                                        'MAX_RECORDS' => $cfg_ocs["cron_sync_number"],
                                                                        'ORDER'       => 'LASTDATE',
                                                                        'COMPLETE'    => '0',
                                                                        'FILTER'      => [
                                                                           'IDS'      => $already_linked_ids,
                                                                           'CHECKSUM' => $cfg_ocs["checksum"],
                                                                        ]
                                                                     ]);

               if (isset($ocsResult['COMPUTERS']) && count($ocsResult['COMPUTERS']) > 0) {
                  foreach ($ocsResult['COMPUTERS'] as $computer)
                     $res[$computer['META']['ID']] = $computer['META'];
               }
            }

            //         $res = $PluginOcsinventoryngDBocs->getComputersToUpdate($cfg_ocs, $max_date);

            $task->setVolume(0);
            if (count($res) > 0) {

               foreach ($res as $k => $data) {
                  if (count($data) > 0) {
                     // Fetch all linked computers from GLPI that were returned from OCS
                     $query_glpi  = "SELECT `id`, `ocs_deviceid`
                                    FROM `glpi_plugin_ocsinventoryng_ocslinks`
                                    WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                                = $plugin_ocsinventoryng_ocsservers_id
                                          AND `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` = '" . $data["ID"] . "'";
                     $result_glpi = $DB->query($query_glpi);
                     if ($DB->numrows($result_glpi) > 0) {
                        while ($values = $DB->fetchAssoc($result_glpi)) {
                           $task->addVolume(1);
                           $task->log(sprintf(__('%1$s: %2$s'), _n('Computer', 'Computer', 1),
                                             sprintf(__('%1$s (%2$s)'), $values["ocs_deviceid"], $values["id"])));

                           $sync_params = ['ID'                                  => $values["id"],
                                          'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                          'cfg_ocs'                             => $cfg_ocs,
                                          'force'                               => 0];
                           PluginOcsinventoryngOcsProcess::synchronizeComputer($sync_params);

                        }
                     }
                  }
               }
            } else {
               return 0;
            }
         }
         return 1;
      } catch (Exception $e) {

      } finally {
         $_SESSION['glpiname'] = $original_glpiname;
      }
   }

   /**
    * For other plugins, add a type to the linkable types
    *
    *
    * @param $type string class name
    * */
   static function registerType($type) {
      if (!in_array($type, self::$types)) {
         self::$types[] = $type;
      }
   }

   /**
    * Type than could be linked to a Store
    *
    * @param $all boolean, all type, or only allowed ones
    *
    * @return array of types
    * */
   static function getTypes($all = false) {

      if ($all) {
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;

      foreach ($types as $key => $type) {
         $dbu = new DbUtils();
         if (!($item = $dbu->getItemForItemtype($type))) {
            continue;
         }

         if (!$item->canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }


   /**
    * @param $id
    */
   static function showOcsReportsConsole($id) {

      $ocsconfig = self::getConfig($id);

      echo "<div class='center'>";
      if ($ocsconfig["ocs_url"] != '') {
         echo "<iframe src='" . $ocsconfig["ocs_url"] . "/index.php?multi=4' width='95%' height='650'>";
      }
      echo "</div>";
   }

   /**
    * @param $target
    * *@since version 0.84
    *
    */
   static function checkBox($target) {

      echo "<a href='" . $target . "?check=all' " .
           "onclick= \"if (markCheckboxes('ocsng_form')) return false;\">" . __('Check all') .
           "</a>&nbsp;/&nbsp;\n";
      echo "<a href='" . $target . "?check=none' " .
           "onclick= \"if ( unMarkCheckboxes('ocsng_form') ) return false;\">" .
           __('Uncheck all') . "</a>\n";
   }



   /**
    * @param null $checkitem
    *
    * @return an
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
    *
    */
   //   function getSpecificMassiveActions($checkitem = null) {
   //
   //      $actions = parent::getSpecificMassiveActions($checkitem);
   //
   //      return $actions;
   //   }
   //

   /**
    * @internal param $width
    */
   function showSystemInformations() {
      $dbu        = new DbUtils();
      $ocsServers = $dbu->getAllDataFromTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]);
      if (!empty($ocsServers)) {
         echo "\n<tr class='tab_bg_2'><th>OCS Inventory NG</th></tr>\n";

         $msg = '';
         foreach ($ocsServers as $ocsServer) {

            echo "<tr class='tab_bg_1'><td>";
            $msg = __('Host', 'ocsinventoryng') . ": " . $ocsServer['ocs_db_host'] . "";
            $msg .= "<br>" . __('Connection') . ": " . (self::checkOCSconnection($ocsServer['id']) ? "Ok" : "KO");
            $msg .= "<br>" . __('Use the OCSNG software dictionary', 'ocsinventoryng') . ": " .
                    ($ocsServer['use_soft_dict'] ? 'Yes' : 'No');
            echo $msg;
            echo "</td></tr>";
         }
      }
   }

   /**
    * Get all languages for a specific library
    *
    * @return array $languages
    * @internal param string $name name of the library :
    *    Currently available :
    *        sDashboard (for Datatable),
    *        mydashboard (for our own)
    */
   static function getJsLanguages() {

      $languages                    = [];
      $languages['sEmptyTable']     = __('No data available in table', 'ocsinventoryng');
      $languages['sInfo']           = __('Showing _START_ to _END_ of _TOTAL_ entries', 'ocsinventoryng');
      $languages['sInfoEmpty']      = __('Showing 0 to 0 of 0 entries', 'ocsinventoryng');
      $languages['sInfoFiltered']   = __('(filtered from _MAX_ total entries)', 'ocsinventoryng');
      $languages['sInfoPostFix']    = __('');
      $languages['sInfoThousands']  = __(',');
      $languages['sLengthMenu']     = __('Show _MENU_ entries', 'ocsinventoryng');
      $languages['sLoadingRecords'] = __('Loading') . "...";
      $languages['sProcessing']     = __('Processing') . "...";
      $languages['sSearch']         = __('Search') . ":";
      $languages['sZeroRecords']    = __('No matching records found', 'ocsinventoryng');
      $languages['oPaginate']       = [
         'sFirst'    => __('First'),
         'sLast'     => __('Last'),
         'sNext'     => " " . __('Next'),
         'sPrevious' => __('Previous')
      ];
      $languages['oAria']           = [
         'sSortAscending'  => __(': activate to sort column ascending', 'ocsinventoryng'),
         'sSortDescending' => __(': activate to sort column descending', 'ocsinventoryng')
      ];
      $languages['close']           = __("Close", "ocsinventoryng");
      $languages['maximize']        = __("Maximize", "ocsinventoryng");
      $languages['minimize']        = __("Minimize", "ocsinventoryng");
      $languages['refresh']         = __("Refresh", "ocsinventoryng");
      $languages['buttons']         = [
         'colvis' => __('Column visibility', 'ocsinventoryng'),
      ];

      $languages['select'] = [
         'rows' => __('%d rows selected', 'ocsinventoryng'),
      ];

      return $languages;
   }
}
