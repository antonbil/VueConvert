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
   - VueConvertSpecialPage.php
   - VueConvertApi.php
   - VueConvertLogic.php
   - VueConvertApi.i18n.php
   - VueConvert_body.php
   - genim.rb

 - The following line needs to be added to LocalSettings.php

 require_once("$IP/extensions/VueConvert/VueConvertSpecialPage.php");
$wgGroupPermissions['user']['vueconvert'] = true;
$wgAvailableRights[] = 'vueconvert';
require_once "$IP/extensions/VueConvert/VueConvertApi.php";

prerequisites: ruby and ruby-xml-simple packages must be installed

USE:
 - The script will appear on the "Special Pages" page as "VueConvert"
 - The script can be linked to at the following location:
 	{MediaWiki Root Location}/index.php/Special:VueConvert

a call to the api can be done with:

  $main=$this;
//call using api
  $params = new DerivativeRequest( 
	  $main->getRequest(),
	  array(
	    'action' => 'vueconvert',//action defined in new api
	    'postfix' => $postfix,//fill parameters of api
	    'prefix' => $prefix,
	    'ymlcontent' => $ymlcontent,
	    'vuecontent' => $vuecontent,
	    'vuename' => $vuename,
 	  true
)
  );
  $api = new ApiMain( $params ,true);//true = enable write: important!
  $api->execute();
  $templatetext= $api->getResult()->getResultData()['vueconvert']['templatetext'];
