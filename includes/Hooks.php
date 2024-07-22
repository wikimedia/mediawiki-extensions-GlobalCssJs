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

use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Hook\EditPage__showEditForm_initialHook;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use Skin;

//phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
class Hooks implements
	BeforePageDisplayHook,
	ResourceLoaderRegisterModulesHook,
	EditPage__showEditForm_initialHook,
	GetPreferencesHook
{

	/**
	 * @return Config
	 */
	private static function getConfig(): Config {
		return MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'globalcssjs' );
	}

	/**
	 * Helper function for checking whether the extension has been configured correctly.
	 *
	 * @param array $config
	 * @return bool
	 */
	private static function isConfiguredCorrectly( $config ) {
		return !( $config['wiki'] === false || $config['source'] === false );
	}

	/**
	 * Handler for BeforePageDisplay hook.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$config = self::getConfig();
		$useSiteCssJs = $config->get( 'UseGlobalSiteCssJs' );
		$globalCssJsConfig = $config->get( 'GlobalCssJsConfig' );

		if ( !self::isConfiguredCorrectly( $globalCssJsConfig ) ) {
			// Not configured yet, don't register any modules.
			return;
		}

		$out->addModuleStyles( [ 'ext.globalCssJs.user.styles' ] );
		$out->addModules( [ 'ext.globalCssJs.user' ] );
		if ( $useSiteCssJs ) {
			$out->addModuleStyles( [ 'ext.globalCssJs.site.styles' ] );
			$out->addModules( [ 'ext.globalCssJs.site' ] );
		}

		// Add help link
		$rlConfig = $config->get( 'GlobalCssJsConfig' );
		if ( $rlConfig['wiki'] === WikiMap::getCurrentWikiId() ) {
			$title = $out->getTitle();
			$user = $out->getUser();
			$name = $user->getName();
			if ( $useSiteCssJs && $title->inNamespace( NS_MEDIAWIKI )
				&& ( $title->getText() === 'Global.css' || $title->getText() === 'Global.js' )
			) {
				$out->addHelpLink( 'Help:Extension:GlobalCssJs' );
			} elseif ( $user->isNamed() && $title->inNamespace( NS_USER )
				&& ( $title->getText() === "$name/global.js" || $title->getText() === "$name/global.css" )
			) {
				$out->addHelpLink( 'Help:Extension:GlobalCssJs' );
			}
		}
	}

	/**
	 * Given a user, should we load scripts for them?
	 *
	 * @param UserIdentity $user
	 * @return bool
	 */
	public static function loadForUser( UserIdentity $user ): bool {
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );
		$wiki = $config['wiki'];
		if ( $wiki === WikiMap::getCurrentWikiId() ) {
			return true;
		}
		if ( $wiki === false ) {
			// Not configured, don't load anything
			return false;
		}

		$lookup = MediaWikiServices::getInstance()
			->getCentralIdLookupFactory()
			->getLookup();
		return $lookup->isAttached( $user ) && $lookup->isAttached( $user, $wiki );
	}

	/**
	 * Handler for ResourceLoaderRegisterModules hook.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderRegisterModules
	 * @param ResourceLoader $resourceLoader
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );

		if ( !self::isConfiguredCorrectly( $config ) ) {
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
	public function onEditPage__showEditForm_initial( $editPage, $output ) {
		$gcssjsConfig = self::getConfig()->get( 'GlobalCssJsConfig' );
		$config = $output->getConfig();
		$user = $output->getUser();
		$title = $editPage->getTitle();
		if ( $gcssjsConfig['wiki'] === WikiMap::getCurrentWikiId() && $user->isNamed()
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
	protected static function makeCentralLink( Title $title, string $msg ): string {
		$config = self::getConfig()->get( 'GlobalCssJsConfig' );
		$message = wfMessage( $msg );
		if ( $config['wiki'] === WikiMap::getCurrentWikiId() ) {
			return MediaWikiServices::getInstance()->getLinkRenderer()->makeLink( $title, $message->text() );
		} elseif ( isset( $config['baseurl'] ) && $config['baseurl'] !== false ) {
			return Linker::makeExternalLink(
				// bug 66873, don't use localized namespace
				$config['baseurl'] . '/User:' .
					htmlspecialchars( $title->getText(), ENT_QUOTES ),
				$message->escaped()
			);
		} else {
			return WikiMap::makeForeignLink(
				$config['wiki'],
				'User:' . $title->getText(), // bug 66873, don't use localized namespace
				$message->escaped()
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
	public function onGetPreferences( $user, &$prefs ) {
		$ctx = RequestContext::getMain();
		$allowUserCss = $ctx->getConfig()->get( 'AllowUserCss' );
		$allowUserJs = $ctx->getConfig()->get( 'AllowUserJs' );

		if ( !$allowUserCss && !$allowUserJs ) {
			// No user CSS or JS allowed
			return;
		}

		$safeMode = MediaWikiServices::getInstance()->getUserOptionsLookup()->getOption( $user, 'forcesafemode' );
		if ( $safeMode ) {
			// Safe mode is enabled
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
