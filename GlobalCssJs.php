<?php
/**
* Global CSS/JS loader by Ryan Schmidt
* Put global site CSS/JS in the "central" wiki's MediaWiki:Global.css and MediaWiki:Global.js respectively
* Put global user CSS/JS in the "central" wiki's User:Yourname/global.css and User:Yourname/global.js respectively
* See https://www.mediawiki.org/wiki/Extension:GlobalCssJs
*/
 
if( !defined( 'MEDIAWIKI' ) ) {
    echo( "This is an extension to the MediaWiki software and cannot be used standalone" );
    die;
}
 
$wgExtensionCredits['other'][] = array(
'name' => 'Global CSS/JS',
'author' => 'Ryan Schmidt',
'version' => '2.0.1',
'url' => 'https://www.mediawiki.org/wiki/Extension:GlobalCssJs',
'descriptionmsg' => 'globalcssjs-desc',
);
 
$wgHooks['GetPreferences'][] = 'wfGlobalCssJsAddPrefToggle';
$wgHooks['BeforePageDisplay'][] = 'wfGlobalCssJs';
$wgExtensionMessagesFiles['GlobalCssJs'] = __DIR__ . '/GlobalCssJs.i18n.php';


function wfGlobalCssJsAddPrefToggle( User $user, array &$preferences ) {
	$preferences['enableglobalcssjs'] = array(
		'type' => 'toggle',
		'label-message' => 'tog-enableglobalcssjs',
		'section' => 'rendering/skin'
	);
}

/**
 * @param OutputPage $out
 * @param Skin $skin
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
 * @return bool
 */
function wfGlobalCssJs( OutputPage &$out, Skin &$skin ) {
    global $wgGlobalCssJsUrl, $wgAllowUserCss, $wgAllowUserJs, $wgJsMimeType, $wgUseSiteCss, $wgUseSiteJs;
    if( !isset($wgGlobalCssJsUrl) || !$out->getUser()->isLoggedIn() )
            return true;
    $name = urlencode($out->getUser()->getName());
    $url = $wgGlobalCssJsUrl; // just makes the lines shorter, nothing more.
    $toggle = $out->getUser()->getBoolOption('enableglobalcssjs');
    if($wgUseSiteCss)
        $out->addScript('<style type="text/css">/*<![CDATA[*/ @import "' . $url . '?title=MediaWiki:Global.css&action=raw&ctype=text/css";/*]]>*/</style>' . "\n");
    if($wgUseSiteJs)
        $out->addScript('<script type="' . $wgJsMimeType . '" src="' . $url . '?title=MediaWiki:Global.js&action=raw&ctype=' . $wgJsMimeType . '&dontcountme=s"></script>' . "\n");
    if($wgAllowUserCss)
        $out->addScript('<style type="text/css">/*<![CDATA[*/ @import "' . $url . '?title=User:' . $name . '/global.css&action=raw&ctype=text/css"; /*]]>*/</style>' . "\n");
    if($wgAllowUserJs)
        $out->addScript('<script type="' . $wgJsMimeType . '" src="' . $url . '?title=User:' . $name . '/global.js&action=raw&ctype=' . $wgJsMimeType . '&dontcountme=s"></script>' . "\n");
    return true;
}
