<?php

class GlobalCssJsHooks {

	static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['enableglobalcssjs'] = array(
			'type' => 'toggle',
			'label-message' => 'tog-enableglobalcssjs',
			'section' => 'rendering/skin'
		);
	}

	/**
	 * @param &$out OutputPage
	 * @param &$skin Skin
	 * @return bool
	 */
	static function onBeforePageDisplay( &$out, &$skin ) {
		global $wgGlobalCssJsConfig;

		// Global site modules are loaded for everyone
		$out->addModules( 'ext.globalcssjs.site' );

		$user = $out->getUser();
		// Only load user modules for logged in users
		if ( $user->isAnon() ) {
			return true;
		}

		// Check if the user has disabled it
		if ( !$user->getBoolOption( 'enableglobalcssjs' ) ) {
			return true;
		}

		$wiki = $wgGlobalCssJsConfig['wiki'];

		// If we are on a different site, use a hook to allow other extensions
		// like CentralAuth verify that the same account exists on both sites
		if ( $wiki === wfWikiID() || ( $wiki !== false
			&&  wfRunHooks( 'LoadGlobalCssJs', array( $user, $wiki, wfWikiID() ) ) )
		) {
			$out->addModules( 'ext.globalcssjs.user' );
		}

		return true;
	}

	/**
	 * Registers a global user module.
	 * @param ResourceLoader $resourceLoader
	 * @return bool
	 */
	static function onResourceLoaderRegisterModules( &$resourceLoader ) {
		global $wgGlobalCssJsConfig;

		if ( $wgGlobalCssJsConfig['wiki'] === false || $wgGlobalCssJsConfig['source'] === false ) {
			// If not configured properly, exit
			wfDebugLog( 'GlobalCssJs', '$wgGlobalCssJsConfig has not been configured properly.' );
			return true;
		}

		$user = array(
			'class' => 'ResourceLoaderGlobalUserModule',
		) + $wgGlobalCssJsConfig;

		$site = array(
			'class' => 'ResourceLoaderGlobalSiteModule',
		) + $wgGlobalCssJsConfig;

		$resourceLoader->register( 'ext.globalcssjs.user', $user );
		$resourceLoader->register( 'ext.globalcssjs.site', $site );

		return true;
	}
}
