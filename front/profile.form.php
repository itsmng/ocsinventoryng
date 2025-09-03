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

Session::checkRight("profile", READ);

$profile = new PluginOcsinventoryngProfile();

if (isset($_POST['addocsserver'])) {
   Session::checkRight("profile", UPDATE);
   
   $profile_id = intval($_POST['profile']);
   $server_id = intval($_POST['plugin_ocsinventoryng_ocsservers_id']);
   
   if ($profile_id > 0 && $server_id > 0) {
      $DB->insert(
         'glpi_plugin_ocsinventoryng_ocsservers_profiles',
         [
            'profiles_id' => $profile_id,
            'plugin_ocsinventoryng_ocsservers_id' => $server_id
         ]
      );
      
      Session::addMessageAfterRedirect(__('Server added successfully', 'ocsinventoryng'));
   }
   
   Html::back();
   
} elseif (isset($_POST['delete'])) {
   Session::checkRight("profile", UPDATE);
   
   $profile_id = intval($_POST['profile']);
   
   if (isset($_POST['item']) && is_array($_POST['item'])) {
      foreach ($_POST['item'] as $id => $value) {
         if ($value == 1) {
            $DB->delete(
               'glpi_plugin_ocsinventoryng_ocsservers_profiles',
               ['id' => intval($id)]
            );
         }
      }
      
      Session::addMessageAfterRedirect(__('Selected servers deleted successfully', 'ocsinventoryng'));
   }
   
   Html::back();
   
} elseif (isset($_POST['update_rights'])) {
   Session::checkRight("profile", UPDATE);
   
   $result = $profile->updateRights($_POST);
   
   if ($result) {
      Session::addMessageAfterRedirect(__('Rights updated successfully', 'ocsinventoryng'));
   } else {
      Session::addMessageAfterRedirect(__('Error updating rights', 'ocsinventoryng'), false, ERROR);
   }
   
   Html::back();
   
} else {
   $profiles_id = 0;
   if (isset($_GET['id'])) {
      $profiles_id = intval($_GET['id']);
   }
   
   Html::header(__('OCS Inventory NG', 'ocsinventoryng'), $_SERVER['PHP_SELF'], "admin", "profile", "ocsinventoryng");
   
   $profile->showForm($profiles_id);
   
   Html::footer();
}
?>