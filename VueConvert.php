<?php
/*
 *
 * ========================= VueConvert.php =================================
 * Revision Information
 *   Changed: $LastChangedDate$
 *   Revision: $LastChangedRevision$
 *   Last Update By: $Author$
 */
 
/*       1         2         3         4         5         6         7         8
12345678901234567890123456798012345678901234567890123456789012346579801234567890
*/

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/VueConvert/VueConvert.php" );
EOT;
        exit( 1 );
}

//fix permissions. Can be executed by user group
$wgGroupPermissions['user']['vueconvert'] = true;
$wgAvailableRights[] = 'vueconvert';

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Vue Convert',
	'version' => '1.0',
	'author' => 'Anton Bil',
	'url' => 'http://www.mediawiki.org/wiki/Extension:VueConvert',
	'descriptionmsg' => 'vueconvert-desc',
);

$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['VueConvert'] = $dir . 'VueConvert_body.php';
$wgSpecialPages['VueConvert'] = 'VueConvert';
$wgExtensionMessagesFiles['VueConvert'] = $dir . 'VueConvert.i18n.php';
$wgExtensionAliasesFiles['VueConvert'] = $dir . 'VueConvert.alias.php';
$wgMessagesDirs['VueConvert'] = __DIR__ . '/i18n';//. '/i18n';
