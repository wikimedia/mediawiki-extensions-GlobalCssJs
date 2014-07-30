<?php

/**
 * Helper class to provide some useful functions when testing
 * subclasses of ResourceLoaderGlobalModule
 */
class ResourceLoaderGlobalModuleTestCase extends MediaWikiTestCase {

	/**
	 * Default global settings to pass to MediaWikiTestCase::setMwGlobals
	 *
	 * @param string $skin
	 * @return array
	 */
	public static function getDefaultGlobalSettings( $skin = 'vector' ) {
		return array(
			'wgUseSiteCss' => true,
			'wgUseSiteJs' => true,
			'wgUseGlobalSiteCssJs' => true,
			'wgAllowUserJs' => false,
			'wgAllowUserCss' => false,
			// ResourceLoaderContext will fallback to $wgDefaultSkin, so we set it
			// to an invalid skin to bypass some checks
			'wgDefaultSkin' => $skin,
			'wgGlobalCssJsConfig' => self::getFakeOptions(),
		);
	}

	/**
	 * Get a fake ResourceLoaderContext object for testing
	 *
	 * @param string $skin
	 * @return ResourceLoaderContext
	 */
	public static function getContext( $skin = 'vector' ) {
		$query = ResourceLoader::makeLoaderQuery(
			array(), // modules; irrelevant
			'en',
			$skin,
			'TestUser',
			null, // version
			false, // debug
			ResourceLoaderModule::TYPE_COMBINED,
			true, // printable
			false, // handheld
			array() // extra
		);

		return new ResourceLoaderContext( new ResourceLoader, new FauxRequest( $query ) );
	}

	public static function getFakeOptions() {
		return array(
			'wiki' => wfWikiID(), // Don't call GlobalCssJsHooks::loadForUser
			'source' => 'fakesource',
		);
	}

}