# Plugin ocsinventoryng for ITSM-NG

![Logo ocsinventoryng](https://raw.githubusercontent.com/itsmng/ocsinventoryng/master/ocsinventoryng.png "Logo ocsinventoryng")

![Menu ocsinventoryng](https://raw.githubusercontent.com/itsmng/ocsinventoryng/master/wiki/menu.png "Menu ocsinventoryng")

This plugin is connector between OCS Inventory software and ITSM-NG (https://ocsinventory-ng.org/).

It bundle a lot of feature in order to import the following data from OCS Inventory :
- Computers information
- Softwares information
- SNMP Scanned devices
- IPDiscover scans
- OCS' plugins information

Supported plugin list : 
- Office licences
- Antivirus information
- Windows update status
- Uptime
- Teamviewer
- Windows users 
- Proxy configuration
- Extended operating system information
- Services
- Custom installed application
- Network drives
- Process list
- Bitlocker

Additional plugin can be supported if needed, please create an issue if you feel one plugin compatibility is missing !

OCS Inventory plugin provide a automated import of the computers present in the OCS database.
Its composed of a script in order to automate computer import and synchronization.

A web page displays the list of running scripts and their status alongside all the data related to the import.

__Note : This plugin is a fork of the former ocsinventoryng plugin__