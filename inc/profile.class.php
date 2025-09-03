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
 * Class PluginOcsinventoryngProfile
 */
class PluginOcsinventoryngProfile extends CommonDBTM {


   static $rightname = "profile";

   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Profile'
          && $item->getField('interface') != 'helpdesk') {
         return __('OCSNG', 'ocsinventoryng');
      }
      return '';
   }


   /**
    * @see inc/CommonGLPI::displayTabContentForItem()
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         $ID   = $item->getID();
         $prof = new self();

         self::addDefaultProfileInfos($ID,
                                      ['plugin_ocsinventoryng'        => 0,
                                       'plugin_ocsinventoryng_sync'   => 0,
                                       'plugin_ocsinventoryng_view'   => 0,
                                       'plugin_ocsinventoryng_import' => 0,
                                       'plugin_ocsinventoryng_link'   => 0,
                                       'plugin_ocsinventoryng_clean'  => 0,
                                       'plugin_ocsinventoryng_rule'   => 0
                                      ]);
         $prof->showForm($ID);
      }
      return true;
   }


   /**
    * @param $ID
    */
   static function createFirstAccess($ID) {
      //85
      self::addDefaultProfileInfos($ID,
                                   ['plugin_ocsinventoryng'        => READ + CREATE + UPDATE + PURGE,
                                    'plugin_ocsinventoryng_sync'   => READ + UPDATE,
                                    'plugin_ocsinventoryng_view'   => READ,
                                    'plugin_ocsinventoryng_import' => READ + UPDATE,
                                    'plugin_ocsinventoryng_link'   => READ + UPDATE,
                                    'plugin_ocsinventoryng_clean'  => READ + UPDATE,
                                    'plugin_ocsinventoryng_rule'   => READ + UPDATE], true);
   }


   /**
    * @param      $profiles_id
    * @param      $rights
    * @param bool $drop_existing
    *
    * @internal param $profile
    */
   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {

      $profileRight = new ProfileRight();
      $dbu = new DbUtils();
      foreach ($rights as $right => $value) {
         if ($dbu->countElementsInTable('glpi_profilerights',["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
            $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
         }
         if (!$dbu->countElementsInTable('glpi_profilerights',
                                         ["profiles_id" => $profiles_id, "name" => $right])) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

   /**
    * Show profile form
    *
    * @param int  $profiles_id
    * @param bool $openform
    * @param bool $closeform
    *
    * @return void
    * @throws \GlpitestSQLError
    * @internal param int $items_id id of the profile
    * @internal param value $target url of target
    */
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {
      global $DB, $CFG_GLPI;

      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      $used = [];
      $configid = [];
      $crit = ['profiles_id' => $profiles_id];
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers_profiles", $crit) as $data) {
         $used[$data['plugin_ocsinventoryng_ocsservers_id']] = $data['plugin_ocsinventoryng_ocsservers_id'];
         $configid[$data['plugin_ocsinventoryng_ocsservers_id']] = $data['id'];
      }
      $available_servers = [];
      if (Session::haveRight("profile", UPDATE)) {
         $query = "SELECT `id`, `name` 
                   FROM `glpi_plugin_ocsinventoryng_ocsservers` 
                   WHERE `is_active` = 1 
                   ORDER BY `name`";
         $result = $DB->query($query);
         
         while ($data = $DB->fetchAssoc($result)) {
            if (!in_array($data['id'], $used)) {
               $available_servers[$data['id']] = htmlspecialchars($data['name']);
            }
         }
      }
   
      $assigned_servers = [];
      $dbu = new DbUtils();
      $nbservers = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers_profiles',
                                        ["profiles_id" => $profiles_id]);
   
      if ($nbservers > 0) {
         $ocsserver = new PluginOcsinventoryngOcsServer();
         foreach ($used as $id) {
            if ($ocsserver->getFromDB($id)) {
               $assigned_servers[] = [
                  'id' => $configid[$id],
                  'name' => $ocsserver->getLink(),
                  'server_id' => $id
               ];
            }
         }
      }
   
      if (Session::haveRight("profile", UPDATE)) {
         $add_form = [
            'action' => $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/profile.form.php",
            'method' => 'post',
            'buttons' => [
               !empty($available_servers) ? [
                  'name' => 'addocsserver',
                  'value' => _sx('button', 'Add'),
                  'type' => 'submit',
                  'class' => 'btn btn-secondary',
               ] : []
            ],
            'content' => [
               sprintf(__('%1$s - %2$s'), 'OcsinventoryNG', $profile->fields["name"]) => [
                  'visible' => true,
                  'inputs' => []
               ]
            ]
         ];
   
         if (!empty($available_servers)) {
            $add_form['content'][sprintf(__('%1$s - %2$s'), 'OcsinventoryNG', $profile->fields["name"])]['inputs'][_n('Allowed OCSNG server', 'Allowed OCSNG servers', 2, 'ocsinventoryng')] = [
               'type' => 'select',
               'name' => 'plugin_ocsinventoryng_ocsservers_id',
               'values' => ['' => Dropdown::EMPTY_VALUE] + $available_servers,
               'col_lg' => 6,
               'col_md' => 8,
            ];
   
            $add_form['content'][sprintf(__('%1$s - %2$s'), 'OcsinventoryNG', $profile->fields["name"])]['inputs'][''] = [
               'type' => 'hidden',
               'name' => 'profile',
               'value' => $profiles_id
            ];
         } else {
            $add_form['content'][sprintf(__('%1$s - %2$s'), 'OcsinventoryNG', $profile->fields["name"])]['inputs'][_n('Allowed OCSNG server', 'Allowed OCSNG servers', 2, 'ocsinventoryng')] = [
               'type' => 'content',
               'content' => '<div class="alert alert-info">' . __('No available servers', 'ocsinventoryng') . '</div>',
               'col_lg' => 12,
            ];
         }
   
         renderTwigForm($add_form);
      }
   
      if ($nbservers > 0) {
         $list_form = [
            'action' => $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/profile.form.php",
            'method' => 'post',
            'buttons' => [
               Session::haveRight("profile", UPDATE) ? [
                  'name' => 'delete',
                  'value' => _sx('button', 'Delete selected'),
                  'type' => 'submit',
                  'class' => 'btn btn-danger',
               ] : []
            ],
            'content' => [
               __('Assigned servers', 'ocsinventoryng') => [
                  'visible' => true,
                  'inputs' => $this->buildAssignedServersInputs($assigned_servers, $profiles_id)
               ]
            ]
         ];
   
         renderTwigForm($list_form);
      } else {
         echo '<div class="card mt-3">';
         echo '<div class="card-header"><h4>' . __('Assigned servers', 'ocsinventoryng') . '</h4></div>';
         echo '<div class="card-body">';
         echo '<p>' . __('No assigned servers', 'ocsinventoryng') . '</p>';
         echo '</div>';
         echo '</div>';
      }
   
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) && $openform) {
         $rights = $this->getAllRights();
   
         $rights_form = [
            'action' => $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/profile.form.php",
            'method' => 'post',
            'buttons' => [
               $canedit && $closeform ? [
                  'name' => 'update_rights',
                  'value' => _sx('button', 'Save'),
                  'type' => 'submit',
                  'class' => 'btn btn-secondary',
               ] : []
            ],
            'content' => [
               __('General') => [
                  'visible' => true,
                  'inputs' => $this->convertRightsToTwigInputs($rights, $canedit, $profiles_id)
               ]
            ]
         ];
   
         renderTwigForm($rights_form);
      }
   }
   
   private function buildAssignedServersInputs($assigned_servers, $profiles_id) {
      $inputs = [];
      
      $inputs[''] = [
         'type' => 'hidden',
         'name' => 'profile',
         'value' => $profiles_id
      ];
   
      foreach ($assigned_servers as $server) {
         $inputs[$server['name']] = [
            'type' => 'checkbox',
            'name' => 'item[' . $server['id'] . ']',
            'value' => 1,
            'col_lg' => 12,
         ];
      }
   
      return $inputs;
   }
   

   private function convertRightsToTwigInputs($rights, $canedit, $profiles_id) {
      global $DB;
      
      $inputs = [];
      
      $profile_rights = [];
      $query = "SELECT * FROM `glpi_profilerights` WHERE `profiles_id` = " . intval($profiles_id);
      $result = $DB->query($query);
      while ($data = $DB->fetchAssoc($result)) {
         $profile_rights[$data['name']] = $data['rights'];
      }
      
      $inputs['profile_id_hidden'] = [
         'type' => 'hidden',
         'name' => 'profiles_id',
         'value' => $profiles_id
      ];
   
      foreach ($rights as $right) {
         $field_name = $right['field'];
         $label = $right['label'];
         $current_value = $profile_rights[$field_name] ?? 0;
         
         if (isset($right['rights'])) {
            $options = [];
            foreach ($right['rights'] as $value => $text) {
               $options[$value] = $text;
            }
            
            $inputs[$label] = [
               'type' => 'select',
               'name' => 'rights[' . $field_name . ']',
               'values' => $options,
               'value' => $current_value,
               'col_lg' => 6,
               'col_md' => 8
            ];
         } else {
            $inputs[$label] = [
               'type' => 'select',
               'name' => 'rights[' . $field_name . ']',
               'values' => [
                  '0' => __('No access'),
                  READ => __('Read'),
                  READ | CREATE => __('Read/Create'),
                  READ | UPDATE => __('Read/Update'), 
                  READ | CREATE | UPDATE => __('Read/Create/Update'),
                  READ | CREATE | UPDATE | PURGE => __('Full access')
               ],
               'value' => $current_value,
               'col_lg' => 6,
               'col_md' => 8
            ];
         }
      }
   
      return $inputs;
   }
   

   public function updateRights($input) {
      global $DB;
      
      if (!isset($input['profiles_id']) || !isset($input['rights'])) {
         return false;
      }
      
      $profiles_id = intval($input['profiles_id']);
      
      if (!Session::haveRight("profile", UPDATE)) {
         return false;
      }
      
      $success = true;
      
      foreach ($input['rights'] as $right_name => $right_value) {
         $existing = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
               'profiles_id' => $profiles_id,
               'name' => $right_name
            ]
         ]);
         
         if (count($existing)) {
            $update_result = $DB->update(
               'glpi_profilerights',
               ['rights' => intval($right_value)],
               [
                  'profiles_id' => $profiles_id,
                  'name' => $right_name
               ]
            );
            
            if (!$update_result) {
               $success = false;
            }
         } else {
            $insert_result = $DB->insert(
               'glpi_profilerights',
               [
                  'profiles_id' => $profiles_id,
                  'name' => $right_name,
                  'rights' => intval($right_value)
               ]
            );
            
            if (!$insert_result) {
               $success = false;
            }
         }
      }
      
      return $success;
   }

   /**
    * @return array
    */
   static function getAllRights() {
      $rights = [['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => _n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng'],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('Manually synchronization', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_sync',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('See information', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_view',
                  'rights'   => [READ => __('Read')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('Clean links between GLPI and OCSNG', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_clean',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('Import computer', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_import',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('Link computer', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_link',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => _n('Rule', 'Rules', 2),
                  'field'    => 'plugin_ocsinventoryng_rule',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]]];
      return $rights;
   }

   /**
    * Init profiles
    *
    * @param $old_right
    *
    * @return int
    */

   static function translateARight($old_right) {
      switch ($old_right) {
         case '':
            return 0;
         case 'r' :
            return READ;
         case 'w':
            return READ + UPDATE;
         case '0':
         case '1':
            return $old_right;

         default :
            return 0;
      }
   }

   /**
    * @since 0.85
    * Migration rights from old system to the new one for one profile
    *
    * @param $profiles_id the profile ID
    *
    * @return bool
    */
   static function migrateOneProfile($profiles_id) {
      global $DB;
      //Cannot launch migration if there's nothing to migrate...
      if (!$DB->tableExists('glpi_plugin_ocsinventoryng_profiles')) {
         return true;
      }

      foreach ($DB->request('glpi_plugin_ocsinventoryng_profiles',
                            "`profiles_id`=$profiles_id") as $profile_data) {

         $matching       = ['ocsng'       => 'plugin_ocsinventoryng',
                            'sync_ocsng'  => 'plugin_ocsinventoryng_sync',
                            'view_ocsng'  => 'plugin_ocsinventoryng_view',
                            'clean_ocsng' => 'plugin_ocsinventoryng_clean',
                            'rule_ocs'    => 'plugin_ocsinventoryng_rule'];
         $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
         foreach ($matching as $old => $new) {
            if (!isset($current_rights[$old])) {
               $query = "UPDATE `glpi_profilerights` 
                         SET `rights` = '" . self::translateARight($profile_data[$old]) . "' 
                         WHERE `name` = '$new' AND `profiles_id` = $profiles_id";
               $DB->query($query);
            }
         }
      }
   }

   /**
    * Initialize profiles, and migrate it necessary
    */
   static function initProfile() {
      global $DB;
      $profile = new self();
      $dbu = new DbUtils();
      //Add new rights in glpi_profilerights table
      foreach ($profile->getAllRights() as $data) {
         if ($dbu->countElementsInTable("glpi_profilerights",
                                  ["name" => $data['field']]) == 0) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }

      //Migration old rights in new ones
      foreach ($DB->request("SELECT `id` FROM `glpi_profiles`") as $prof) {
         self::migrateOneProfile($prof['id']);
      }
      foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "' 
                              AND `name` LIKE '%plugin_ocsinventoryng%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }
   }


   static function removeRightsFromSession() {
      foreach (self::getAllRights() as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
   }

   /**
    * @param $profile
    */
   static function addAllServers($profile) {
      global $DB;

      $profservers = new PluginOcsinventoryngOcsserver_Profile();

      $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                ON (`glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id`
                         = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                     AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id` = " . $profile . ")
              WHERE `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`id` IS NULL
                    AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active` = 1";

      foreach ($DB->request($query) as $data) {
         $input['plugin_ocsinventoryng_ocsservers_id'] = $data['id'];
         $input['profiles_id']                         = $profile;
         $profservers->add($input);
      }
   }
}
