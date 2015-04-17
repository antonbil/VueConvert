# VueConvert-byAB
readme.txt

MODULE:
This extension adds a special script page that will convert a vue-files
based on the data in a yml-file.
It uses the ruby-script genim.rb to do the actual conversion
INSTALLATION:
 - The following files should be added to the extensions\VueConvert
 directory under the wiki install directory.
   - VueConvert.alias.php
   - VueConvert.i18n.php
   - VueConvert.php
   - VueConvert_body.php
   - genim.rb

 - The following line needs to be added to LocalSettings.php

 require_once("$IP/extensions/VueConvert/VueConvert.php");
$wgGroupPermissions['user']['vueconvert'] = true;
$wgAvailableRights[] = 'vueconvert';

prerequisites: ruby and ruby-xml-simple packages must be installed

USE:
 - The script will appear on the "Special Pages" page as "VueConvert"
 - The script can be linked to at the following location:
 	{MediaWiki Root Location}/index.php/Special:VueConvert