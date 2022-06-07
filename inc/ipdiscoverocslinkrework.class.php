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
 * Class PluginOcsinventoryngIpdiscoverOcslinkrework
 */
class PluginOcsinventoryngIpdiscoverOcslinkrework extends CommonDBTM {
    static $rightname = "plugin_ocsinventoryng";

    static $corr = [
        'locations_id' => "Location",
        'networks_id' => "Network",
        'groups_id_tech' => "Group",
        'groups_id' => "Group",
        'networkequipmenttypes_id' => "NetworkEquipmentType",
    ];

    /**
    * @param int $nb
    *
    * @return string|translated
    */
    static function getTypeName($nb = 0) {
        return __('IPDiscover Import', 'ocsinventoryng');
    }

    /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param $item               CommonGLPI object
    * @param $withtemplate (default 0)
    *
    * @return string|translated
    */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

        if ($item->getType() == "PluginOcsinventoryngOcsServer") {
            if (PluginOcsinventoryngOcsServer::checkOCSconnection($item->getID())
                && PluginOcsinventoryngOcsServer::checkVersion($item->getID())
                && PluginOcsinventoryngOcsServer::checkTraceDeleted($item->getID())) {
                $client  = PluginOcsinventoryngOcsServer::getDBocs($item->getID());
                $ipd    = ($client->getIntConfig('IPDISCOVER') > 0)?true:false;

                if ($ipd) {
                    return __('IPDiscover Import', 'ocsinventoryng');
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

        if ($item->getType() == "PluginOcsinventoryngOcsServer") {
            $conf = new self();
            $conf->ocsFormIPDImportOptions($item->getID());
        }
        return true;
    }

    /**
    * get the OCS types from DB
    *
    * @param type $out array
    */
    static function getOCSTypes() {
        $ocsClient = new PluginOcsinventoryngOcsServer();
        $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
        $query     = "SELECT `devicetype`.`id` , `devicetype`.`name`
                      FROM `devicetype`";
        $result    = $DBOCS->query($query);
        $types = [
            0 => '-----'
        ];

        while ($ent = $DBOCS->fetchAssoc($result)) {
            $types[$ent['name']] = $ent['name'];
        }

        return $types;
    }
    
    /**
     * getItemType
     *
     * @return void
     */
    private function getItemType() {
        return [
            '0' => '-----',
            'Computer' => __('Computer'), 
            'NetworkEquipment' => __('Network device'), 
            'Peripheral' => __('Device'), 
            'Phone' => __('Phone'), 
            'Printer' => __('Printer')
        ];
    }

    /**
    * @param $ID
    *
    * @internal param $withtemplate (default '')
    * @internal param $templateid (default '')
    */
    function ocsFormIPDImportOptions($ID) {
        global $DB;

        $configNonInv = [];

        $linkFields = [
            '0' => '-----',
            'locations_id' => __('Location'),
            'networks_id' => __('Network'),
            'groups_id_tech' => __('Group in charge of the hardware'),
            'groups_id' => __('Group'),
            'networkequipmenttypes_id' => __('Type'),
            'contact' => __('Alternate username'),
            'comment' => __('Comment')
        ];

        foreach($DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', ['plugin_ocsinventoryng_ocsservers_id' => $ID]) as $id => $row) {
            $configNonInv[$row['ocs_type']] = $row['link_field'];
        }

        echo "<div>";
        echo "<form name='formipdconfignoninv' id='formipdconfignoninv' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngIpdiscoverOcslinkrework") . "' method='post'>";
        echo "<table class='tab_cadre_fixe'>\n";

        echo "<tr><th colspan ='4'>";
        echo __('IpDiscover Non-Inventoried Equipment configurations', 'ocsinventoryng');
        echo "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('Network name') . "</td>";
        echo "<td colspan='4'>";
        Dropdown::showFromArray('subnet', $linkFields, ['value' => (isset($configNonInv['subnet'])) ? $configNonInv['subnet'] : 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('ID') . "</td>";
        echo "<td colspan='4'>";
        Dropdown::showFromArray('id', $linkFields, ['value' => (isset($configNonInv['id'])) ? $configNonInv['id'] : 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('TAG') . "</td>";
        echo "<td colspan='4'>";
        Dropdown::showFromArray('tag', $linkFields, ['value' => (isset($configNonInv['tag'])) ? $configNonInv['tag'] : 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td></td>";
        echo "<td>";
        echo "<input type='hidden' name='ocsserver_id' value='".$ID."'>";
        echo "<input type='submit' name='submitipdconfignoninv' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
        echo "</td></tr>";

        echo "</table>\n";
        Html::closeForm();
        echo "</div>";

        $ocsTypes = self::getOCSTypes();

        echo "<div>";
        echo "<form name='formipdconfigidt' id='formipdconfigidt' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngIpdiscoverOcslinkrework") . "' method='post'>";
        echo "<table class='tab_cadre_fixe'>\n";

        echo "<tr><th colspan ='4'>";
        echo __('IpDiscover Identified Equipment configurations', 'ocsinventoryng');
        echo "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('OCS Type', 'ocsinventoryng') . "</td>";
        echo "<td>";
        Dropdown::showFromArray('ocstype', $ocsTypes, []);
        echo "</td>";
        echo "<td class='center'>" . __('GLPI Type', 'ocsinventoryng') . "</td>";
        echo "<td>";
        Dropdown::showFromArray('glpitype', $this->getItemType(), []);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center' colspan ='4'>";
        echo "<input type='hidden' name='ocsserver_id' value='".$ID."'>";
        echo "<input type='submit' name='submitipdconfigidt' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
        echo "</td></tr>";

        echo "</table>\n";
        Html::closeForm();
        echo "</div>";

        self::showIdentifiedEquipmentTable($ID);
    }

    /**
     * @param $ID
     */
    static function showIdentifiedEquipmentTable($ID) {
        global $DB;

        $configIdt = $DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt', ['plugin_ocsinventoryng_ocsservers_id' => $ID]);

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        // Table header
        echo "<tr class='noHover'><th colspan='8'>" . sprintf(__('Linked objects', 'ocsinventoryng'));
        echo "</th></tr>";

        // Fields header
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('OCS Type', 'ocsinventoryng') . "</th>";
        echo "<th>" . __('GLPI Type', 'ocsinventoryng') . "</th>";
        echo "<th>" . __("Actions") . "</th>";
        echo "</tr>";

        // Fields
        if (!count($configIdt)) {
            echo "<tr><td colspan='8'>" .__('empty', 'ocsinventoryng'). "</td></tr>";
        } else {
            foreach ($configIdt as $id => $value) {
                echo "<td>" . $value['ocs_type'] . "</td>";
                echo "<td>" . $value['glpi_obj'] . "</td>";
                echo '<td><a href='.Toolbox::getItemTypeFormURL("PluginOcsinventoryngIpdiscoverOcslinkrework").'?delete=idt&id='.$id.'>'.__('Delete').'</a></td>';
                echo "</tr>";
            }
        }

        // Data
        echo "</table></div>";
    }
    
    /**
     * checkIfConfExists
     *
     * @param  mixed $ID
     * @return void
     */
    public function checkIfConfExists($ID) {
        global $DB;

        $req = $DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', ['plugin_ocsinventoryng_ocsservers_id' => $ID]);
        return count($req);
    }
    
    /**
     * checkIfConfIdtExists
     *
     * @param  mixed $ID
     * @param  mixed $ocstype
     * @return void
     */
    public function checkIfConfIdtExists($ID, $ocstype) {
        global $DB;

        $req = $DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt', ['plugin_ocsinventoryng_ocsservers_id' => $ID, 'ocs_type' => $ocstype]);
        return count($req);
    }
    
    /**
     * insertNewIdtConf
     *
     * @param  mixed $post
     * @return void
     */
    public function insertNewIdtConf($post) {
        global $DB;

        $DB->insert(
            'glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt', [
               'ocs_type' => $post['ocstype'],
               'glpi_obj' => $post['glpitype'],
               'plugin_ocsinventoryng_ocsservers_id' => $post['ocsserver_id']
            ]
        );
    }
    
    /**
     * insertNewConf
     *
     * @param  mixed $post
     * @return void
     */
    public function insertNewConf($post) {
        global $DB;

        // SUBNET
        $DB->insert(
            'glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', [
               'ocs_type' => 'subnet',
               'link_field' => $post['subnet'],
               'plugin_ocsinventoryng_ocsservers_id' => $post['ocsserver_id']
            ]
        );

        // ID
        $DB->insert(
            'glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', [
               'ocs_type' => 'id',
               'link_field' => $post['id'],
               'plugin_ocsinventoryng_ocsservers_id' => $post['ocsserver_id']
            ]
        );

        // TAG
        $DB->insert(
            'glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', [
               'ocs_type' => 'tag',
               'link_field' => $post['tag'],
               'plugin_ocsinventoryng_ocsservers_id' => $post['ocsserver_id']
            ]
        );
    }
    
    /**
     * updateExistingConf
     *
     * @param  mixed $post
     * @return void
     */
    public function updateExistingConf($post) {
        global $DB;

        // SUBNET
        $DB->update(
            'glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', [
               'link_field'  => $post['subnet'],
            ], [
               'plugin_ocsinventoryng_ocsservers_id' => $post['ocsserver_id'],
               'ocs_type' => 'subnet',
            ]
        );

        // ID
        $DB->update(
            'glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', [
               'link_field'  => $post['id'],
            ], [
               'plugin_ocsinventoryng_ocsservers_id' => $post['ocsserver_id'],
               'ocs_type' => 'id',
            ]
        );

        // TAG
        $DB->update(
            'glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', [
               'link_field'  => $post['tag'],
            ], [
               'plugin_ocsinventoryng_ocsservers_id' => $post['ocsserver_id'],
               'ocs_type' => 'tag',
            ]
        );
    }
    
    /**
     * removeIdtConf
     *
     * @param  mixed $id
     * @return void
     */
    public function removeIdtConf($id) {
        global $DB;
        
        $DB->delete(
            'glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt', [
               'id' => $id
            ]
        );
    }
    
    /**
     * associateGlpiType
     *
     * @param  mixed $ocstypes
     * @return void
     */
    public function associateGlpiType($ocstypes) {
        global $DB;

        if(is_array($ocstypes)) {
            foreach($ocstypes as $key => $value) {
                $req = $DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt', ['ocs_type' => $value]);
                if ($row = $req->next()) {
                    $types[$key] = $row['glpi_obj'];
                } else {
                    $types[$key] = 'NetworkEquipment';
                }
            }
        } else {
            $req = $DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt', ['ocs_type' => $ocstypes]);
            if ($row = $req->next()) {
                $types = $row['glpi_obj'];
            } else {
                $types = 'NetworkEquipment';
            }
        }
      
        return $types;
    }
    
    /**
     * importIpDiscover
     *
     * @param  mixed $ipdDatas
     * @param  mixed $ocsServerID
     * @param  mixed $params
     * @return void
     */
    static function importIpDiscover($ipdDatas, $ocsServerID, $params = []) {
        global $DB;

        $configNonInv = [];
        $configIdent = [];
        // Get IpDiscover config non-ident
        foreach($DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', ['plugin_ocsinventoryng_ocsservers_id' => $ocsServerID]) as $value) {
            $configNonInv[$value["ocs_type"]] = $value["link_field"];
        }

        // Get Ipdiscover config ident
        foreach($DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt', ['plugin_ocsinventoryng_ocsservers_id' => $ocsServerID]) as $value) {
            $configIdent[$value["ocs_type"]] = $value["glpi_obj"];
        }

        // Create a new element status for NetworkEquipment ipdiscover inventory
        $state = new State();
        $verifSateNonInv = $state->find(['name' => 'OCSNG - Non-Inventoried']);

        if(count($verifSateNonInv) == 0) {
            $state->add([
                "name" => "OCSNG - Non-Inventoried",
                "entities_id" => 0,
                "is_recursive" => 0,
                "states_id" => 0,
                "completename" => "OCSNG - Non-Inventoried",
                "comment" => "OCSNG IpDIscover Non-Inventoried status",
            ]);
        }

        foreach($state->find(['name' => 'OCSNG - Non-Inventoried']) as $id) {
            $stateid = $id['id'];
        }

        $verifSateIdent = $state->find(['name' => 'OCSNG - Identified']);

        if(count($verifSateIdent) == 0) {
            $state->add([
                "name" => "OCSNG - Identified",
                "entities_id" => 0,
                "is_recursive" => 0,
                "states_id" => 0,
                "completename" => "OCSNG - Identified",
                "comment" => "OCSNG IpDIscover Identified status",
            ]);
        }

        foreach($state->find(['name' => 'OCSNG - Identified']) as $id) {
            $stateidentid = $id['id'];
        }

        if($params['inventory_type'] == "noninventoried") {
            $status = self::importNonInventoried($ipdDatas, $ocsServerID, $stateid, $configNonInv);
        } elseif($params['inventory_type'] == "identified") {
            $status = self::importIdentified($ipdDatas, $ocsServerID, $configNonInv, $configIdent, $stateidentid);
        } else {
            $status = ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_NOTUPDATED];
        }

        return $status;
    }
    
    /**
     * importIdentified
     *
     * @param  mixed $ipdDatas
     * @param  mixed $ocsServerID
     * @param  mixed $configNonInv
     * @param  mixed $configIdent
     * @return void
     */
    static function importIdentified($ipdDatas, $ocsServerID, $configNonInv, $configIdent, $stateidentid) {
        global $DB;

        foreach($ipdDatas as $key => $value) {
            if(array_key_exists($key, $configNonInv) && $configNonInv[$key] != "0") {
                if(array_key_exists($configNonInv[$key], self::$corr) && trim($value) != "") {
                    $obj = new self::$corr[$configNonInv[$key]]();
                    $verif = $obj->find(["name" => $value]);
                    if(count($verif) == 0) {
                        $obj->add([
                            "name" => $value
                        ]);
                    }
                    foreach($obj->find(["name" => $value]) as $id) {
                        $objId[$configNonInv[$key]] = $id['id'];
                    }
                }
                if(in_array($configNonInv[$key], array('comment', 'contact'))) {
                    $objId[$configNonInv[$key]] = $value;
                }
            }
        }
        
        if(isset($configIdent[$ipdDatas['type']])) {
            $Equipment = new $configIdent[$ipdDatas['type']]();
            $className = $configIdent[$ipdDatas['type']];
        } else {
            $Equipment = new NetworkEquipment();
            $className = 'NetworkEquipment';
        }

        $input = [
            'is_dynamic'   => 1,
            'locations_id' => 0,
            'entities_id'  => 0,
            'name'         => $ipdDatas['description'],
            'comment'      => '',
            'contact'      => $ipdDatas['user'],
            'states_id'    => $stateidentid
        ];
        
        if(isset($objId)) {
            foreach($objId as $column => $data) {
                $input[$column] = $data;
            }
        }

        $equipment = $Equipment->add($input);

        $NetworkPort = new NetworkPort();

        $port_input = [
            'name'                     => $ipdDatas['description'],
            'mac'                      => (strpos($ipdDatas['mac'], '.') !== false) ? 'ff:ff:ff:ff:ff:ff' : $ipdDatas['mac'],
            'items_id'                 => $equipment,
            'itemtype'                 => $className,
            'instantiation_type'       => "NetworkPortEthernet",
            "entities_id"              => 0,
            "NetworkName_name"         => ($ipdDatas['subnet'] != "" && !is_null($ipdDatas['subnet'])) ? str_replace(" ", "", $ipdDatas['subnet']) : $ipdDatas['ip'],
            "NetworkName__ipaddresses" => ["-100" => $ipdDatas['ip']],
            '_create_children'         => 1,
            'is_deleted'               => 0,
            'comment'                  => (strpos($ipdDatas['mac'], '.') !== false) ? 'Remote scanned device from ipdiscover' : '',
        ];

        $NetworkPort->add($port_input);

        $mac = $ipdDatas['mac'];
        $date = $ipdDatas['date'];
        $subnet = $ipdDatas['netid'];

        $glpiQuery = "INSERT INTO `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
            (`items_id`,`itemtype`,`macaddress`,`last_update`,`subnet`,`plugin_ocsinventoryng_ocsservers_id`, `status`)
            VALUES($equipment,'$className','$mac','$date','$subnet',$ocsServerID, 'identified')";

        $check = $DB->query($glpiQuery);

        if(!$check) {
            return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_FAILED_IMPORT];
        }
        return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_IMPORTED];
        
    }
    
    /**
     * importNonInventoried
     *
     * @param  mixed $ipdDatas
     * @param  mixed $ocsServerID
     * @param  mixed $stateid
     * @param  mixed $configNonInv
     * @return void
     */
    static function importNonInventoried($ipdDatas, $ocsServerID, $stateid, $configNonInv) {
        global $DB;

        foreach($ipdDatas as $key => $value) {
            if(array_key_exists($key, $configNonInv) && $configNonInv[$key] != "0") {
                if(array_key_exists($configNonInv[$key], self::$corr) && trim($value) != "") {
                    $obj = new self::$corr[$configNonInv[$key]]();
                    $verif = $obj->find(["name" => $value]);
                    if(count($verif) == 0) {
                        $obj->add([
                            "name" => $value
                        ]);
                    }
                    foreach($obj->find(["name" => $value]) as $id) {
                        $objId[$configNonInv[$key]] = $id['id'];
                    }
                }
            }
        }
        
        $NetworkEquipment = new NetworkEquipment();
        
        $input = [
            'is_dynamic'   => 1,
            'locations_id' => 0,
            'entities_id'  => 0,
            'name'         => $ipdDatas['ip'],
            'comment'      => '',
            'states_id'    => $stateid,
        ];
        
        if(isset($objId)) {
            foreach($objId as $column => $data) {
                $input[$column] = $data;
            }
        }

        $equipment = $NetworkEquipment->add($input);

        $NetworkPort = new NetworkPort();

        $port_input = [
            'name'                     => $ipdDatas['ip'],
            'mac'                      => (strpos($ipdDatas['mac'], '.') !== false) ? 'ff:ff:ff:ff:ff:ff' : $ipdDatas['mac'],
            'items_id'                 => $equipment,
            'itemtype'                 => "NetworkEquipment",
            'instantiation_type'       => "NetworkPortEthernet",
            "entities_id"              => 0,
            "NetworkName_name"         => ($ipdDatas['subnet'] != "" && !is_null($ipdDatas['subnet'])) ? str_replace(" ", "", $ipdDatas['subnet']) : $ipdDatas['ip'],
            "NetworkName__ipaddresses" => ["-100" => $ipdDatas['ip']],
            '_create_children'         => 1,
            'is_deleted'               => 0,
            'comment'                  => (strpos($ipdDatas['mac'], '.') !== false) ? 'Remote scanned device from ipdiscover' : '',
        ];

        $networkPortId = $NetworkPort->add($port_input);

        $mac = $ipdDatas['mac'];
        $date = $ipdDatas['date'];
        $subnet = $ipdDatas['netid'];

        $glpiQuery = "INSERT INTO `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
            (`items_id`,`itemtype`,`macaddress`,`last_update`,`subnet`,`plugin_ocsinventoryng_ocsservers_id`, `status`)
            VALUES($equipment,'NetworkEquipment','$mac','$date','$subnet',$ocsServerID, 'noninventoried')";

        $check = $DB->query($glpiQuery);

        if(!$check) {
            return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_FAILED_IMPORT];
        }
        return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_IMPORTED];
    }
    
    /**
     * removeIpDiscover
     *
     * @param  mixed $mac
     * @param  mixed $ocsServerID
     * @return void
     */
    static function removeIpDiscover($mac, $ocsServerID) {
        global $DB;

        $status = false;

        foreach($DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinks', ['plugin_ocsinventoryng_ocsservers_id' => $ocsServerID, 'macaddress' => $mac]) as $value) {
            $equipment = new $value['itemtype']();
            $status = $equipment->delete(['id' => $value['items_id']]);
            if(!$status) {
                return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_FAILED_REMOVE];
            }
            $status = $DB->delete('glpi_plugin_ocsinventoryng_ipdiscoverocslinks', ['id' => $value['id']]);
        }

        if(!$status) {
            return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_FAILED_REMOVE];
        }
        return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_REMOVED];
    }
    
    /**
     * updateIpDiscover
     *
     * @param  mixed $ipdDatas
     * @param  mixed $ocsServerId
     * @param  mixed $params
     * @return void
     */
    static function updateIpDiscover($ipdDatas, $ocsServerId, $params = []) {
        global $DB;

        $configNonInv = [];
        $configIdent = [];
        // Get IpDiscover config non-ident
        foreach($DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv', ['plugin_ocsinventoryng_ocsservers_id' => $ocsServerId]) as $value) {
            $configNonInv[$value["ocs_type"]] = $value["link_field"];
        }

        foreach($ipdDatas as $key => $value) {
            if(array_key_exists($key, $configNonInv) && $configNonInv[$key] != "0") {
                if(array_key_exists($configNonInv[$key], self::$corr) && trim($value) != "") {
                    $obj = new self::$corr[$configNonInv[$key]]();
                    $verif = $obj->find(["name" => $value]);
                    if(count($verif) == 0) {
                        $obj->add([
                            "name" => $value
                        ]);
                    }
                    foreach($obj->find(["name" => $value]) as $id) {
                        $objId[$configNonInv[$key]] = $id['id'];
                    }
                }
            }
        }

        $req = $DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinks', ['plugin_ocsinventoryng_ocsservers_id' => $ocsServerId, 'macaddress' => $ipdDatas['mac']]);

        if ($ipd = $req->next()) {
            $equipment = new $ipd['itemtype']();

            $input = [
                'id'           => $ipd['items_id'],
                'is_dynamic'   => 1,
                'locations_id' => 0,
                'entities_id'  => 0,
                'name'         => (isset($ipdDatas['description']) && $ipdDatas['description'] != "") ? $ipdDatas['description'] : $ipdDatas['ip'],
                'comment'      => '',
                'contact'      => (isset($ipdDatas['user'])) ? $ipdDatas['user'] : '',
            ];

            if(isset($objId)) {
                foreach($objId as $column => $data) {
                    $input[$column] = $data;
                }
            }

            $result = $equipment->update($input);

            if(!$result) {
                return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_NOTUPDATED];
            }

            $reqUpdate = $DB->update(
                'glpi_plugin_ocsinventoryng_ipdiscoverocslinks', [
                   'subnet'      => $ipdDatas['netid'],
                   'last_update'  => $ipdDatas['date']
                ], [
                   'id' => $ipd['id']
                ]
            );
        }

        return ['status' => PluginOcsinventoryngOcsProcess::IPDISCOVER_SYNCHRONIZED];
    }

    /**
    * parse array with ip or mac into one string
    *
    * @param array|type $array
    *
    * @return string $
    */
    public static function parseArrayToString($array = []) {
        $token = "";

        if (sizeof($array) == 0) {
            return "''";
        }

        for ($i = 0; $i < sizeof($array); $i++) {
            if ($i == sizeof($array) - 1) {
                $token .= "'" . $array[$i] . "'";
            } else {
                $token .= "'" . $array[$i] . "'" . ",";
            }
        }

        return $token;
    }

    /**
    * @param $ipAdress
    *
    * @return int|mixed
    */
    static function getSubnetIDbyIP($ipAdress) {

        $subnet    = -1;
        $ocsClient = new PluginOcsinventoryngOcsServer();
        $ocsdb     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $OCSDB     = $ocsdb->getDB();

        $query  = "SELECT *
            FROM subnet
            WHERE `subnet`.`NETID` = '$ipAdress'";

        $result = $OCSDB->query($query);
        if ($result->num_rows > 0) {
            $res    = $OCSDB->fetchAssoc($result);
            $tab    = $_SESSION["subnets"];
            $subnet = array_search($res["ID"], $tab);
        }

        return $subnet;
    }

    /**
    * get the mac adresses in glpi_plugin_ocsinventoryng_ipdiscoverocslinks table
    * @global type $DB
    * @return array with mac addresses
    */
    static function getKnownMacAdresseFromGlpi($status = null) {
        global $DB;

        $macAdresses = [];

        $query = "SELECT `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`.`macaddress`
            FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`";

        if(!is_null($status)) {
            $query .= " WHERE `status` = '$status'";
        }

        $result      = $DB->query($query);

        while ($res = $DB->fetchAssoc($result)) {
            $macAdresses[] = $res["macaddress"];
        }

        return $macAdresses;
    }

    /**
    * @param $array
    * @param $key
    * @param $val
    *
    * @return bool
    */
    static function findInArray($array, $key, $val) {
        foreach ($array as $item) {
            if (isset($item[$key]) && $item[$key] == $val) {
                return true;
            }
        }

        return false;
    }

    /**
    * this function get datas on an certain ipaddress
    *
    * @param type       $ipAdress string
    * @param type       $plugin_ocsinventoryng_ocsservers_id string
    * @param type       $status string
    * @param array|type $knownMacAdresses array
    *
    * @return array
    */
    static function getHardware($ipAdress, $plugin_ocsinventoryng_ocsservers_id, $status, $knownMacAdresses = []) {
        global $DB;

        $ocsClient = new PluginOcsinventoryngOcsServer();
        $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();
        $query     = "";

        switch($status) {
            case "inventoried":
                $query = "SELECT `hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain` 
                    FROM `hardware` 
                    LEFT JOIN `networks` ON `networks`.`hardware_id`=`hardware`.`id` 
                    WHERE `networks`.`ipsubnet`='$ipAdress' 
                    AND status='Up' 
                    GROUP BY `hardware`.`id`,`hardware`.`lastdate`, `hardware`.`name`, `hardware`.`userid`, `hardware`.`osname`, `hardware`.`workgroup`, `hardware`.`osversion`, `hardware`.`ipaddr`, `hardware`.`userdomain`
                    ORDER BY `hardware`.`lastdate`";
                break;
            
            case "nonimported":
                $macAdresses = self::parseArrayToString($knownMacAdresses);

                $query = "SELECT `netmap`.`ip`, `netmap`.`mac`, `netmap`.`mask`, `netmap`.`date`, `netmap`.`name` as DNS
                    FROM `netmap` 
                    LEFT JOIN `networks` 
                    ON `netmap`.`mac` =`networks`.`macaddr` 
                    WHERE `netmap`.`netid`='$ipAdress' 
                    AND (`networks`.`macaddr` IS NULL OR `networks`.`ipsubnet` <> `netmap`.`netid`) 
                    AND `netmap`.`mac` NOT IN ( SELECT DISTINCT(`network_devices`.`macaddr`) FROM `network_devices`)";

                    if($macAdresses != "") {
                        $query .= " AND `netmap`.`mac` NOT IN ($macAdresses)";
                    }

                    $query .= " GROUP BY `netmap`.`mac`,`netmap`.`ip`,`netmap`.`mask`, `netmap`.`date`, `netmap`.`name`
                    ORDER BY `netmap`.`date` DESC";
                break;

            case "identified":
                $query = "SELECT *
                    FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks` 
                    WHERE `subnet` = '$ipAdress' AND `status`= 'identified'
                    ORDER BY `last_update`";
                break;

            case "noninventoried":
                $query = "SELECT *
                    FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks` 
                    WHERE `subnet` = '$ipAdress' AND `status`= 'noninventoried'
                    ORDER BY `last_update`";
                break;
        }

        if ($status == "identified" || $status == "noninventoried") {
            $result   = $DB->query($query);
            $hardware = [];
            while ($res = $DBOCS->fetchAssoc($result)) {
                if (!isset($res["mac"])) {
                    $hardware[] = $res;
                } else if (!self::findInArray($hardware, "mac", $res["mac"])) {
                    $hardware[] = $res;
                }
            }
        } else {
            $result   = $DBOCS->query($query);
            $hardware = [];

            while ($res = $DBOCS->fetchAssoc($result)) {
                if (!isset($res["mac"])) {
                    $hardware[] = $res;
                } else if (!self::findInArray($hardware, "mac", $res["mac"])) {
                    $hardware[] = $res;
                }
            }
        }

        return $hardware;
    }
    
    /**
     * getStatusName
     *
     * @param  mixed $status
     * @return void
     */
    static function getStatusName($status) {
        switch($status) {
            case "inventoried":
                return __('Inventoried', 'ocsinventoryng');
            case "nonimported":
                return __('Non Imported', 'ocsinventoryng');
            case "noninventoried":
                return __('Non Inventoried', 'ocsinventoryng');
            case "identified":
                return __('Identified', 'ocsinventoryng');
        }
    }
    
    /**
     * getSubnetNamebyIP
     *
     * @param  mixed $ipAdress
     * @return void
     */
    static function getSubnetNamebyIP($ipAdress) {
        $name      = "";
        $ocsClient = new PluginOcsinventoryngOcsServer();
        $ocsdb     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $OCSDB     = $ocsdb->getDB();

        $query  = "SELECT *
            FROM `subnet`
            WHERE `subnet`.`NETID` = '$ipAdress'";

        $result = $OCSDB->query($query);
        
        if ($result->num_rows > 0) {
            $res  = $OCSDB->fetchAssoc($result);
            $name = $res["NAME"];
        }

        return $name;
    }
    
    /**
     * checkBox
     *
     * @param  mixed $target
     * @return void
     */
    static function checkBox($target) {
        echo "<div class='center'><a href='".$target."?check=all' onclick= \"if(markCheckboxes('ipdiscover_form')) return false;\">".__('Check all')."</a>&nbsp;/&nbsp;\n";
        echo "<a href='".$target."?check=none' onclick= \"if(unMarkCheckboxes('ipdiscover_form')) return false;\">".__('Uncheck all')."</a></div>\n";
    }
    
    /**
     * showItem
     *
     * @param  mixed $value
     * @param  mixed $linkto
     * @param  mixed $id
     * @param  mixed $type
     * @param  mixed $checkbox
     * @param  mixed $check
     * @param  mixed $iterator
     * @return void
     */
    static function showItem($value, $linkto = "", $id = "", $type = "", $checkbox = false, $check = "", $iterator = 0) {
        $out = "<td>";
        if ($checkbox) {
            $out .= "<input type='checkbox' name='mactoimport[$iterator][" . $value . "]' ".($check == "all" ? "checked" : "").">\n";
            $out .= "</td>\n";
            return $out;
        } else {
            if (!empty($linkto)) {
                $out .= "<a href=\"$linkto" . "?ip=$id&status=$type\">";
            }
            $out .= $value;
            if (!empty($linkto)) {
                $out .= "</a>";
            }
            $out .= "</td>\n";

            return $out;
        }
    }
     
    /**
     * getOCSTypesForm
     *
     * @param  mixed $out
     * @return void
     */
    static function getOCSTypesForm(&$out) {
        $ocsClient = new PluginOcsinventoryngOcsServer();
        $DBOCS     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getDB();
        $query     = "SELECT `devicetype`.`id` , `devicetype`.`name` FROM `devicetype`";
        $result    = $DBOCS->query($query);
  
        while ($ent = $DBOCS->fetchAssoc($result)) {
           $out["id"][]   = $ent["id"];
           $out["name"][] = $ent["name"];
        }
    }     
    
    /**
     * showHardware
     *
     * @param  mixed $hardware
     * @param  mixed $lim
     * @param  mixed $start
     * @param  mixed $ipAdress
     * @param  mixed $status
     * @param  mixed $subnet
     * @param  mixed $action
     * @return void
     */
    static function showHardware($hardware, $lim, $start, $ipAdress, $status, $subnet, $action) {
        global $CFG_GLPI, $DB;

        $output_type = Search::HTML_OUTPUT; //0
        $link        = $CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/ipdiscoverrework.import.php";
        $return      = $CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/ipdiscover.php";
        $reload      = "ip=$ipAdress&status=$status&action=$action";
        $refresh     = $CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/ipdiscoverrework.import.php?".$reload;
        $returnargs  = "subnetsChoice=$subnet&action=$action";
        $backValues  = "?b[]=$ipAdress&b[]=$status";

        $status_name = self::getStatusName($status);
        $subnet_name = self::getSubnetNamebyIP($ipAdress);

        echo "<div class='center'>";
        echo "<h2>".__('Subnet')." ".$subnet_name." (".$ipAdress.") - ".$status_name;
        echo "&nbsp;&nbsp;";
        Html::showSimpleForm($refresh, 'refresh', _sx('button', 'Refresh'), [], "fa-sync-alt fa-2x");
        echo "</h2>";
        echo "</div>";

        if ($subnet >= 0) {
            $back = __('Back');
            echo "<div class='center'><a href='$return?$returnargs'>$back</div>";
        }

        echo Html::printPager($start, count($hardware), $link, $reload);
        echo Search::showNewLine($output_type, true);

        if (empty($hardware)) {
            echo "<div class='center b'><br>" . __('No new IPDiscover device to import', 'ocsinventoryng') . "</div>";
            Html::displayBackLink();
        } else {
            $header_num = 1;
            switch ($status) {
                case "inventoried":
                    self::displayInventoriedTable($hardware, $output_type, $start, $lim);
                    break;
                case "identified":
                    self::displayIdentifiedTable($hardware, $output_type, $start, $lim, $backValues, $ipAdress);
                    break;
                case "noninventoried":
                    self::displayNonInventoriedTable($hardware, $output_type, $start, $lim, $backValues, $ipAdress);
                    break;
                case "nonimported":
                    self::displayNonImportedTable($hardware, $output_type, $start, $lim, $backValues, $ipAdress);
                    break;
            }
        }
    }

    static function displayNonImportedTable($hardware, $output_type, $start, $lim, $backValues, $ipAdress) {
        global $DB, $CFG_GLPI;

        $ocsTypes       = self::getOCSTypes();
        $link           = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
        $target         = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscoverrework.import.php" . $backValues;
        $macConstructor = "";

        self::checkBox($target);

        echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
        echo "<div class='center' style=\"width=100%\">";

        echo Html::submit(_sx('button', 'Import'), ['name' => 'IdentifyAndImport']);
        echo "&nbsp;";
        echo Html::submit(_sx('button', 'Delete from OCSNG', 'ocsinventoryng'), ['name' => 'delete']);
        echo "</div>";

        echo "<table width='100%'class='tab_cadrehov'>\n";
        echo Search::showHeaderItem($output_type, __('Date'), $header_num);
        echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
        echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
        echo Search::showHeaderItem($output_type, __('Subnet mask'), $header_num);
        echo Search::showHeaderItem($output_type, __('DNS', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('Description')."<span class='red'>*</span>", $header_num);
        echo Search::showHeaderItem($output_type, __('OCS Type', 'ocsinventoryng')."<span class='red'>*</span>", $header_num);
        echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
        echo Search::showEndLine($output_type);

        $row_num  = 1;

        for ($i = $start; $i < $lim + $start; $i++) {
            if (isset($hardware[$i])) {
                $row_num++;
                echo Search::showNewLine($output_type, $row_num % 2);
                echo self::showItem(Html::convDateTime($hardware[$i]["date"]));

                if (isset($_SESSION["OCS"]["IpdiscoverMacConstructors"])) {
                    $macs = unserialize($_SESSION["OCS"]["IpdiscoverMacConstructors"]);
                    if (isset($macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))])) {
                        $macConstructor = $macs[mb_strtoupper(substr($hardware[$i]["mac"], 0, 8))];
                    } else {
                        $macConstructor = __("unknow");
                    }
                }

                $mac = $hardware[$i]["mac"] . "<small> ( " . $macConstructor . " )</small>";
                echo self::showItem($mac);
                echo self::showItem($ip = $hardware[$i]["ip"]);
                echo self::showItem($hardware[$i]["mask"]);
                echo self::showItem($hardware[$i]["DNS"]);
                echo "<td>";
                echo Html::input("itemsdescription[" . $i . "]", ['type'     => 'text']);
                echo "</td>";
                echo "<td>";
                Dropdown::showFromArray("ocsitemstype[$i]", $ocsTypes);
                echo "</td>"; 
                echo self::showItem($hardware[$i]["mac"], "", "", "", true, "", $i);
                echo "<tbody style=\"display:none\">";
                echo "<tr><td>";
                echo Html::hidden("itemsip[" . $i . "]", ['value' => $ip]);
                echo Html::hidden("subnet", ['value' => $ipAdress]);
                echo "</td></tr>";
                echo "</tbody>";
            }
        }

        echo "</table>\n";
        echo "<div class='center' style=\"width=100%\">";
        echo Html::submit(_sx('button', 'Import'), ['name' => 'IdentifyAndImport']);
        echo "&nbsp;";
        echo Html::submit(_sx('button', 'Delete from OCSNG', 'ocsinventoryng'), ['name' => 'delete']);
        echo "</div>";
        Html::closeForm();
        self::checkBox($target);
    }
    
    /**
     * displayNonInventoriedTable
     *
     * @param  mixed $hardware
     * @param  mixed $output_type
     * @param  mixed $start
     * @param  mixed $lim
     * @param  mixed $backValues
     * @param  mixed $ipAdress
     * @return void
     */
    static function displayNonInventoriedTable($hardware, $output_type, $start, $lim, $backValues, $ipAdress) {
        global $DB, $CFG_GLPI;

        $ocsTypes       = self::getOCSTypes();
        $link           = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscover.php";
        $target         = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscoverrework.import.php" . $backValues;
        $macConstructor = "";

        self::checkBox($target);

        echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
        echo "<div class='center' style=\"width=100%\">";

        echo Html::submit(_sx('button', 'Import'), ['name' => 'IdentifyAndImport']);
        echo "&nbsp;";
        echo Html::submit(_sx('button', 'Delete from OCSNG', 'ocsinventoryng'), ['name' => 'delete']);
        echo "</div>";

        echo "<table width='100%'class='tab_cadrehov'>\n";
        echo Search::showHeaderItem($output_type, __('Item'), $header_num);
        echo Search::showHeaderItem($output_type, __('Item type'), $header_num);
        echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
        echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
        echo Search::showHeaderItem($output_type, __('Location'), $header_num);
        echo Search::showHeaderItem($output_type, __('Import date in GLPI', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('Subnet'), $header_num);
        echo Search::showHeaderItem($output_type, __('Description')."<span class='red'>*</span>", $header_num);
        echo Search::showHeaderItem($output_type, __('OCS Type', 'ocsinventoryng')."<span class='red'>*</span>", $header_num);
        echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
        echo Search::showEndLine($output_type);

        $row_num = 1;

        for ($i = $start; $i < $lim + $start; $i++) {
            if (isset($hardware[$i])) {
                $row_num++;
                $item_num = 1;
                echo Search::showNewLine($output_type, $row_num % 2);
                $dbu = new DbUtils();
                $class = $dbu->getItemForItemtype($hardware[$i]["itemtype"]);
                $class->getFromDB($hardware[$i]["items_id"]);
                $iplist = "";
                $ip     = new IPAddress();
                // Update IPAddress
                foreach ($DB->request('glpi_networkports', ['itemtype' => $hardware[$i]["itemtype"], 'items_id' => $hardware[$i]["items_id"]]) as $netname) {
                    foreach ($DB->request('glpi_networknames', ['itemtype' => 'NetworkPort', 'items_id' => $netname['id']]) as $dataname) {
                        foreach ($DB->request('glpi_ipaddresses', ['itemtype' => 'NetworkName', 'items_id' => $dataname['id']]) as $data) {
                            $ip->getFromDB($data['id']);
                            $iplist .= $ip->getName() . "<br>";
                        }
                    }
                }
                echo Search::showItem($output_type, $class->getLink(), $item_num, $row_num);
                echo Search::showItem($output_type, $class->getTypeName(), $item_num, $row_num);
                echo Search::showItem($output_type, $hardware[$i]["macaddress"], $item_num, $row_num);
                echo Search::showItem($output_type, $iplist, $item_num, $row_num);
                echo Search::showItem($output_type, Dropdown::getDropdownName("glpi_locations", $class->fields["locations_id"]), $item_num, $row_num);
                echo Search::showItem($output_type, Html::convDateTime($hardware[$i]["last_update"]), $item_num, $row_num);
                echo Search::showItem($output_type, $hardware[$i]["subnet"], $item_num, $row_num);
                echo "<td>";
                echo Html::input("itemsdescription[" . $i . "]", ['type'     => 'text']);
                echo "</td>";
                echo "<td>";
                Dropdown::showFromArray("ocsitemstype[$i]", $ocsTypes);
                echo "</td>";
                echo self::showItem($hardware[$i]["id"], "", "", "", true, "", $i);
                echo Search::showEndLine($output_type);
            }
        }

        echo "</table>\n";
        echo "<div class='center' style=\"width=100%\">";
        echo Html::submit(_sx('button', 'Import'), ['name' => 'IdentifyAndImport']);
        echo "&nbsp;";
        echo Html::submit(_sx('button', 'Delete from OCSNG', 'ocsinventoryng'), ['name' => 'delete']);
        echo "</div>";
        Html::closeForm();
        self::checkBox($target);
    }
    
    /**
     * displayIdentifiedTable
     *
     * @param  mixed $hardware
     * @param  mixed $output_type
     * @param  mixed $start
     * @param  mixed $lim
     * @param  mixed $backValues
     * @param  mixed $ipAdress
     * @return void
     */
    static function displayIdentifiedTable($hardware, $output_type, $start, $lim, $backValues, $ipAdress) {
        global $DB, $CFG_GLPI;

        $target = $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ipdiscoverrework.import.php" . $backValues;
        self::checkBox($target);

        echo "<form method='post' id='ipdiscover_form' name='ipdiscover_form' action='$target'>";
        echo "<div class='center' style=\"width=100%\">";
        echo Html::submit(_sx('button', 'Delete link', 'ocsinventoryng'), ['name' => 'deletelink']);
        echo "</div>";

        echo "<table width='100%'class='tab_cadrehov'>\n";
        echo Search::showHeaderItem($output_type, __('Item'), $header_num);
        echo Search::showHeaderItem($output_type, __('Item type'), $header_num);
        echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
        echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
        echo Search::showHeaderItem($output_type, __('Location'), $header_num);
        echo Search::showHeaderItem($output_type, __('Import date in GLPI', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('Subnet'), $header_num);
        echo Search::showHeaderItem($output_type, __('&nbsp;'), $header_num);
        echo Search::showEndLine($output_type);

        $row_num = 1;

        for ($i = $start; $i < $lim + $start; $i++) {
            if (isset($hardware[$i])) {
                $row_num++;
                $item_num = 1;
                echo Search::showNewLine($output_type, $row_num % 2);
                $dbu = new DbUtils();
                $class = $dbu->getItemForItemtype($hardware[$i]["itemtype"]);
                $class->getFromDB($hardware[$i]["items_id"]);
                $iplist = "";
                $ip     = new IPAddress();
                // Update IPAddress
                foreach ($DB->request('glpi_networkports', ['itemtype' => $hardware[$i]["itemtype"], 'items_id' => $hardware[$i]["items_id"]]) as $netname) {
                    foreach ($DB->request('glpi_networknames', ['itemtype' => 'NetworkPort', 'items_id' => $netname['id']]) as $dataname) {
                        foreach ($DB->request('glpi_ipaddresses', ['itemtype' => 'NetworkName', 'items_id' => $dataname['id']]) as $data) {
                            $ip->getFromDB($data['id']);
                            $iplist .= $ip->getName() . "<br>";
                        }
                    }
                }
                echo Search::showItem($output_type, $class->getLink(), $item_num, $row_num);
                echo Search::showItem($output_type, $class->getTypeName(), $item_num, $row_num);
                echo Search::showItem($output_type, $hardware[$i]["macaddress"], $item_num, $row_num);
                echo Search::showItem($output_type, $iplist, $item_num, $row_num);
                echo Search::showItem($output_type, Dropdown::getDropdownName("glpi_locations", $class->fields["locations_id"]), $item_num, $row_num);
                echo Search::showItem($output_type, Html::convDateTime($hardware[$i]["last_update"]), $item_num, $row_num);
                echo Search::showItem($output_type, $hardware[$i]["subnet"], $item_num, $row_num);
                echo self::showItem($hardware[$i]["id"], "", "", "", true, "", $i);
                echo Search::showEndLine($output_type);
            }
        }

        echo "<tbody style=\"display:none\">";
        echo "<tr><td>";
        echo Html::hidden('subnet', ['value' => $ipAdress]);
        echo "</td></tr>";
        echo "</tbody>";
        echo "</table>\n";
        echo "<div class='center' style=\"width=100%\">";
        echo Html::submit(_sx('button', 'Delete link', 'ocsinventoryng'), ['name' => 'deletelink']);
        echo "</div>";
        Html::closeForm();
        self::checkBox($target);
    }
    
    /**
     * displayInventoriedTable
     *
     * @param  mixed $hardware
     * @param  mixed $output_type
     * @param  mixed $start
     * @param  mixed $lim
     * @return void
     */
    static function displayInventoriedTable($hardware, $output_type, $start, $lim) {
        echo "<table width='100%'class='tab_cadrehov'>\n";
        echo Search::showHeaderItem($output_type, __('User'), $header_num);
        echo Search::showHeaderItem($output_type, __('Name'), $header_num);
        echo Search::showHeaderItem($output_type, __('System'), $header_num);
        echo Search::showHeaderItem($output_type, __('Version of the operating system'), $header_num);
        echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
        echo Search::showHeaderItem($output_type, __('Last OCSNG inventory date', 'ocsinventoryng'), $header_num);
        echo Search::showEndLine($output_type);
        $row_num = 1;

        for ($i = $start; $i < $lim + $start; $i++) {
            if (isset($hardware[$i])) {
                $row_num++;
                $item_num = 1;

                echo Search::showNewLine($output_type, $row_num % 2);
                echo Search::showItem($output_type, $hardware[$i]["userid"], $item_num, $row_num);
                echo Search::showItem($output_type, $hardware[$i]["name"], $item_num, $row_num);
                echo Search::showItem($output_type, $hardware[$i]["osname"], $item_num, $row_num);
                echo Search::showItem($output_type, $hardware[$i]["osversion"], $item_num, $row_num);
                echo Search::showItem($output_type, $hardware[$i]["ipaddr"], $item_num, $row_num);
                echo Search::showItem($output_type, Html::convDateTime($hardware[$i]["lastdate"]), $item_num, $row_num);
                echo Search::showEndLine($output_type);
            }
        }
        echo "</table>\n";
    }
    
    /**
     * getEquipmentDetails
     *
     * @param  mixed $mac
     * @param  mixed $ocsserverid
     * @return void
     */
    static function getEquipmentDetails($mac, $ocsserverid) {
        $ocsClient = new PluginOcsinventoryngOcsServer();
        $DBOCS     = $ocsClient->getDBocs($ocsserverid)->getDB();
        $query     = "SELECT n.ip, n.mac, n.mask, n.date, n.name, n.TAG as nTag, s.name as subnet, s.id as id, s.tag as tag, n.netid
        FROM netmap n LEFT JOIN networks ns ON ns.macaddr=n.mac 
        LEFT JOIN subnet s ON n.NETID = s.NETID WHERE n.mac IN ('$mac')";

        $result    = $DBOCS->query($query);

        $equipment = [];
  
        while ($ent = $DBOCS->fetchAssoc($result)) {
            $equipment = [
                "mac" => $mac,
                "subnet" => $ent["subnet"],
                "ip" => $ent["ip"],
                "date" => $ent["date"],
                "tag" => $ent["tag"],
                "netid" => $ent["netid"],
                "id" => $ent["id"]
            ];
        }

        return $equipment;
    }
    
    /**
     * updateOCSLink
     *
     * @param  mixed $equipment
     * @param  mixed $ocsserverid
     * @return void
     */
    static function updateOCSLink($equipment, $ocsserverid) {
        $ocsClient = new PluginOcsinventoryngOcsServer();
        $DBOCS     = $ocsClient->getDBocs($ocsserverid)->getDB();
        $ocsType     = $equipment["type"];
        $description = $equipment["description"];
        $user        = $equipment["user"];
        $mac         = $equipment["mac"];

        $ocsQuery    = "INSERT INTO `network_devices` (`description`,`type`,`macaddr`,`user`) VALUES('$description','$ocsType','$mac','$user')";
        
        $DBOCS->query($ocsQuery);
    }
    
    /**
     * cleanNonIventoried
     *
     * @param  mixed $id
     * @return void
     */
    static function cleanNonIventoried($id) {
        global $DB;

        $req = $DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinks', ['id' => $id]);
        $item = $req->next();
        $sup = $DB->delete('glpi_plugin_ocsinventoryng_ipdiscoverocslinks', ['id' => $item['id']]);

        $class = new $item['itemtype']();

        $class->delete(['id' => $item['items_id']]);

        return $item['macaddress'];
    }
        
    /**
     * deleteMACFromOCS
     *
     * @param  mixed $plugin_ocsinventoryng_ocsservers_id
     * @param  mixed $macAdresses
     * @return void
     */
    static function deleteMACFromOCS($plugin_ocsinventoryng_ocsservers_id, $mac) {
        $ocsClient = new PluginOcsinventoryngOcsServer();
        $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();

        $query = " DELETE FROM `netmap` WHERE `MAC`='$mac' ";
        $DBOCS->query($query);

        $query = " DELETE FROM `network_devices` WHERE `MACADDR`='$mac' ";
        $DBOCS->query($query);
    }
    
    /**
     * unlinkMACFromOCS
     *
     * @param  mixed $plugin_ocsinventoryng_ocsservers_id
     * @param  mixed $mac
     * @return void
     */
    static function unlinkMACFromOCS($plugin_ocsinventoryng_ocsservers_id, $mac) {
        $ocsClient = new PluginOcsinventoryngOcsServer();
        $DBOCS     = $ocsClient->getDBocs($plugin_ocsinventoryng_ocsservers_id)->getDB();

        $query = " DELETE FROM `network_devices` WHERE `MACADDR`='$mac' ";
        $DBOCS->query($query);
    }
    
    /**
     * getMacAdressKeyVal
     *
     * @param  mixed $macAdresses
     * @return void
     */
    static function getMacAdressKeyVal($macAdresses) {
        $keys = [];
        foreach ($macAdresses as $key => $val) {
            foreach ($val as $mac => $on) {
                $keys[$key] = $mac;
            }
        }
        return $keys;
    }
}