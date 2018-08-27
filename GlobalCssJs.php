<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'GlobalCssJs' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['GlobalCssJs'] = __DIR__ . '/i18n/core';
	wfWarn(
		'Deprecated PHP entry point used for GlobalCssJs extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the GlobalCssJs extension requires MediaWiki 1.31+' );
}
