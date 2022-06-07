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

Session::checkRight("plugin_ocsinventoryng", READ);

$ipd = new PluginOcsinventoryngIpdiscoverOcslinkrework();

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "ocsserver");

if(isset($_POST['submitipdconfignoninv'])) {
    if($_POST['subnet'] != $_POST['id'] && $_POST['subnet'] != $_POST['tag'] && $_POST['tag'] != $_POST['id']) {
        $checkConf = $ipd->checkIfConfExists($_POST['ocsserver_id']);

        if($checkConf > 0) {
            $ipd->updateExistingConf($_POST);
        } else {
            $ipd->insertNewConf($_POST);
        }
        Html::back();
    } else {
        Session::addMessageAfterRedirect(__('Fields cannot be identical'), false, ERROR);
        Html::back();
    }  
}

if(isset($_POST['submitipdconfigidt'])) {
    if($_POST['ocstype'] != '0' && $_POST['glpitype'] != '0') {
        $checkConf = $ipd->checkIfConfIdtExists($_POST['ocsserver_id'], $_POST['ocstype']);

        if($checkConf == 0) {
            $ipd->insertNewIdtConf($_POST);
        }else {
            Session::addMessageAfterRedirect(__('This asset already exists.'), false, ERROR);
        }
        Html::back();
    } else {
        Session::addMessageAfterRedirect(__('OCS Type and GLPI type cannot be empty'), false, ERROR);
        Html::back();
    }
}

if(isset($_GET['delete']) && $_GET['delete'] == 'idt') {
    $ipd->removeIdtConf($_GET['id']);
    Html::back();
}

Html::footer();