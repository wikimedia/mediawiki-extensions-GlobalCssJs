<?php

class GlobalCssJsHooks {

	/**
	 * @param OutputPage $out
	 * @param array $modules
	 * @return bool
	 */
	static function onOutputPageScriptsForBottomQueue( OutputPage $out, array &$modules ) {
		$modules = array_merge( $modules, array( 'ext.globalCssJs.user', 'ext.globalCssJs.site' ) );

		return true;
	}

	/**
	 * Given a user, should we load scripts for them?
	 * @param User $user
	 * @return bool
	 */
	static function loadForUser( User $user ) {
		global $wgGlobalCssJsConfig;
		$wiki = $wgGlobalCssJsConfig['wiki'];
		return $wiki === wfWikiID() || ( $wiki !== false ) &&
			wfRunHooks( 'LoadGlobalCssJs', array( $user, $wiki, wfWikiID() ) );
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
		$resourceLoader->register( 'ext.globalCssJs.user', $user );

		if ( $wgUseGlobalSiteCssJs ) {
			$site = array(
				'class' => 'ResourceLoaderGlobalSiteModule',
			) + $wgGlobalCssJsConfig;
			$resourceLoader->register( 'ext.globalCssJs.site', $site );
		}

		return true;
	}

	/**
	 * @param EditPage $editPage
	 * @param OutputPage $output
	 * @return bool
	 */
	static function onEditPageshowEditForminitial( EditPage $editPage, OutputPage $output ) {
		global $wgGlobalCssJsConfig, $wgAllowUserJs, $wgAllowUserCss;
		$user = $output->getUser();
		if ( $wgGlobalCssJsConfig['wiki'] === wfWikiID() && $user->isLoggedIn()
			&& $editPage->formtype == 'initial' && $editPage->isCssJsSubpage
		) {
			$title = $editPage->getTitle();
			$name = $user->getName();
			if ( $wgAllowUserJs && $editPage->isJsSubpage && $title->getText() == $name . '/global.js' ) {
				$msg = 'globalcssjs-warning-js';
			} elseif ( $wgAllowUserCss && $editPage->isCssSubpage && $title->getText() == $name . '/global.css' ) {
				$msg = 'globalcssjs-warning-css';
			} else {
				// CSS or JS page, but not a global one
				return true;
			}
			$output->wrapWikiMsg( "<div id='mw-$msg'>\n$1\n</div>", array( $msg ) );
		}
		return true;
	}

	/**
	 * Convenince function to make a link to page that might be on another site
	 * @param Title $title
	 * @param string $msg message key
	 * @return string HTMl link
	 */
	protected static function makeCentralLink( Title $title, $msg ) {
		global $wgGlobalCssJsConfig;
		$message = wfMessage( $msg )->escaped();
		if ( $wgGlobalCssJsConfig['wiki'] === wfWikiID() ) {
			return Linker::link( $title, $message );
		} else {
			return WikiMap::makeForeignLink(
				$wgGlobalCssJsConfig['wiki'],
				"User:" . $title->getText(), // bug 66873, don't use localized namespace
				$message
			);
		}
	}

	static function onGetPreferences( User $user, array &$prefs ) {
		global $wgAllowUserCss, $wgAllowUserJs;

		if ( !$wgAllowUserCss && !$wgAllowUserJs ) {
			// No user CSS or JS allowed
			return true;
		}

		if ( !self::loadForUser( $user ) ) {
			// No global scripts for this user :(
			return true;
		}
		$ctx = RequestContext::getMain();
		$userName = $user->getName();
		$linkTools = array();
		if ( $wgAllowUserCss ) {
			$cssPage = Title::makeTitleSafe( NS_USER, $userName . '/global.css' );
			$linkTools[] = self::makeCentralLink( $cssPage, 'globalcssjs-custom-css' );
		}
		if ( $wgAllowUserJs ) {
			$jsPage = Title::makeTitleSafe( NS_USER, $userName . '/global.js' );
			$linkTools[] = self::makeCentralLink( $jsPage, 'globalcssjs-custom-js' );
		}

		$prefs = wfArrayInsertAfter(
			$prefs,
			array( 'globalcssjs' => array(
				'type' => 'info',
				'raw' => 'true',
				'default' => $ctx->getLanguage()->pipeList( $linkTools ),
				'label-message' => 'globalcssjs-custom-css-js',
				'section' => 'rendering/skin',
			) ),
			'commoncssjs'
		);
		return true;
	}
}
