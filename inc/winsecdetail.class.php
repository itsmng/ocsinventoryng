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
 * Class PluginOcsinventoryngWinsecdetail
 */
class PluginOcsinventoryngWinsecdetail extends CommonDBChild
{

    // From CommonDBChild
    static public $itemtype = 'Computer';
    static public $items_id = 'computers_id';

    static $rightname = "plugin_ocsinventoryng";

    /**
     * @param int $nb
     *
     * @return string
     */
    static function getTypeName($nb = 0)
    {
        return __('Windows Security Center Details', 'ocsinventoryng');
    }

    /**
     * Update config of the Windows Security Center Details
     *
     * This function erase old data and import the new ones about Windows Security Center Details
     *
     * @param $computers_id integer : glpi computer id.
     * @param $ocsComputer
     * @param $cfg_ocs
     * @param $force
     */
    static function updateWinsecdetail($computers_id, $ocsComputer, $cfg_ocs, $force)
    {
        $uninstall_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 3)) {
            $uninstall_history = 1;
        }
        $install_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
            $install_history = 1;
        }

        if ($force) {
            self::resetWinsecdetail($computers_id, $uninstall_history);
        }

        $CompWinsec = new self();
        $input = [];
        $input["computers_id"] = $computers_id;

        // Map all the fields from OCS data
        $fields = [
            'AMENGINEVERSION',
            'AMPRODUCTVERSION',
            'AMRUNNINGMODE',
            'AMSERVICEENABLED',
            'AMSERVICEVERSION',
            'ANTISPYWAREENABLED',
            'ANTISPYWARESIGNATUREAGE',
            'ANTISPYWARESIGNATURELASTUPDATED',
            'ANTISPYWARESIGNATUREVERSION',
            'ANTIVIRUSENABLED',
            'ANTIVIRUSSIGNATUREAGE',
            'ANTIVIRUSSIGNATURELASTUPDATED',
            'ANTIVIRUSSIGNATUREVERSION',
            'BEHAVIORMONITORENABLED',
            'IOAVPROTECTIONENABLED',
            'ISTAMPERPROTECTED',
            'NISENABLED',
            'NISENGINEVERSION',
            'NISSIGNATUREAGE',
            'NISSIGNATURELASTUPDATED',
            'NISSIGNATUREVERSION',
            'ONACCESSPROTECTIONENABLED',
            'REALTIMEPROTECTIONENABLED',
            'TAMPERPROTECTIONSOURCE'
        ];

        foreach ($fields as $field) {
            if (isset($ocsComputer[$field])) {
                $input[strtolower($field)] = $ocsComputer[$field];
            }
        }

        $CompWinsec->add($input, ['disable_unicity_check' => true], $install_history);
    }

    /**
     * Delete old Windows Security Center Details entries
     *
     * @param $glpi_computers_id integer : glpi computer id.
     * @param $uninstall_history boolean
     *
     */
    static function resetWinsecdetail($glpi_computers_id, $uninstall_history)
    {

        $winsec = new self();
        $winsec->deleteByCriteria(['computers_id' => $glpi_computers_id], 1, $uninstall_history);
    }

    /**
     * @see CommonGLPI::getTabNameForItem()
     *
     * @param \CommonGLPI $item
     * @param int         $withtemplate
     *
     * @return array|string
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $plugin_ocsinventoryng_ocsservers_id = PluginOcsinventoryngOcslink::getOCSServerForItem($item);
        if (
            $plugin_ocsinventoryng_ocsservers_id > 0
            && PluginOcsinventoryngOcsServer::serverIsActive($plugin_ocsinventoryng_ocsservers_id)
        ) {
            $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
            // can exists for template
            if (
                ($item->getType() == 'Computer')
                && Computer::canView()
                && $cfg_ocs["import_winsecdetails"]
            ) {
                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    $nb = $dbu->countElementsInTable(
                        'glpi_plugin_ocsinventoryng_winsecdetails',
                        ["computers_id" => $item->getID()]
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
            return '';
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
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $plugin_ocsinventoryng_ocsservers_id = PluginOcsinventoryngOcslink::getOCSServerForItem($item);
        if (!PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            echo "<div class='spaced center'>";
            echo "<table class='tab_cadre_fixehov'>";
            echo "<tr class='noHover'><th colspan='5'>" . self::getTypeName(0) .
                "</th></tr>";
            echo "<tr class='tab_bg_2'><th colspan='5'>" . __('Server unreachable', 'ocsinventoryng') . "</th></tr>";
            echo "</table>";
            echo "</div>";
        } else {
            self::showForComputer($item, $withtemplate);
        }
        return true;
    }

    /**
     * Print the computers windows security center details
     *
     * @param             $comp                  Computer object
     * @param bool|string $withtemplate boolean  Template or basic item (default '')
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    static function showForComputer(Computer $comp, $withtemplate = '')
    {
        global $DB;

        $ID = $comp->fields['id'];

        if (
            !$comp->getFromDB($ID)
            || !$comp->can($ID, READ)
        ) {
            return false;
        }

        echo "<div class='spaced center'>";

        if ($result = $DB->request('glpi_plugin_ocsinventoryng_winsecdetails', ['computers_id' => $ID])) {
            echo "<table class='tab_cadre_fixehov'>";
            $colspan = 2;
            echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName($result->numrows()) .
                "</th></tr>";

            if ($result->numrows() != 0) {

                $header = "<tr><th>" . __('Property', 'ocsinventoryng') . "</th>";
                $header .= "<th>" . __('Value') . "</th>";
                $header .= "</tr>";
                echo $header;

                Session::initNavigateListItems(
                    __CLASS__,
                    //TRANS : %1$s is the itemtype name,
                    //        %2$s is the name of the item (used for headings of a list)
                    sprintf(
                        __('%1$s = %2$s'),
                        Computer::getTypeName(1),
                        $comp->getName()
                    )
                );

                foreach ($result as $data) {
                    echo "<tr class='tab_bg_2'>";
                    echo "<td colspan='2'>";
                    echo "<table class='tab_cadre_fixe'>";

                    // Display all fields in a structured way
                    $fields = [
                        'AM Engine Version' => 'amengineversion',
                        'AM Product Version' => 'amproductversion',
                        'AM Running Mode' => 'amrunningmode',
                        'AM Service Enabled' => 'amserviceenabled',
                        'AM Service Version' => 'amserviceversion',
                        'Antispyware Enabled' => 'antispywareenabled',
                        'Antispyware Signature Age' => 'antispywaresignatureage',
                        'Antispyware Signature Last Updated' => 'antispywaresignaturelastupdated',
                        'Antispyware Signature Version' => 'antispywaresignatureversion',
                        'Antivirus Enabled' => 'antivirusenabled',
                        'Antivirus Signature Age' => 'antivirussignatureage',
                        'Antivirus Signature Last Updated' => 'antivirussignaturelastupdated',
                        'Antivirus Signature Version' => 'antivirussignatureversion',
                        'Behavior Monitor Enabled' => 'behaviormonitorenabled',
                        'IOAV Protection Enabled' => 'ioavprotectionenabled',
                        'Is Tamper Protected' => 'istamperprotected',
                        'NIS Enabled' => 'nisenabled',
                        'NIS Engine Version' => 'nisengineversion',
                        'NIS Signature Age' => 'nissignatureage',
                        'NIS Signature Last Updated' => 'nissignaturelastupdated',
                        'NIS Signature Version' => 'nissignatureversion',
                        'On Access Protection Enabled' => 'onaccessprotectionenabled',
                        'Real Time Protection Enabled' => 'realtimeprotectionenabled',
                        'Tamper Protection Source' => 'tamperprotectionsource'
                    ];

                    foreach ($fields as $label => $field) {
                        if (isset($data[$field]) && !empty($data[$field])) {
                            echo "<tr class='tab_bg_1'>";
                            echo "<td><strong>" . __($label, 'ocsinventoryng') . "</strong></td>";
                            echo "<td>" . $data[$field] . "</td>";
                            echo "</tr>";
                        }
                    }

                    echo "</table>";
                    echo "</td>";
                    echo "</tr>";
                    Session::addToNavigateListItems(__CLASS__, $data['id']);

                    $self = new self();
                    $self->getFromDB($data['id']);
                }
                echo $header;

            } else {
                echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
            }

            echo "</table></br>";
        }
        echo "</div>";
        return true;
    }

    /**
     * @param \Computer $comp
     * @param string    $withtemplate
     *
     * @return bool
     */
    static function showForSimpleForItem(Computer $comp, $withtemplate = '')
    {
        global $DB;

        $ID = $comp->fields['id'];

        if (
            !$comp->getFromDB($ID)
            || !$comp->can($ID, READ)
        ) {
            return false;
        }

        if ($iterator = $DB->request('glpi_plugin_ocsinventoryng_winsecdetails', ['computers_id' => $ID])) {

            if (count($iterator)) {
                $data = $iterator->next();

                $self = new self();
                $self->getFromDB($data['id']);

                echo "<table class='tab_cadre_fixe'>";
                echo "<tr><th colspan='4'>" . __('Windows Security Center Details', 'ocsinventoryng') . "</th></tr>";

                $fields = [
                    'AM Engine Version' => 'amengineversion',
                    'AM Product Version' => 'amproductversion',
                    'AM Running Mode' => 'amrunningmode',
                    'AM Service Enabled' => 'amserviceenabled',
                    'AM Service Version' => 'amserviceversion',
                    'Antispyware Enabled' => 'antispywareenabled',
                    'Antispyware Signature Age' => 'antispywaresignatureage',
                    'Antispyware Signature Last Updated' => 'antispywaresignaturelastupdated',
                    'Antispyware Signature Version' => 'antispywaresignatureversion',
                    'Antivirus Enabled' => 'antivirusenabled',
                    'Antivirus Signature Age' => 'antivirussignatureage',
                    'Antivirus Signature Last Updated' => 'antivirussignaturelastupdated',
                    'Antivirus Signature Version' => 'antivirussignatureversion',
                    'Behavior Monitor Enabled' => 'behaviormonitorenabled',
                    'IOAV Protection Enabled' => 'ioavprotectionenabled',
                    'Is Tamper Protected' => 'istamperprotected',
                    'NIS Enabled' => 'nisenabled',
                    'NIS Engine Version' => 'nisengineversion',
                    'NIS Signature Age' => 'nissignatureage',
                    'NIS Signature Last Updated' => 'nissignaturelastupdated',
                    'NIS Signature Version' => 'nissignatureversion',
                    'On Access Protection Enabled' => 'onaccessprotectionenabled',
                    'Real Time Protection Enabled' => 'realtimeprotectionenabled',
                    'Tamper Protection Source' => 'tamperprotectionsource'
                ];

                foreach ($fields as $label => $field) {
                    if (isset($data[$field]) && !empty($data[$field])) {
                        echo "<tr class='tab_bg_1'>";
                        echo "<td><strong>" . __($label, 'ocsinventoryng') . "</strong></td>";
                        echo "<td>" . $data[$field] . "</td>";
                        echo "</tr>";
                    }
                }

                echo "</table>";
            }
        }
        return true;
    }
}
