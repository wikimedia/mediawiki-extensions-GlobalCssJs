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
	 * @param array $options
	 * @return ResourceLoaderContext
	 */
	public static function getContext( array $options ) {
		$options += array(
			'skin' => 'vector',
			'user' => 'TestUser',
		);
		$query = ResourceLoader::makeLoaderQuery(
			array(), // modules; irrelevant
			'en',
			$options['skin'],
			$options['user'],
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