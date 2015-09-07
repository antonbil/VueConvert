 <?php

// Take credit for your work, in the "api" category.
//add require_once "$IP/extensions/VueConvert/VueConvertApi.php"; to LocalSettings.php 
//test: api.php ? action=vueconvert&face=O_o&format=xml Let op: geen spaties na vueconvert!
$wgExtensionCredits['api'][] = array(

	'path' => __FILE__,

	// The name of the extension, which will appear on Special:Version.
	'name' => 'Convert Vue-file',

	// A description of the extension, which will appear on Special:Version.
	'description' => 'Convert Vue-file',

	// Alternatively, you can specify a message key for the description.
	'descriptionmsg' => 'vueconvertapi-desc',

	// The version of the extension, which will appear on Special:Version.
	// This can be a number or a string.
	'version' => 1, 

	// Your name, which will appear on Special:Version.
	'author' => 'Anton Bil',

	// The URL to a wiki page/web page with information about the extension,
	// which will appear on Special:Version.
	'url' => 'https://www.mediawiki.org/wiki/API:Extensions',

);

// Map class name to filename for autoloading
$wgAutoloadClasses['VueConvertApi'] = __DIR__ . '/VueConvertApi_body.php';

// Map module name to class name
$wgAPIModules['vueconvert'] = 'VueConvertApi';

// Load the internationalization file
$wgExtensionMessagesFiles['VueConvertApi'] = __DIR__ . '/VueConvertApi.i18n.php';//constant was different!?

// Return true so that MediaWiki continues to load extensions.
return true;