<?php
include ("../../../inc/includes.php");

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('ocsinventoryng') || !$plugin->isActivated('ocsinventoryng')) {
   Html::displayNotFoundError();
}

$_SESSION['test'] = "TEST";

//check for ACLs
if (PluginOcsinventoryngSnmplinkRework::canView()) {
   //View is granted: display the list.

   //Add page header
   Html::header(
      __('OCS Inventory NG', 'ocsinventoryng'),
      $_SERVER['PHP_SELF'],
      'assets',
      'pluginocsinventoryngsnmplinkrework',
      'snmplinkrework'
   );

   Search::show('PluginOcsinventoryngSnmplinkRework');

   Html::footer();
} else {
   //View is not granted.
   Html::displayRightError();
}