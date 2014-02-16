<?php

if( !defined( 'MEDIAWIKI' ) ) {
    echo( "This is an extension to the MediaWiki software and cannot be used standalone" );
    die;
}

$wgExtensionCredits['other'][] = array(
	'name' => 'Global CSS/JS',
	'author' => array( 'Ryan Schmidt', 'Szymon Świerkosz', 'Kunal Mehta' ),
	'version' => '3.0.0',
	'url' => 'https://www.mediawiki.org/wiki/Extension:GlobalCssJs',
	'descriptionmsg' => 'globalcssjs-desc',
);

/**
 * If set, users can put their custom JS and CSS code on pages
 * User:Name/global.js and User:Name/global.css on a central wiki.
 *
 * Administrators can put global code on MediaWiki:Global.js and .css
 *
 * Required properties:
 * 	'wiki' - name of the central wiki database
 * 	'source' - name of a ResourceLoader source pointing to the central wiki
 *
 * For example:
 * $wgGlobalCssJsConfig = array(
 *     'wiki' => 'metawiki',
 *     'source' => 'metawiki',
 * );
 * $wgResourceLoaderSources['metawiki'] = array(
 *     'apiScript' => '//meta.wikimedia.org/w/api.php',
 *     'loadScript' => '//meta.wikimedia.org/w/load.php',
 * );
 * @var array
 */
$wgGlobalCssJsConfig = array(
	'wiki' => false,
	'source' => false,
);

$wgAutoloadClasses['ResourceLoaderGlobalModule'] = __DIR__ . '/ResourceLoaderGlobalModule.php';
$wgAutoloadClasses['ResourceLoaderGlobalSiteModule'] = __DIR__ . '/ResourceLoaderGlobalSiteModule.php';
$wgAutoloadClasses['ResourceLoaderGlobalUserModule'] = __DIR__ . '/ResourceLoaderGlobalUserModule.php';
$wgAutoloadClasses['GlobalCssJsHooks'] = __DIR__ . '/GlobalCssJs.hooks.php';
$wgExtensionMessagesFiles['GlobalCssJs'] = __DIR__ . '/GlobalCssJs.i18n.php';
$wgExtensionFunctions[] = function () {
	global $wgGlobalCssJsConfig;
	if ( $wgGlobalCssJsConfig['wiki'] === wfWikiID() ) {
		$wgExtensionMessagesFiles['GlobalCssJsCentral'] = __DIR__ . '/GlobalCssJs.central.i18n.php';
	}
};

$wgHooks['BeforePageDisplay'][] = 'GlobalCssJsHooks::onBeforePageDisplay';
$wgHooks['ResourceLoaderRegisterModules'][] = 'GlobalCssJsHooks::onResourceLoaderRegisterModules';
