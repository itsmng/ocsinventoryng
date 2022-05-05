CREATE TABLE `glpi_plugin_ocsinventoryng_snmplinkreworks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object` varchar(255) NOT NULL,
  `glpi_col` varchar(255) NOT NULL,
  `ocs_snmp_type_id` int(11) NOT NULL,
  `ocs_snmp_label_id` int(11) NOT NULL,
  `is_reconsiliation` TINYINT(1) NOT NULL DEFAULT 0,
  `ocs_srv` INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;