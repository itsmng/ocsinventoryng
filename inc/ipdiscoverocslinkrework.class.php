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
    public static function getOCSTypes() {
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
            'NetworkEquipment' => __('NetworkEquipment'), 
            'Peripheral' => __('Peripheral'), 
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
        Dropdown::showFromArray('subnet', $linkFields, ['value' => ($configNonInv['subnet']) ? $configNonInv['subnet'] : 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('ID') . "</td>";
        echo "<td colspan='4'>";
        Dropdown::showFromArray('id', $linkFields, ['value' => ($configNonInv['id']) ? $configNonInv['id'] : 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('TAG') . "</td>";
        echo "<td colspan='4'>";
        Dropdown::showFromArray('tag', $linkFields, ['value' => ($configNonInv['tag']) ? $configNonInv['tag'] : 0]);
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
      
        foreach($ocstypes as $key => $value) {
            $req = $DB->request('glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt', ['ocs_type' => $value]);
            if ($row = $req->next()) {
                $types[$key] = $row['glpi_obj'];
            } else {
                $types[$key] = 'NetworkEquipment';
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
            'mac'                      => (strpos($ipdDatas['mac'], '.') !== false) ? '6d:b0:03:88:27:6e' : $ipdDatas['mac'],
            'items_id'                 => $equipment,
            'itemtype'                 => $className,
            'instantiation_type'       => "NetworkPortEthernet",
            "entities_id"              => 0,
            "NetworkName_name"         => ($ipdDatas['subnet'] != "" && !is_null($ipdDatas['subnet'])) ? $ipdDatas['subnet'] : $ipdDatas['ip'],
            "NetworkName__ipaddresses" => ["-100" => $ipdDatas['ip']],
            '_create_children'         => 1,
            'is_deleted'               => 0,
            'comment'                  => (strpos($ipdDatas['mac'], '.') !== false) ? 'Dummy Address MAC' : '',
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
            'mac'                      => (strpos($ipdDatas['mac'], '.') !== false) ? '6d:b0:03:88:27:6e' : $ipdDatas['mac'],
            'items_id'                 => $equipment,
            'itemtype'                 => "NetworkEquipment",
            'instantiation_type'       => "NetworkPortEthernet",
            "entities_id"              => 0,
            "NetworkName_name"         => ($ipdDatas['subnet'] != "" && !is_null($ipdDatas['subnet'])) ? $ipdDatas['subnet'] : $ipdDatas['ip'],
            "NetworkName__ipaddresses" => ["-100" => $ipdDatas['ip']],
            '_create_children'         => 1,
            'is_deleted'               => 0,
            'comment'                  => (strpos($ipdDatas['mac'], '.') !== false) ? 'Dummy Address MAC' : '',
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
}