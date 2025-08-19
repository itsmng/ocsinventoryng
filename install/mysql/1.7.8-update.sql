### Dump table glpi_plugin_ocsinventoryng_winsecdetails

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_winsecdetails`;
CREATE TABLE `glpi_plugin_ocsinventoryng_winsecdetails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `amengineversion` varchar(255) DEFAULT NULL,
  `amproductversion` varchar(255) DEFAULT NULL,
  `amrunningmode` varchar(255) DEFAULT NULL,
  `amserviceenabled` varchar(255) DEFAULT NULL,
  `amserviceversion` varchar(255) DEFAULT NULL,
  `antispywareenabled` varchar(255) DEFAULT NULL,
  `antispywaresignatureage` varchar(255) DEFAULT NULL,
  `antispywaresignaturelastupdated` varchar(255) DEFAULT NULL,
  `antispywaresignatureversion` varchar(255) DEFAULT NULL,
  `antivirusenabled` varchar(255) DEFAULT NULL,
  `antivirussignatureage` varchar(255) DEFAULT NULL,
  `antivirussignaturelastupdated` varchar(255) DEFAULT NULL,
  `antivirussignatureversion` varchar(255) DEFAULT NULL,
  `behaviormonitorenabled` varchar(255) DEFAULT NULL,
  `ioavprotectionenabled` varchar(255) DEFAULT NULL,
  `istamperprotected` varchar(255) DEFAULT NULL,
  `nisenabled` varchar(255) DEFAULT NULL,
  `nisengineversion` varchar(255) DEFAULT NULL,
  `nissignatureage` varchar(255) DEFAULT NULL,
  `nissignaturelastupdated` varchar(255) DEFAULT NULL,
  `nissignatureversion` varchar(255) DEFAULT NULL,
  `onaccessprotectionenabled` varchar(255) DEFAULT NULL,
  `realtimeprotectionenabled` varchar(255) DEFAULT NULL,
  `tamperprotectionsource` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_winsecdetails` TINYINT(1) NOT NULL DEFAULT '0';