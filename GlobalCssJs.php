<?php

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This is an extension to the MediaWiki software and cannot be used standalone" );
	die;
}

$wgExtensionCredits['other'][] = array(
	'name' => 'GlobalCssJs',
	'namemsg' => 'globalcssjs-name',
	'path' => __FILE__,
	'author' => array( 'Ryan Schmidt', 'Szymon Świerkosz', 'Kunal Mehta' ),
	'version' => '3.3.0',
	'url' => 'https://www.mediawiki.org/wiki/Extension:GlobalCssJs',
	'descriptionmsg' => 'globalcssjs-desc',
	'license-name' => 'GPL-2.0+',
);

/**
 * If set, users can put their custom JS and CSS code on pages
 * User:Name/global.js and User:Name/global.css on a central wiki.
 *
 * Administrators can put global code on MediaWiki:Global.js and .css
 *
 * Required properties:
 *   'wiki' - name of the central wiki database
 *   'source' - name of a ResourceLoader source pointing to the central wiki
 *
 * For example:
 * $wgGlobalCssJsConfig = array(
 *   'wiki' => 'metawiki',
 *   'source' => 'metawiki',
 * );
 * $wgResourceLoaderSources['metawiki'] = array(
 *   'apiScript' => '//meta.wikimedia.org/w/api.php',
 *   'loadScript' => '//meta.wikimedia.org/w/load.php',
 * );
 * @var array
 */
$wgGlobalCssJsConfig = array(
	'wiki' => false,
	'source' => false,
);

/**
 * If true, global site wide css / js is loaded from the central wiki
 * for all users on all page loads.
 * @var boolean
*/
$wgUseGlobalSiteCssJs = true;

$wgAutoloadClasses['ResourceLoaderGlobalModule'] = __DIR__ . '/ResourceLoaderGlobalModule.php';
$wgAutoloadClasses['ResourceLoaderGlobalSiteModule'] = __DIR__ . '/ResourceLoaderGlobalSiteModule.php';
$wgAutoloadClasses['ResourceLoaderGlobalUserModule'] = __DIR__ . '/ResourceLoaderGlobalUserModule.php';
$wgAutoloadClasses['GlobalCssJsHooks'] = __DIR__ . '/GlobalCssJs.hooks.php';

// Only for unit tests
$wgAutoloadClasses['ResourceLoaderGlobalModuleTestCase'] = __DIR__ . '/tests/ResourceLoaderGlobalModuleTestCase.php';

$wgMessagesDirs['GlobalCssJs'] = __DIR__ . '/i18n/core';
$wgConfigRegistry['globalcssjs'] = 'GlobalVarConfig::newInstance';

$wgExtensionFunctions[] = 'efGlobalCssJs';
function efGlobalCssJs() {
	global $wgGlobalCssJsConfig, $wgUseGlobalSiteCssJs,
		$wgMessagesDirs;
	if ( $wgGlobalCssJsConfig['wiki'] === wfWikiID() && $wgUseGlobalSiteCssJs ) {
		$wgMessagesDirs['GlobalCssJsCentral'] = __DIR__ . '/i18n/central';
	}
}

$wgHooks['BeforePageDisplay'][] = 'GlobalCssJsHooks::onBeforePageDisplay';
$wgHooks['ResourceLoaderRegisterModules'][] = 'GlobalCssJsHooks::onResourceLoaderRegisterModules';
$wgHooks['EditPage::showEditForm:initial'][] = 'GlobalCssJsHooks::onEditPageshowEditForminitial';
$wgHooks['GetPreferences'][] = 'GlobalCssJsHooks::onGetPreferences';
$wgHooks['UnitTestsList'][] = 'GlobalCssJsHooks::onUnitTestsList';
