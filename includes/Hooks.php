<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 * @author Szymon Świerkosz
 * @author Kunal Mehta
 */

namespace MediaWiki\GlobalCssJs;

use CentralIdLookup;
use ConfigFactory;
use EditPage;
use Linker;
use OutputPage;
use RequestContext;
use ResourceLoader;
use Title;
use User;
use WikiMap;

class Hooks {

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
		$out->addModuleStyles( [ 'ext.globalCssJs.user.styles', 'ext.globalCssJs.site.styles' ] );
		$out->addModuleScripts( [ 'ext.globalCssJs.user', 'ext.globalCssJs.site' ] );
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
			&& ( $title->getText() === 'Global.css' || $title->getText() === 'Global.js' )
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
		if ( $wiki === wfWikiID() ) {
			return true;
		} elseif ( $wiki === false ) {
			// Not configured, don't load anything
			return false;
		}

		$lookup = CentralIdLookup::factory();
		return $lookup->isAttached( $user ) && $lookup->isAttached( $user, $wiki );
	}

	/**
	 * Registers a global user module.
	 * @param ResourceLoader &$resourceLoader
	 * @return bool
	 */
	static function onResourceLoaderRegisterModules( &$resourceLoader ) {
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );

		if ( $config['wiki'] === false || $config['source'] === false ) {
			// If not configured properly, exit
			wfDebugLog( 'GlobalCssJs', '$wgGlobalCssJsConfig has not been configured properly.' );
			return true;
		}

		$userJs = [
			'class' => ResourceLoaderGlobalUserModule::class,
			'type' => 'script',
		] + $config;
		$resourceLoader->register( 'ext.globalCssJs.user', $userJs );

		$userCss = [
			'class' => ResourceLoaderGlobalUserModule::class,
			'type' => 'style',
		] + $config;
		$resourceLoader->register( 'ext.globalCssJs.user.styles', $userCss );

		$siteJs = [
			'class' => ResourceLoaderGlobalSiteModule::class,
			'type' => 'script',
		] + $config;
		$resourceLoader->register( 'ext.globalCssJs.site', $siteJs );

		$siteCss = [
			'class' => ResourceLoaderGlobalSiteModule::class,
			'type' => 'style',
		] + $config;
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
		$title = $editPage->getTitle();
		if ( $gcssjsConfig['wiki'] === wfWikiID() && $user->isLoggedIn()
			&& $editPage->formtype == 'initial' && $title->isCssJsSubpage()
		) {
			$title = $editPage->getTitle();
			$name = $user->getName();
			if ( $config->get( 'AllowUserJs' ) && $title->isJsSubpage() &&
				$title->getText() == $name . '/global.js'
			) {
				$msg = 'globalcssjs-warning-js';
			} elseif ( $config->get( 'AllowUserCss' ) && $title->isCssSubpage() &&
				$title->getText() == $name . '/global.css'
			) {
				$msg = 'globalcssjs-warning-css';
			} else {
				// CSS or JS page, but not a global one
				return true;
			}
			$output->wrapWikiMsg( "<div id='mw-$msg'>\n$1\n</div>", [ $msg ] );
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
		} elseif ( isset( $config['baseurl'] ) && $config['baseurl'] !== false ) {
			return Linker::makeExternalLink(
				// bug 66873, don't use localized namespace
				$config['baseurl'] . '/User:' .
					htmlspecialchars( $title->getText(), ENT_QUOTES ),
				$message
			);
		} else {
			return WikiMap::makeForeignLink(
				$config['wiki'],
				'User:' . $title->getText(), // bug 66873, don't use localized namespace
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
		$linkTools = [];
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
			[ 'globalcssjs' => [
				'type' => 'info',
				'raw' => 'true',
				'default' => $ctx->getLanguage()->pipeList( $linkTools ),
				'label-message' => 'globalcssjs-custom-css-js',
				'section' => 'rendering/skin',
			] ],
			'commoncssjs'
		);
		return true;
	}
}
