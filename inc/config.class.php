<?php

/*
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

/**
 * Class PluginOcsinventoryngConfig
 */
class PluginOcsinventoryngConfig extends CommonDBTM {

   /**
    * @var string
    */
   static $rightname = "plugin_ocsinventoryng";

   /**
    * @param int $nb
    *
    * @return string|translated
    */
   static function getTypeName($nb = 0) {
      return __("Plugin setup", 'ocsinventoryng');
   }


   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $tab['1'] = __('Alerts', 'ocsinventoryng');
               if (PluginOcsinventoryngOcsServer::useMassImport()) {
                  //If connection to the OCS DB  is ok, and all rights are ok too
                  $tab['2'] = __("Automatic synchronization's configuration", 'ocsinventoryng');
                  $tab['3'] = __('Check OCSNG import script', 'ocsinventoryng');
               }
               return $tab;
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

      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->displayAlerts();
                  break;
               case 2 :
                  $item->showFormAutomaticSynchronization();
                  break;
               case 3 :
                  $item->showScriptLock();
                  break;
            }

            break;

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
      return $ong;
   }

   public static function getConfig() {
      static $config = null;

      if (is_null($config)) {
         $config = new self();
      }
      $config->getFromDB(1);

      return $config;
   }

   /**
    *
    */
   static function showMenu() {
      global $CFG_GLPI;

      $configLabel = __('Configuration');
      $serverLabel = _n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng');
      $pluginLabel = self::getTypeName();

      echo <<<HTML
      <div class="container text-center d-flex flex-column">
         <h2>$configLabel</h2>
         <a class="btn btn-outline-secondary mt-3 mx-auto w-50" href='{$CFG_GLPI["root_doc"]}/plugins/ocsinventoryng/front/ocsserver.php'>$serverLabel</a>
         <a class="btn btn-outline-secondary mt-3 mx-auto w-50" href='{$CFG_GLPI["root_doc"]}/plugins/ocsinventoryng/front/config.form.php'>$pluginLabel</a>
      </div>
      HTML;
   }

   /**
    * @param array $options
    *
    * @return bool
    */
   function showForm($options = []) {

      $this->getFromDB(1);

      $form = [
         'action' => $this->getFormURL(),
         'buttons' => [
            [
               'name' => 'update',
               'type' => 'submit',
               'value' => __('Save'),
               'class' => 'btn btn-secondary'
            ]
         ],
         'content' => [
            __('OCS-NG Synchronization alerts', 'ocsinventoryng') => [
               'visible' => true,
               'inputs' => [
                  [
                     'type' => 'hidden',
                     'name' => 'id',
                     'value' => 1,
                  ],
                  __('New imported computers from OCS-NG', 'ocsinventoryng') => [
                     'type' => 'number',
                     'name' => 'use_newocs_alert',
                     'min' => 0,
                     'max' => 99,
                     'value' => $this->fields["use_newocs_alert"],
                     'col_lg' => 6,
                  ],
                  __('Computers not synchronized with OCS-NG since more', 'ocsinventoryng') => [
                     'type' => 'number',
                     'name' => 'delay_ocs',
                     'min' => 0,
                     'max' => 99,
                     'value' => $this->fields["delay_ocs"],
                     'col_lg' => 6,
                  ]
               ]
            ]
         ]
      ];
      renderTwigForm($form);

      return true;
   }

   static function displayAlerts() {
      global $DB;

      $CronTask = new CronTask();

      $config = self::getConfig();

      $ocsalert = new PluginOcsinventoryngOcsAlert();
      $ocsalert->getFromDBbyEntity($_SESSION["glpiactive_entity"]);
      if (isset($ocsalert->fields["use_newocs_alert"])
          && $ocsalert->fields["use_newocs_alert"] > 0) {
         $use_newocs_alert = $ocsalert->fields["use_newocs_alert"];
      } else {
         $use_newocs_alert = $config->useNewocsAlert();
      }

      if (isset($ocsalert->fields["delay_ocs"])
          && $ocsalert->fields["delay_ocs"] > 0) {
         $delay_ocs = $ocsalert->fields["delay_ocs"];
      } else {
         $delay_ocs = $config->getDelayOcs();
      }
      $synchro_ocs = 0;
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsAlert", "SynchroAlert")) {
         if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $delay_ocs > 0) {
            $synchro_ocs = 1;
         }
      }
      $new_ocs = 0;
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsAlert", "AlertNewComputers")) {
         if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $use_newocs_alert > 0) {
            $new_ocs = 1;
         }
      }

      if ($synchro_ocs == 0
          && $new_ocs == 0) {
         echo "<div align='center'><b>" . __('No used alerts, please activate the automatic actions', 'ocsinventoryng') . "</b></div>";
      }

      if ($new_ocs != 0) {

         foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers", "`is_active` = 1") as $config) {

            $query  = PluginOcsinventoryngOcsAlert::queryNew($delay_ocs, $config,
                                                             $_SESSION["glpiactive_entity"]);
            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {

               if (Session::isMultiEntitiesMode()) {
                  $nbcol = 9;
               } else {
                  $nbcol = 8;
               }

               echo "<div align='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'>";
               echo "<tr><th colspan='$nbcol'>";
               echo __('New imported computers from OCS-NG', 'ocsinventoryng') . " - " . $delay_ocs . " " . _n('Day', 'Days', 2) . "</th></tr>";
               echo "<tr><th>" . __('Name') . "</th>";
               if (Session::isMultiEntitiesMode()) {
                  echo "<th>" . __('Entity') . "</th>";
               }
               echo "<th>" . __('Operating system') . "</th>";
               echo "<th>" . __('Status') . "</th>";
               echo "<th>" . __('Location') . "</th>";
               echo "<th>" . __('User') . " / " . __('Group') . " / " . __('Alternate username') . "</th>";
               echo "<th>" . __('Last OCSNG inventory date', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('OCSNG server', 'ocsinventoryng') . "</th></tr>";

               while ($data = $DB->fetchArray($result)) {
                  echo PluginOcsinventoryngOcsAlert::displayBody($data);
               }
               echo "</table></div>";
            } else {
               echo "<br><div align='center'><b>" . __('No new imported computer from OCS-NG', 'ocsinventoryng') . "</b></div>";
            }
         }
         echo "<br>";

      }

      if ($synchro_ocs != 0) {

         foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers", "`is_active` = 1") as $config) {

            $query  = PluginOcsinventoryngOcsAlert::query($delay_ocs, $config, $_SESSION["glpiactive_entity"]);

            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {

               if (Session::isMultiEntitiesMode()) {
                  $nbcol = 9;
               } else {
                  $nbcol = 8;
               }
               echo "<div align='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'>";
               echo "<tr><th colspan='$nbcol'>";
               echo __('Computers not synchronized with OCS-NG since more', 'ocsinventoryng') . " " . $delay_ocs . " " . _n('Day', 'Days', 2) . "</th></tr>";
               echo "<tr><th>" . __('Name') . "</th>";
               if (Session::isMultiEntitiesMode()) {
                  echo "<th>" . __('Entity') . "</th>";
               }
               echo "<th>" . __('Operating system') . "</th>";
               echo "<th>" . __('Status') . "</th>";
               echo "<th>" . __('Location') . "</th>";
               echo "<th>" . __('User') . " / " . __('Group') . " / " . __('Alternate username') . "</th>";
               echo "<th>" . __('Last OCSNG inventory date', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('OCSNG server', 'ocsinventoryng') . "</th></tr>";

               while ($data = $DB->fetchArray($result)) {

                  echo PluginOcsinventoryngOcsAlert::displayBody($data);
               }
               echo "</table></div>";
            } else {
               echo "<br><div align='center'><b>" . __('No computer not synchronized since more', 'ocsinventoryng') . " " . $delay_ocs . " " . _n('Day', 'Days', 2) . "</b></div>";
            }
         }
         echo "<br>";

      }
   }


   /**
    * @return bool
    * @internal param $target
    */
   function showFormAutomaticSynchronization() {

      if (!Session::haveRight("plugin_ocsinventoryng_sync", READ)) {
         return false;
      }
      $canedit = Session::haveRight("plugin_ocsinventoryng_sync", UPDATE);
      $this->getFromDB(1);

      $form = [
         'action' => $this->getFormURL(),
         'buttons' => [
            $canedit ? [
               'name' => 'update',
               'type' => 'submit',
               'value' => __('Save'),
               'class' => 'btn btn-secondary'
            ] : []
         ],
         'content' => [
            $this->getTypeName() => [
               'visible' => true,
               'inputs' => [
                  [
                     'type' => 'hidden',
                     'name' => 'id',
                     'value' => 1,
                  ],
                  __('Show processes where nothing was changed', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'is_displayempty',
                     'value' => $this->fields["is_displayempty"],
                  ],
                  __('Authorize the OCSNG update (purge agents when purge GLPI computers or from Automatic actions)', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'allow_ocs_update',
                     'value' => $this->fields["allow_ocs_update"],
                     'col_lg' => 8,
                  ],
                  __('Log imported computers', 'ocsinventoryng') => [
                     'type' => 'checkbox',
                     'name' => 'log_imported_computers',
                     'value' => $this->fields["log_imported_computers"],
                  ],
                  __('Refresh information of a process every', 'ocsinventoryng') => [
                     'type' => 'number',
                     'name' => 'delay_refresh',
                     'min' => 0,
                     'max' => 99,
                     'after' => _n('second', 'seconds', 2, 'ocsinventoryng'),
                     'value' => $this->fields["delay_refresh"],
                     'col_lg' => 8,
                  ],
                  __('Comments') => [
                     'type' => 'textarea',
                     'name' => 'comment',
                     'value' => $this->fields["comment"],
                     'col_lg' => 12,
                     'col_md' => 12,
                  ]
               ]
            ]
         ]
      ];
      renderTwigForm($form);
      return true;
   }


   /**
    *
    */
   function showScriptLock() {

      $statusLabel = $this->isScriptLocked() ? __('Lock activated', 'ocsinventoryng') : __('Lock not activated', 'ocsinventoryng');
      $statusIcon = $this->isScriptLocked() ? "<i style='color:firebrick' class='fas fa-lock'></i>" : "<i style='color:darkgreen' class='fas fa-unlock'></i>";

      echo "<p> $statusLabel $statusIcon </p>";
      $form = [
         'action' => $_SERVER['HTTP_REFERER'],
         'buttons' => [ [
            'name' =>  $this->isScriptLocked() ? 'soft_unlock' : 'soft_lock',
            'type' => 'submit',
            'value' => $this->isScriptLocked() ? __('Unlock', 'ocsinventoryng') : __('Lock', 'ocsinventoryng'),
            'class' => 'btn btn-secondary'
         ] ],
         'content' => [ '' => [ 'visible' => false, 'inputs' => [] ] ] ];
      renderTwigForm($form);
      
   }


   /**
    * @return bool
    */
   static function isScriptLocked() {
      return file_exists(PLUGIN_OCSINVENTORYNG_LOCKFILE);
   }


   /**
    *
    */
   function setScriptLock() {

      $fp = fopen(PLUGIN_OCSINVENTORYNG_LOCKFILE, "w+");
      fclose($fp);
   }


   /**
    *
    */
   function removeScriptLock() {

      if (file_exists(PLUGIN_OCSINVENTORYNG_LOCKFILE)) {
         unlink(PLUGIN_OCSINVENTORYNG_LOCKFILE);
      }
   }


   /**
    * @return mixed
    */
   function getAllOcsServers() {
      global $DB;

      $servers[-1] = __('All servers', 'ocsinventoryng');

      $sql    = "SELECT `id`, `name`
                  FROM `glpi_plugin_ocsinventoryng_ocsservers`";
      $result = $DB->query($sql);

      while ($conf = $DB->fetchArray($result)) {
         $servers[$conf["id"]] = $conf["name"];
      }

      return $servers;
   }


   /**
    * @return mixed
    */
   static function canUpdateOCS() {

      $config = new PluginOcsinventoryngConfig();
      $config->getFromDB(1);
      return $config->fields['allow_ocs_update'];
   }

   /**
    * @return mixed
    */
   static function logProcessedComputers() {

      $config = new PluginOcsinventoryngConfig();
      $config->getFromDB(1);
      return $config->fields['log_imported_computers'];
   }

   /**
    * Display debug information for current object
    **/
   function showDebug() {

      NotificationEvent::debugEvent(new PluginOcsinventoryngNotimportedcomputer(),
                                    ['entities_id' => 0,
                                     'notimported' => []]);
   }

   //----------------- Getters and setters -------------------//

   public function useNewocsAlert() {
      return $this->fields['use_newocs_alert'];
   }

   public function getDelayOcs() {
      return $this->fields['delay_ocs'];
   }

}
