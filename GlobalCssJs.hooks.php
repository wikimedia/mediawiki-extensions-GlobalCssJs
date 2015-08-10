<?php

class GlobalCssJsHooks {

	private static function getConfig() {
		return ConfigFactory::getDefaultInstance()->makeConfig( 'globalcssjs' );
	}

	/**
	 * If site-wide JS/CSS is enabled, add MediaWiki:Global.js/css messages
	 *
	 * @todo This probably doesn't work on all setups and is hacky.
	 */
	public static function onExtensionFunctions() {
		global $wgMessagesDirs;
		$config = self::getConfig();
		$rlConfig = $config->get( 'GlobalCssJsConfig' );
		if ( $rlConfig['wiki'] === wfWikiID() && $config->get( 'UseGlobalSiteCssJs' ) ) {
			$wgMessagesDirs['GlobalCssJsCentral'] = __DIR__ . '/i18n/central';
		}
	}

	/**
	 * @param OutputPage $out
	 * @return bool
	 */
	static function onBeforePageDisplay( OutputPage $out ) {
		$out->addModuleStyles( array( 'ext.globalCssJs.user.styles', 'ext.globalCssJs.site.styles' ) );
		$out->addModuleScripts( array( 'ext.globalCssJs.user', 'ext.globalCssJs.site' ) );
		// Add help link
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );
		if ( $config['wiki'] !== wfWikiID() ) {
			return true;
		}

		$useSiteCssJs = self::getConfig()->get( 'UseGlobalSiteCssJs' );
		$title = $out->getTitle();
		$user = $out->getUser();
		$name = $user->getName();
		if ( $useSiteCssJs && $title->inNamespace( NS_MEDIAWIKI )
			&& ( $title->getText() === 'Global.css' || $title->getText() === 'Global.js')
		) {
			$out->addHelpLink( 'Help:Extension:GlobalCssJs' );
		} elseif ( $user->isLoggedIn() && $title->inNamespace( NS_USER )
			&& ( $title->getText() === "$name/global.js" || $title->getText() === "$name/global.css" )
		) {
			$out->addHelpLink( 'Help:Extension:GlobalCssJs' );
		}
		return true;
	}

	/**
	 * Given a user, should we load scripts for them?
	 * @param User $user
	 * @return bool
	 */
	static function loadForUser( User $user ) {
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );
		$wiki = $config['wiki'];
		return $wiki === wfWikiID() || ( $wiki !== false ) &&
			Hooks::run( 'LoadGlobalCssJs', array( $user, $wiki, wfWikiID() ) );
	}

	/**
	 * Registers a global user module.
	 * @param ResourceLoader $resourceLoader
	 * @return bool
	 */
	static function onResourceLoaderRegisterModules( &$resourceLoader ) {
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );

		if ( $config['wiki'] === false || $config['source'] === false ) {
			// If not configured properly, exit
			wfDebugLog( 'GlobalCssJs', '$wgGlobalCssJsConfig has not been configured properly.' );
			return true;
		}

		$userJs = array(
			'class' => 'ResourceLoaderGlobalUserModule',
			'type' => 'script',
		) + $config;
		$resourceLoader->register( 'ext.globalCssJs.user', $userJs );

		$userCss = array(
			'position' => 'top',
			'class' => 'ResourceLoaderGlobalUserModule',
			'type' => 'style',
		) + $config;
		$resourceLoader->register( 'ext.globalCssJs.user.styles', $userCss );

		$siteJs = array(
			'class' => 'ResourceLoaderGlobalSiteModule',
			'type' => 'script',
		) + $config;
		$resourceLoader->register( 'ext.globalCssJs.site', $siteJs );

		$siteCss = array(
			'position' => 'top',
			'class' => 'ResourceLoaderGlobalSiteModule',
			'type' => 'style',
		) + $config;
		$resourceLoader->register( 'ext.globalCssJs.site.styles', $siteCss );

		return true;
	}

	/**
	 * @param EditPage $editPage
	 * @param OutputPage $output
	 * @return bool
	 */
	static function onEditPageshowEditForminitial( EditPage $editPage, OutputPage $output ) {
		$gcssjsConfig = self::getConfig()->get( 'GlobalCssJsConfig' );
		$config = $output->getConfig();
		$user = $output->getUser();
		if ( $gcssjsConfig['wiki'] === wfWikiID() && $user->isLoggedIn()
			&& $editPage->formtype == 'initial' && $editPage->isCssJsSubpage
		) {
			$title = $editPage->getTitle();
			$name = $user->getName();
			if ( $config->get( 'AllowUserJs' ) && $editPage->isJsSubpage && $title->getText() == $name . '/global.js' ) {
				$msg = 'globalcssjs-warning-js';
			} elseif ( $config->get( 'AllowUserCss' ) && $editPage->isCssSubpage && $title->getText() == $name . '/global.css' ) {
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
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );
		$message = wfMessage( $msg )->escaped();
		if ( $config['wiki'] === wfWikiID() ) {
			return Linker::link( $title, $message );
		} else {
			return WikiMap::makeForeignLink(
				$config['wiki'],
				"User:" . $title->getText(), // bug 66873, don't use localized namespace
				$message
			);
		}
	}

	static function onGetPreferences( User $user, array &$prefs ) {
		$ctx = RequestContext::getMain();
		$allowUserCss = $ctx->getConfig()->get( 'AllowUserCss' );
		$allowUserJs = $ctx->getConfig()->get( 'AllowUserJs' );

		if ( !$allowUserCss && !$allowUserJs ) {
			// No user CSS or JS allowed
			return true;
		}

		if ( !self::loadForUser( $user ) ) {
			// No global scripts for this user :(
			return true;
		}
		$userName = $user->getName();
		$linkTools = array();
		if ( $allowUserCss ) {
			$cssPage = Title::makeTitleSafe( NS_USER, $userName . '/global.css' );
			$linkTools[] = self::makeCentralLink( $cssPage, 'globalcssjs-custom-css' );
		}
		if ( $allowUserJs ) {
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

	/**
	 * Load our unit tests
	 */
	public static function onUnitTestsList( array &$files ) {
		$files = array_merge( $files, glob( __DIR__ . '/tests/*Test.php' ) );

		return true;
	}
}
