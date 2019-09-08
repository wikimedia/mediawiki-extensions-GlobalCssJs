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
	 * Handler for wgExtensionFunctions.
	 *
	 * If site-wide global CSS/JS is enabled, load the stubs for those
	 * gadget pages (implemented as interface messages).
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
	 * Handler for BeforePageDisplay hook.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @param OutputPage $out
	 */
	public static function onBeforePageDisplay( OutputPage $out ) {
		$config = self::getConfig();
		$useSiteCssJs = $config->get( 'UseGlobalSiteCssJs' );

		$out->addModuleStyles( [ 'ext.globalCssJs.user.styles' ] );
		$out->addModules( [ 'ext.globalCssJs.user' ] );
		if ( $useSiteCssJs ) {
			$out->addModuleStyles( [ 'ext.globalCssJs.site.styles' ] );
			$out->addModules( [ 'ext.globalCssJs.site' ] );
		}

		// Add help link
		$rlConfig = $config->get( 'GlobalCssJsConfig' );
		if ( $rlConfig['wiki'] === wfWikiID() ) {
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
		}
	}

	/**
	 * Given a user, should we load scripts for them?
	 *
	 * @param User $user
	 * @return bool
	 */
	public static function loadForUser( User $user ) {
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );
		$wiki = $config['wiki'];
		if ( $wiki === wfWikiID() ) {
			return true;
		}
		if ( $wiki === false ) {
			// Not configured, don't load anything
			return false;
		}

		$lookup = CentralIdLookup::factory();
		return $lookup->isAttached( $user ) && $lookup->isAttached( $user, $wiki );
	}

	/**
	 * Handler for ResourceLoaderRegisterModules hook.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderRegisterModules
	 * @param ResourceLoader &$resourceLoader
	 */
	public static function onResourceLoaderRegisterModules( &$resourceLoader ) {
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );

		if ( $config['wiki'] === false || $config['source'] === false ) {
			// Not configured yet, don't register any modules.
			return;
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

		if ( self::getConfig()->get( 'UseGlobalSiteCssJs' ) ) {
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
		}
	}

	/**
	 * Handler for 'EditPage::showEditForm:initial' hook.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPage::showEditForm:initial
	 * @param EditPage $editPage
	 * @param OutputPage $output
	 */
	public static function onEditPageshowEditForminitial( EditPage $editPage, OutputPage $output ) {
		$gcssjsConfig = self::getConfig()->get( 'GlobalCssJsConfig' );
		$config = $output->getConfig();
		$user = $output->getUser();
		$title = $editPage->getTitle();
		if ( $gcssjsConfig['wiki'] === wfWikiID() && $user->isLoggedIn()
			&& $editPage->formtype == 'initial' && $title->isUserConfigPage()
		) {
			$title = $editPage->getTitle();
			$name = $user->getName();
			if ( $config->get( 'AllowUserJs' ) && $title->isUserJsConfigPage() &&
				$title->getText() == $name . '/global.js'
			) {
				$msg = 'globalcssjs-warning-js';
			} elseif ( $config->get( 'AllowUserCss' ) && $title->isUserCssConfigPage() &&
				$title->getText() == $name . '/global.css'
			) {
				$msg = 'globalcssjs-warning-css';
			} else {
				// CSS or JS page, but not a global one
				return;
			}
			$output->wrapWikiMsg( "<div id='mw-$msg'>\n$1\n</div>", [ $msg ] );
		}
	}

	/**
	 * Convenince function to make a link to page that might be on another site.
	 *
	 * @param Title $title
	 * @param string $msg message key
	 * @return string HTMl link
	 * @suppress SecurityCheck-DoubleEscaped phan false positive
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

	/**
	 * Handler for GetPreferences hook.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 * @param User $user
	 * @param array &$prefs
	 */
	public static function onGetPreferences( User $user, array &$prefs ) {
		$ctx = RequestContext::getMain();
		$allowUserCss = $ctx->getConfig()->get( 'AllowUserCss' );
		$allowUserJs = $ctx->getConfig()->get( 'AllowUserJs' );

		if ( !$allowUserCss && !$allowUserJs ) {
			// No user CSS or JS allowed
			return;
		}

		if ( !self::loadForUser( $user ) ) {
			// No global scripts for this user :(
			return;
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
	}
}
