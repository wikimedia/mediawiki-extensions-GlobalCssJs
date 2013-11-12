<?php

class GlobalCssJsHooks {

	/**
	 * @param &$out OutputPage
	 * @param &$skin Skin
	 * @return bool
	 */
	static function onBeforePageDisplay( &$out, &$skin ) {
		global $wgGlobalCssJsConfig, $wgUseGlobalSiteCssJs;

		if ( $wgUseGlobalSiteCssJs ) {
			// Global site modules are loaded for everyone, if enabled
			$out->addModules( 'ext.globalcssjs.site' );
		}

		$user = $out->getUser();
		// Only load user modules for logged in users
		if ( $user->isAnon() ) {
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
		global $wgGlobalCssJsConfig, $wgUseGlobalSiteCssJs;

		if ( $wgGlobalCssJsConfig['wiki'] === false || $wgGlobalCssJsConfig['source'] === false ) {
			// If not configured properly, exit
			wfDebugLog( 'GlobalCssJs', '$wgGlobalCssJsConfig has not been configured properly.' );
			return true;
		}

		$user = array(
			'class' => 'ResourceLoaderGlobalUserModule',
		) + $wgGlobalCssJsConfig;
		$resourceLoader->register( 'ext.globalcssjs.user', $user );

		if ( $wgUseGlobalSiteCssJs ) {
			$site = array(
				'class' => 'ResourceLoaderGlobalSiteModule',
			) + $wgGlobalCssJsConfig;
			$resourceLoader->register( 'ext.globalcssjs.site', $site );
		}

		return true;
	}

	/**
	 * @param EditPage $editPage
	 * @param OutputPage $output
	 * @return bool
	 */
	static function onEditPageshowEditForminitial( EditPage $editPage, OutputPage $output ) {
		global $wgGlobalCssJsConfig;
		$user = $output->getUser();
		if ( $wgGlobalCssJsConfig['wiki'] === wfWikiID() && $user->isLoggedIn()
			&& $editPage->formtype == 'initial' && $editPage->isCssJsSubpage
		) {
			$title = $editPage->getTitle();
			$name = $user->getName();
			if ( $editPage->isJsSubpage && $title->getText() == $name . '/global.js' ) {
				$msg = 'globalcssjs-warning-js';
			} elseif ( $editPage->isCssSubpage && $title->getText() == $name . '/global.css' ) {
				$msg = 'globalcssjs-warning-css';
			} else {
				// CSS or JS page, but not a global one
				return true;
			}
			$output->wrapWikiMsg( "<div id='mw-$msg'>\n$1\n</div>", array( $msg ) );
		}
		return true;
	}

}
