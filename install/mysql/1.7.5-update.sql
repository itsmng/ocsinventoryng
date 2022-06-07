### Dump table glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworknoninv` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ocs_type` VARCHAR(40) DEFAULT NULL,
    `link_field` VARCHAR(40) DEFAULT NULL,
    `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkidt` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ocs_type` VARCHAR(40) DEFAULT NULL,
    `glpi_obj` VARCHAR(40) DEFAULT NULL,
    `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkdelete

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkdelete`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ipdiscoverocslinksreworkdelete` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `status` VARCHAR(40) DEFAULT NULL,
    `remove` INT(11) DEFAULT 0,
    `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;