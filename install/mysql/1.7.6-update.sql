### Dump table glpi_plugin_ocsinventoryng_ipdiscoversnmpreconciliation

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ipdiscoversnmpreconciliation`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ipdiscoversnmpreconciliation` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `merge_ipd_in_snmp` INT(11) NOT NULL DEFAULT '0',
    `switch_to_identified_type` VARCHAR(255) DEFAULT NULL,
    `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;