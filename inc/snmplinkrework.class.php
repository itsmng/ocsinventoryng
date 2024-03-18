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
	static $rightname = "plugin_ocsinventoryng";

	static $snmptypes = [
		"Computer", 
		"NetworkEquipment", 
		"Peripheral", 
		"Phone", 
		"Printer"
	];

	static $tableToObject = [
		"glpi_computers" => "Computer",
		"glpi_networkequipments" => "NetworkEquipment",
		"glpi_peripherals" => "Peripheral",
		"glpi_phones" => "Phone",
		"glpi_printers" => "Printer"
	];

	static $networkColumns = [
		"networks_name_id" => "name",
		"networks_ip_id" => "NetworkName__ipaddresses",
		"networks_macaddress_id" => "mac"
	];

	static $correspondances = [
		"Computer" => [
			"models_id" => [
				"column" => "computermodels_id",
				"class" => "ComputerModel"
			],
			"types_id" => [
				"column" => "computertypes_id",
				"class" => "ComputerType"
			],
			"locations_id" => [
				"column" => "locations_id",
				"class" => "Location"
			],
			"manufacturers_id" => [
				"column" => "manufacturers_id",
				"class" => "Manufacturer"
			]
		],
		"NetworkEquipment" => [
			"models_id" => [
				"column" => "networkequipmentmodels_id",
				"class" => "NetworkEquipmentModel"
			],
			"types_id" => [
				"column" => "networkequipmenttypes_id",
				"class" => "NetworkEquipmentType"
			],
			"locations_id" => [
				"column" => "locations_id",
				"class" => "Location"
			],
			"manufacturers_id" => [
				"column" => "manufacturers_id",
				"class" => "Manufacturer"
			]
		],
		"Peripheral" => [
			"models_id" => [
				"column" => "peripheralmodels_id",
				"class" => "PeripheralModel"
			],
			"types_id" => [
				"column" => "peripheraltypes_id",
				"class" => "PeripheralType"
			],
			"locations_id" => [
				"column" => "locations_id",
				"class" => "Location"
			],
			"manufacturers_id" => [
				"column" => "manufacturers_id",
				"class" => "Manufacturer"
			]
		], 
		"Phone" => [
			"models_id" => [
				"column" => "phonemodels_id",
				"class" => "PhoneModel"
			],
			"types_id" => [
				"column" => "phonetypes_id",
				"class" => "PhoneType"
			],
			"locations_id" => [
				"column" => "locations_id",
				"class" => "Location"
			],
			"manufacturers_id" => [
				"column" => "manufacturers_id",
				"class" => "Manufacturer"
			]
		], 
		"Printer" => [
			"models_id" => [
				"column" => "printermodels_id",
				"class" => "PrinterModel"
			],
			"types_id" => [
				"column" => "printertypes_id",
				"class" => "PrinterType"
			],
			"locations_id" => [
				"column" => "locations_id",
				"class" => "Location"
			],
			"manufacturers_id" => [
				"column" => "manufacturers_id",
				"class" => "Manufacturer"
			]
		]
	];

	static $excludeIP = [
		"127.0.0.1",
		"0.0.0.0"
	];

	/**
     * @see inc/CommonGLPI::getTabNameForItem()
     *
     * @param $item CommonGLPI object
     * @param $withtemplate (default 0)
     *
     * @return string|translated
     */
	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
		if(in_array($item->getType(), self::$snmptypes) && $this->canView()) {
			if ($this->getFromDBByCrit(['items_id' => $item->getID(), 'itemtype' => $item->getType()])) {
				return __('OCSNG SNMP', 'ocsinventoryng');
			}
		} elseif($item->getType() == "PluginOcsinventoryngOcsServer") {
			if (PluginOcsinventoryngOcsServer::checkOCSconnection($item->getID())
				&& PluginOcsinventoryngOcsServer::checkVersion($item->getID())
				&& PluginOcsinventoryngOcsServer::checkTraceDeleted($item->getID())) {

				$client  = PluginOcsinventoryngOcsServer::getDBocs($item->getID());
				$version = $client->getTextConfig('GUI_VERSION');
				$snmp    = ($client->getIntConfig('SNMP') > 0)?true:false;
	
				if($version < PluginOcsinventoryngOcsServer::OCS2_1_VERSION_LIMIT && $snmp) {
					return __('SNMP Import', 'ocsinventoryng');
				}
			}
		}

		return '';
	}

	/**
     * @param $item CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     *
     * @return bool|true
     */
	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
		if(in_array($item->getType(), self::$snmptypes)) {
			$ID = $item->getField('id');
			$prof = new self();
			$prof->showForm($ID);
		} elseif($item->getType() == "PluginOcsinventoryngOcsServer") {
			$conf = new self();
			$conf->ocsFormSNMPImportOptions($item->getID());
		}

		return true;
	}

	/**
	 * 
	 * 
	 * @param $plugin_ocsinventoryng_ocsservers_id
	 */
	static function snmpMenu($plugin_ocsinventoryng_ocsservers_id) {
		global $CFG_GLPI, $DB;

		$ocsservers = [];
		$dbu = new DbUtils();

		$numberActiveServers = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]);

		if($numberActiveServers > 0) {
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

		$sql = "SELECT `name`, `is_active`
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

			//host not imported by thread
			echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
			echo "<tr><th colspan='4'>";
			echo __('OCSNG SNMP import', 'ocsinventoryng');

			if($client->getTextConfig('GUI_VERSION') < PluginOcsinventoryngOcsServer::OCS2_1_VERSION_LIMIT) {
				echo "<br>";
				echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsserver.form.php?id=" . $plugin_ocsinventoryng_ocsservers_id . "&forcetab=PluginOcsinventoryngSnmpOcslink\$1'>";
				echo __('See Setup : SNMP Import before', 'ocsinventoryng');
				echo "</a>";
				echo "</th></tr>";
			}

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
     * @param     $ocsid
     * @param     $plugin_ocsinventoryng_ocsservers_id
     * @param int $lock
     * @param     $params
     *
     * @return array
     */
	static function processSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params) {
		return self::importSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params);
	}
	
	/**
	 * getOCSSnmpTypeConfig
	 *
	 * @param  mixed $OCSSnmpTableId
	 * @param  mixed $OCSServerId
	 * 
	 * @return void
	 */
	static function getOCSSnmpTypeConfig($OCSSnmpTableId, $OCSServerId) {
		global $DB;

		$OCSSnmpTypeConfig = [];

		$query = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` WHERE `ocs_snmp_type_id`=$OCSSnmpTableId AND `ocs_srv`=$OCSServerId";
		$queryResult = $DB->query($query);

		if($queryResult) while ($row = $DB->fetchAssoc($queryResult)) {
			$OCSSnmpTypeConfig[$row["object"]][$row["glpi_col"]] = $row["ocs_snmp_label_id"];
		}

		return $OCSSnmpTypeConfig;
	}
	
	/**
	 * getReconciliationColumn
	 *
	 * @param  mixed $OCSSnmpTableId
	 * @param  mixed $OCSServerId
	 * 
	 * @return void
	 */
	static function getReconciliationColumn($OCSSnmpTableId, $OCSServerId) {
		global $DB;

		$reconciliationColumn = [];

		$query = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` WHERE `ocs_snmp_type_id`=$OCSSnmpTableId AND `ocs_srv`=$OCSServerId AND `is_reconsiliation`=1";
		$queryResult = $DB->query($query);

		if($queryResult) while ($row = $DB->fetchAssoc($queryResult)) {
			$reconciliationColumn[$row["object"]] = [
				"column" => $row["glpi_col"],
				"ocsLabelId" => $row["ocs_snmp_label_id"],
			];
		}

		return $reconciliationColumn;
	}
	
	/**
	 * importSnmp
	 *
	 * @param  mixed $ocsids
	 * @param  mixed $OCSServerId
	 * @param  mixed $params
	 * 
	 * @return void
	 */
	static function importSnmp($ocsids, $OCSServerId, $params) {
		global $DB;

		$OCSSnmpIds = explode("_", $ocsids);
		$OCSSnmpTableId = $OCSSnmpIds[0];
		$OCSSnmpRowId = $OCSSnmpIds[1];

		$OCSClient = PluginOcsinventoryngOcsServer::getDBocs($OCSServerId);
		$OCSConfig = self::getOCSSnmpTypeConfig($OCSSnmpTableId, $OCSServerId);
		$OCSSnmpRowData = $OCSClient->getSnmpValueByTableAndId($OCSSnmpTableId, $OCSSnmpRowId);
		$reconciliation = self::getReconciliationColumn($OCSSnmpTableId, $OCSServerId);

		$configIpdReconciliation = [
            "merged" => 0,
            "switch" => null
        ];

		// Get Ipdiscover config reconciliation with SNMP
        foreach($DB->request('glpi_plugin_ocsinventoryng_ipdiscoversnmpreconciliation', ['plugin_ocsinventoryng_ocsservers_id' => $OCSServerId]) as $value) {
            $configIpdReconciliation["merged"] = $value["merge_ipd_in_snmp"];
            $configIpdReconciliation["switch"] = $value["switch_to_identified_type"];
        }

		foreach($OCSConfig as $tableObj => $data) {
			$object = self::$tableToObject[$tableObj];
			$input = [
				"entities_id" => 0
			];

			foreach($data as $column => $OCSLabelId) {
				if(array_key_exists($column, self::$correspondances[$object]) && trim($OCSSnmpRowData[$OCSLabelId]) != "") {
					$input[self::$correspondances[$object][$column]["column"]] = self::getObjectOrInsert($column, $OCSSnmpRowData[$OCSLabelId], $object);
				} elseif(!array_key_exists($column, self::$networkColumns) && trim($OCSSnmpRowData[$OCSLabelId]) != "") {
					$input[$column] = addslashes($OCSSnmpRowData[$OCSLabelId]);
				}
			}

			$Equipment = new $object();
			
			if(count($input) > 1) {
				// If no reconciliation add equipment without check
				if(empty($reconciliation)) {
					$objId = $Equipment->add($input);
				} else {
					$reconciliationColumn = $reconciliation[$tableObj]["column"];
					$reconciliationValue = $OCSSnmpRowData[$reconciliation[$tableObj]["ocsLabelId"]];
	
					if(array_key_exists($reconciliationColumn, self::$correspondances[$object]) && $input[self::$correspondances[$object][$reconciliationColumn]["column"]] != 0) {
						$ifExists = $Equipment->find([
							self::$correspondances[$object][$reconciliationColumn]["column"] => $input[self::$correspondances[$object][$reconciliationColumn]["column"]]
						]);
						if(count($ifExists) == 0) {
							$objId = $Equipment->add($input);
						}
					} elseif(!array_key_exists($reconciliationColumn, self::$networkColumns)) {
						$ifExists = $Equipment->find([$reconciliationColumn => $input[$reconciliationColumn]]);
						if(count($ifExists) == 0) {
							$objId = $Equipment->add($input);
						}
					} elseif(array_key_exists($reconciliationColumn, self::$networkColumns)) {
						$objId = $Equipment->add($input);
					}
				}

				if(isset($objId)) {
					if(isset($data["networks_name_id"]) && trim($OCSSnmpRowData[$data["networks_name_id"]])!= "") {
						$inputNetwork["name"] = addslashes($OCSSnmpRowData[$data["networks_name_id"]]);
					}

					if(isset($data["networks_ip_id"]) && trim($OCSSnmpRowData[$data["networks_ip_id"]])!= "") {
						if(strpos($OCSSnmpRowData[$data["networks_ip_id"]], "-") !== false) {
							$explode = explode(" - ", $OCSSnmpRowData[$data["networks_ip_id"]]);
							$i = 1;
							foreach($explode as $value) {
								if(!in_array($value, self::$excludeIP)) {
									if($configIpdReconciliation["merged"]) {
										PluginOcsinventoryngIpdiscoverOcslinkrework::checkSnmpIp($value, $OCSServerId, ["id" => $objId, "itemtype" => $object], $configIpdReconciliation);
									}
		
									$inputNetwork["NetworkName__ipaddresses"]["-".$i] = $value;
									$i++;
								}
							}
						} else {
							if($configIpdReconciliation["merged"]) {
								PluginOcsinventoryngIpdiscoverOcslinkrework::checkSnmpIp($OCSSnmpRowData[$data["networks_ip_id"]], $OCSServerId, ["id" => $objId, "itemtype" => $object], $configIpdReconciliation);
							}

							$inputNetwork["NetworkName__ipaddresses"] = ["-100" => $OCSSnmpRowData[$data["networks_ip_id"]]];
						}
					}

					if(isset($data["networks_macaddress_id"]) && trim($OCSSnmpRowData[$data["networks_macaddress_id"]])!= "") {
						if(strlen($OCSSnmpRowData[$data["networks_macaddress_id"]]) == 14) $mac = "00:".$OCSSnmpRowData[$data["networks_macaddress_id"]];
						else $mac = $OCSSnmpRowData[$data["networks_macaddress_id"]];

						$inputNetwork["mac"] = (strpos($mac ?? "", '.') !== false) ? '' : $mac;
					}

					$defaultInputNetwork = [
						'items_id'          	=> $objId,
						'itemtype'          	=> $object,
						'instantiation_type'	=> "NetworkPortEthernet",
						"entities_id"       	=> 0,
						'_create_children'  	=> 1,
						'is_deleted'        	=> 0,
						'comment'           	=> 'Remote scanned device from SNMP',
					];

					if(!empty($inputNetwork)) {
						$inputNetwork = array_merge($defaultInputNetwork, $inputNetwork);
						$NetworkPort = new NetworkPort();
						$NetworkPort->add($inputNetwork);
						$input = array_merge($input, $inputNetwork);
					}

					//Process entity rules
					$ruleCollection = new RuleImportEntityCollection();
					$fields = $ruleCollection->processAllRules(
						[
							'ocsservers_id' => $OCSServerId,
							'_source'       => 'ocsinventoryng',
							'type'			=> 'snmp'
						], 
						[], 
						[
							'ocsid' 	=> $OCSSnmpRowId,
							'snmpdata'	=> $input
						]
					);

					if(isset($fields['entities_id']) && $fields['entities_id'] >= 0) {
						unset($fields["_ruleid"]);
						$fields['id'] = $objId;
						$Equipment->update($fields);
					}
						
					$date = date("Y-m-d H:i:s");

					//Add to snmp link
					$query = "INSERT INTO `glpi_plugin_ocsinventoryng_snmpocslinks`
						SET `items_id`=$objId,`ocs_id`='".$OCSSnmpRowId."',`ocstype`='".$OCSSnmpTableId."',`itemtype`='".$object."',
						`last_update`='".$date."',`plugin_ocsinventoryng_ocsservers_id`=$OCSServerId";

					$DB->query($query);
					return ['status' => PluginOcsinventoryngOcsProcess::SNMP_IMPORTED];
				} else {
					return ['status' => PluginOcsinventoryngOcsProcess::SNMP_FAILED_IMPORT];
				}
			} else {
				return ['status' => PluginOcsinventoryngOcsProcess::SNMP_FAILED_IMPORT];
			}
		}
	}

	/**
	 * getObjectOrInsert
	 *
	 * @param  mixed $object
	 * @param  mixed $value
	 * 
	 * @return void
	 */
	static function getObjectOrInsert($objectColumn, $value, $object){
		$itemId = 0;
		
		$item = new self::$correspondances[$object][$objectColumn]["class"]();
		$ifExists = $item->find(["name" => addslashes($value)]);

		if(count($ifExists) == 0) {
			$itemId = $item->add(["name" => addslashes($value)]);
		} else {
			foreach($ifExists as $id) {
				$itemId = $id["id"];
			}
		}

		return $itemId;
	}
	
	/**
	 * updateSnmp
	 *
	 * @param  mixed $ocsids
	 * @param  mixed $OCSServerId
	 * 
	 * @return void
	 */
	static function updateSnmp($ocsids, $OCSServerId) {
		global $DB;

		$OCSSnmpIds = explode("_", $ocsids);
		$OCSSnmpTableId = $OCSSnmpIds[0];
		$OCSSnmpRowId = $OCSSnmpIds[1];

		$OCSClient = PluginOcsinventoryngOcsServer::getDBocs($OCSServerId);
		$OCSConfig = self::getOCSSnmpTypeConfig($OCSSnmpTableId, $OCSServerId);
		$OCSSnmpRowData = $OCSClient->getSnmpValueByTableAndId($OCSSnmpTableId, $OCSSnmpRowId);
		$reconciliation = self::getReconciliationColumn($OCSSnmpTableId, $OCSServerId);

		$query = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmpocslinks` WHERE ocstype=$OCSSnmpTableId 
			AND ocs_id=$OCSSnmpRowId AND plugin_ocsinventoryng_ocsservers_id=$OCSServerId";
		$ITSMSnmpResult = $DB->query($query);
		$ITSMSnmpRowData = [];

		if($DB->numrows($ITSMSnmpResult)) {
			$ITSMSnmpRowData = $DB->fetchArray($ITSMSnmpResult);
		}

		foreach($OCSConfig as $tableObj => $data) {
			$object = self::$tableToObject[$tableObj];
			$input = [
				"id" => $ITSMSnmpRowData["items_id"] ?? null,
				"entities_id" => 0
			];

			foreach($data as $column => $OCSLabelId) {
				if(array_key_exists($column, self::$correspondances[$object]) && trim($OCSSnmpRowData[$OCSLabelId]) != "") {
					$input[self::$correspondances[$object][$column]["column"]] = self::getObjectOrInsert($column, $OCSSnmpRowData[$OCSLabelId], $object);
				} elseif(!array_key_exists($column, self::$networkColumns) && trim($OCSSnmpRowData[$OCSLabelId]) != "") {
					$input[$column] = addslashes($OCSSnmpRowData[$OCSLabelId]);
				}
			}

			$Equipment = new $object();

			if(!is_null($input["id"])) {
				$objId = $Equipment->update($input);

				if($objId) {
					if(isset($data["networks_name_id"]) && trim($OCSSnmpRowData[$data["networks_name_id"]])!= "") {
						$inputNetwork["name"] = addslashes($OCSSnmpRowData[$data["networks_name_id"]]);
					}

					if(isset($data["networks_ip_id"]) && trim($OCSSnmpRowData[$data["networks_ip_id"]])!= "") {
						if(strpos($OCSSnmpRowData[$data["networks_ip_id"]], "-") !== false) {
							$explode = explode(" - ", $OCSSnmpRowData[$data["networks_ip_id"]]);
							$i = 1;
							foreach($explode as $value) {
								if(!in_array($value, self::$excludeIP)) {
									$inputNetwork["NetworkName__ipaddresses"]["-".$i] = $value;
									$i++;
								}
							}
						} else {
							$inputNetwork["NetworkName__ipaddresses"] = ["-100" => $OCSSnmpRowData[$data["networks_ip_id"]]];
						}
					}

					if(isset($data["networks_macaddress_id"]) && trim($OCSSnmpRowData[$data["networks_macaddress_id"]])!= "") {
						if(strlen($OCSSnmpRowData[$data["networks_macaddress_id"]]) == 14) $mac = "00:".$OCSSnmpRowData[$data["networks_macaddress_id"]];
						else $mac = $OCSSnmpRowData[$data["networks_macaddress_id"]];

						$inputNetwork["mac"] = (strpos($mac ?? "", '.') !== false) ? '' : $mac;
					}

					if(!empty($inputNetwork)) {
						$NetworkPort = new NetworkPort();
						$networkPortItem = $NetworkPort->find(["items_id" => $ITSMSnmpRowData["items_id"], "itemtype" => $object]);
						if(!empty($networkPortItem)) {
							foreach($networkPortItem as $id => $data) {
								$defaultInputNetwork = [
									'id' => $id
								];
							}
							if(!empty($inputNetwork)) {
								$inputNetwork = array_merge($defaultInputNetwork, $inputNetwork);
								$update = $NetworkPort->update($inputNetwork);
							}
						}
						$input = array_merge($input, $inputNetwork);
					}

					//Process entity rules
					$ruleCollection = new RuleImportEntityCollection();
					$fields = $ruleCollection->processAllRules(
						[
							'ocsservers_id' => $OCSServerId,
							'_source'       => 'ocsinventoryng',
							'type'			=> 'snmp'
						], 
						[], 
						[
							'ocsid' 	=> $ITSMSnmpRowData["items_id"],
							'snmpdata'	=> $input
						]
					);

					if(isset($fields['entities_id']) && $fields['entities_id'] >= 0) {
						unset($fields["_ruleid"]);
						$fields['id'] = $ITSMSnmpRowData["items_id"];
						$Equipment->update($fields);
					}

					$date = date("Y-m-d H:i:s");
					$itemId = $ITSMSnmpRowData["items_id"];

					//Add to snmp link
					$query = "UPDATE `glpi_plugin_ocsinventoryng_snmpocslinks`
						SET `last_update`='".$date."' 
						WHERE `items_id`=$itemId AND `ocs_id`='".$OCSSnmpRowId."'AND `ocstype`='".$OCSSnmpTableId."' 
						AND `itemtype`='".$object."' AND `plugin_ocsinventoryng_ocsservers_id`=$OCSServerId";

					$DB->query($query);
					
					return ['status' => PluginOcsinventoryngOcsProcess::SNMP_SYNCHRONIZED];
				} else {
					return ['status' => PluginOcsinventoryngOcsProcess::SNMP_NOTUPDATED];
				}
			} else {
				return ['status' => PluginOcsinventoryngOcsProcess::SNMP_NOTUPDATED];
			}
		}
	}
	
	/**
	 * addSnmp
	 *
	 * @return void
	 */
	static function addSnmp() {
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
			'glpi_computers' => __('Computer'),
			'glpi_monitors' => __('Monitor'),
			'glpi_networkequipments' => __('Network device'),
			'glpi_peripherals' => __('Device'),
			'glpi_phones' => __('Phone'),
			'glpi_printers' => __('Printer'),
		];

		$options = [];

		if (!empty($snmpTypes)) {
			echo "<div align='center'>";
			echo "<form name='form' method='get' action='" . $target . "'>";
			echo "<table class='tab_cadrehov'>";
			echo "<tr>";
			echo "<td>" . __('Object', 'ocsinventoryng') . "</td>";
			echo "<td>";
			Dropdown::showFromArray('SelectGLPI', $glpi_types, $options);
			echo "</td>";
			echo "<td>" . __('OCS snmp Type', 'ocsinventoryng') . "</td>";
			echo "<td>";
			Dropdown::showFromArray('SelectOCS', $snmpTypes, $options);
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<table class='tab_cadrehov'>";
			echo "<tr>";
			echo "<td class='center'>";
			echo Html::submit(_sx('button', 'Post'), ['name' => 'search']);
			echo "</td></tr></table></form></div>";
		} else {
			echo "<div align='center'>";
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr>";
			echo "<td class='center b'>";
			echo __('No SNMP object to link', 'ocsinventoryng');
			echo "</td></tr></table></div>";
		}
	}
	
	/**
	 * showSnmpLinks
	 *
	 * @return void
	 */
	static function showSnmpLinks(){
		global $DB, $CFG_GLPI;

		$ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);
		$ocs_srv = $_SESSION['plugin_ocsinventoryng_ocsservers_id'];

		$query = "SELECT `object`, `ocs_snmp_type_id` FROM `glpi_plugin_ocsinventoryng_snmplinkreworks` WHERE ocs_srv = $ocs_srv GROUP BY `ocs_snmp_type_id`;";
		$result_glpi = $DB->query($query);

		$snmpTypes = $ocsClient->getSnmpTypes();

		$snmpLinks = [];
		$glpi_types = [
			'glpi_computers' => __('Computer'),
			'glpi_monitors' => __('Monitor'),
			'glpi_networkequipments' => __('Network device'),
			'glpi_peripherals' => __('Device'),
			'glpi_phones' => __('Phone'),
			'glpi_printers' => __('Printer'),
		];

		if ($DB->numrows($result_glpi) > 0) {
			$i = 0;

			while ($data = $DB->fetchArray($result_glpi)) {
				$snmpLinks[$i]['object'] = $glpi_types[$data["object"]];
				$snmpLinks[$i]['type'] = $snmpTypes[$data['ocs_snmp_type_id']];
				$snmpLinks[$i]['typeId'] = $data['ocs_snmp_type_id'];
				$i++;
			}
		}

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
	
	/**
	 * addSnmpLinks
	 *
	 * @param  mixed $params
	 * 
	 * @return void
	 */
	static function addSnmpLinks($params = []){
		global $DB, $CFG_GLPI;

		$ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);

		$snmpLabels['0'] = __("Choose a label", "ocsinventoryng");
      	$snmpLabels = array_merge($snmpLabels, $ocsClient->getSnmpLabelByType($_GET['SelectOCS']));

		$target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/snmplinkrework.form.php';

		$snmpLinks = [
			"name" => __("Name"),
			"comment" => __("Comment"),
			"contact" => __("Contact"),
			"locations_id" => __("Location"),
			"manufacturers_id" => __("Manufacturer"),
			"types_id" => __("Type"),
			"models_id" => __("Model"),
			"serial" => __("Serial number"),
			"networks_name_id" => __("Network port")." : ".__("Name"),
			"networks_ip_id" => __("Network port")." : ".__("IP address"),
			"networks_macaddress_id" => __("Network port")." : ".__("MAC address")
		];

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
			if (isset($params[$key])) {
				Dropdown::showFromArray("$key", $snmpLabels, ['value' => $params[$key]]);
			} else {
				Dropdown::showFromArray("$key", $snmpLabels);
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

	/**
	 * createSnmpLinks
	 *
	 * @param  mixed $params
	 * 
	 * @return void
	 */
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

					$DB->insertOrDie('glpi_plugin_ocsinventoryng_snmplinkreworks', [
						'object' => $device,
						'glpi_col' => $key,
						'ocs_snmp_type_id' => $ocsSplit[0],
						'ocs_snmp_label_id' => $ocsSplit[1],
						'is_reconsiliation' => $ocsSplit[2] == "Yes" ? 1 : 0,
						'ocs_srv' => $ocs_srv,
					]);
				}
			}
		}
	}
	
	/**
	 * showForm
	 *
	 * @param  mixed $ID
	 * @param  mixed $options
	 * 
	 * @return void
	 */
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
		echo "<tr><th colspan='2' class='center b'>";
		echo sprintf(__('%1$s %2$s'), ('gestion des droits :'), Dropdown::getDropdownName("glpi_profiles", $this->fields["id"]));
		echo "</th></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>Utiliser Mon Plugin</td><td>";
		Profile::dropdownNoneReadWrite("right", $this->fields["right"], 1, 1, 1);
		echo "</td></tr>";

		if ($canedit) {
			echo "<tr class='tab_bg_1'>";
			echo "<td class='center' colspan='2'>";
			echo "<input type='hidden' name='id' value=$ID>";
			echo "<input type='submit' name='update_user_profile' value='Mettre à jour' class='submit'>";
			echo "</td></tr>";
		}

		echo "</table>";
		Html::closeForm();
	}
	
	/**
	 * showSnmpDeviceToAdd
	 *
	 * @param  mixed $params
	 * 
	 * @return void
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

		$tolinked                            	= $p['tolinked'];
		$start                               	= $p['start'];
		$plugin_ocsinventoryng_ocsservers_id 	= $p['plugin_ocsinventoryng_ocsservers_id'];
		$title 									= __('Import new SNMP devices', 'ocsinventoryng');

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
			'glpi_networkequipments' => 'Network device',
			'glpi_peripherals' => 'Peripheral',
			'glpi_phones' => 'Phone',
			'glpi_printers' => 'Printer',
		];

		$ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION['plugin_ocsinventoryng_ocsservers_id']);
		$ocsResult = $ocsClient->getSnmpRework($_SESSION['plugin_ocsinventoryng_ocsservers_id']);

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
							echo "<h3>";
							echo sprintf(__('Transfer of OCS object %s to GLPI object %s', 'ocsinventoryng'), $ocsClient->getSnmpTypeById($keysnmp), __($glpi_types[self::getGlpiObjectByOcsTypes($keysnmp)]));
							echo "</h3></div>";

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
								echo "<input type='checkbox' name='toimport[".$keysnmp."_".$tab["ID"]."]' ".($p['check'] == "all" ? "checked" : "").">";
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

					echo Html::hidden('plugin_ocsinventoryng_ocsservers_id', ['value' => $plugin_ocsinventoryng_ocsservers_id]);
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
					echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') ."</td></tr>\n";
					echo "</table>";
				}

				echo "</div>";
			} else {
				echo "<div class='center'>";
				echo "<table class='tab_cadre_fixe'>";
				echo "<tr><th>" . $title . "</th></tr>\n";
				echo "<tr class='tab_bg_1'>";
				echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') ."</td></tr>\n";
				echo "</table></div>";
			}
		} else {
			echo "<div class='center'>";
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th>" . $title . "</th></tr>\n";
			echo "<tr class='tab_bg_1'>";
			echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') ."</td></tr>\n";
			echo "</table></div>";
		}
	}
	
	/**
	 * getGlpiObjectByOcsTypes
	 *
	 * @param  mixed $ocsTypes
	 * 
	 * @return void
	 */
	static function getGlpiObjectByOcsTypes($ocsTypes) {
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
	 * showSnmpDeviceToUpdate
	 *
	 * @param  mixed $plugin_ocsinventoryng_ocsservers_id
	 * @param  mixed $check
	 * @param  mixed $start
	 * 
	 * @return void
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
					'glpi_networkequipments' => 'Network device',
					'glpi_peripherals' => 'Peripheral',
					'glpi_phones' => 'Phone',
					'glpi_printers' => 'Printer',
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
							echo "<h3>";
							echo sprintf(__('Transfer of OCS object %s to GLPI object %s', 'ocsinventoryng'), $ocsClient->getSnmpTypeById($keysnmp), __($glpi_types[self::getGlpiObjectByOcsTypes($keysnmp)]));
							echo "</h3>";
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
								echo "<input type='checkbox' name='toupdate[" . $keysnmp . "_" . $tab["ID"] . "]' " .($p['check'] == "all" ? "checked" : "") . ">";
								echo "</td></tr>\n";
							}
						}
					}
					echo "</table><table class='tab_cadrehov'>";

					echo "<tr class='tab_bg_1'><td class='center'>";
					echo Html::submit(_sx('button', 'Synchronize'), ['name' => 'update_ok']);
					echo "<button type='submit' value='" . __('Delete') . "' name='delete' class='vsubmit'>" . __('Delete') . "</button>";
					echo Html::hidden('plugin_ocsinventoryng_ocsservers_id', ['value' => $plugin_ocsinventoryng_ocsservers_id]);
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
		$ocsSnmp = null;

		if (is_null($ocsSnmp)) {
			return false;
		}

		$query  = "INSERT INTO `glpi_plugin_ocsinventoryng_snmpocslinks`
			(`items_id`, `ocs_id`, `itemtype`, `last_update`, `plugin_ocsinventoryng_ocsservers_id`, `linked`)
			VALUES ('$items_id', '$ocsid', '" . $itemtype . "', '" . $_SESSION["glpi_currenttime"] . "', $plugin_ocsinventoryng_ocsservers_id, 1)";
		
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

		if ($p['itemtype'] != -1 && $p['items_id'] > 0 && ($idlink = self::ocsSnmpLink($ocsid, $plugin_ocsinventoryng_ocsservers_id,$p['items_id'], $p['itemtype']))) {
			self::updateSnmp($idlink, $plugin_ocsinventoryng_ocsservers_id);
			return ['status' => PluginOcsinventoryngOcsProcess::SNMP_LINKED];
		}

		return false;
	}
	
	/**
	 * deleteSnmp
	 *
	 * @param  mixed $id
	 * @param  mixed $plugin_ocsinventoryng_ocsservers_id
	 * 
	 * @return void
	 */
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

	/**************************************************************************************
	 *                                                                                    *
	 *                                  OLD SNMP VERSION                                  *
	 *                                                                                    *
	 **************************************************************************************/

	/**
	 * SNMP configuration file for old OCS snmp version
	 * 
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

		$values = [
			-1 => Dropdown::EMPTY_VALUE,
			0  => __('No'),
			1  => __('Yes')
		];

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
					});
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
}