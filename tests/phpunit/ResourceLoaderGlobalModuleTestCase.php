<?php

namespace MediaWiki\GlobalCssJs\Test;

use ConfigFactory;
use FauxRequest;
use MediaWikiTestCase;
use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;

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
	protected function getDefaultGlobalSettings( $skin = 'vector' ) {
		return [
			'wgUseSiteCss' => true,
			'wgUseSiteJs' => true,
			'wgUseGlobalSiteCssJs' => true,
			'wgAllowUserJs' => false,
			'wgAllowUserCss' => false,
			// ResourceLoaderContext will fallback to $wgDefaultSkin, so we set it
			// to an invalid skin to bypass some checks
			'wgDefaultSkin' => $skin,
			'wgGlobalCssJsConfig' => $this->getFakeOptions(),
		];
	}

	/**
	 * Get a fake ResourceLoaderContext object for testing
	 *
	 * @param array $options
	 * @return ResourceLoaderContext
	 */
	protected function getContext( array $options ) {
		$options += [
			'skin' => 'vector',
			'user' => 'TestUser',
		];
		$query = ResourceLoader::makeLoaderQuery(
			[], // modules; irrelevant
			'en',
			$options['skin'],
			$options['user'],
			null, // version
			false, // debug
			ResourceLoaderModule::TYPE_COMBINED
		);
		$rl = new ResourceLoader( ConfigFactory::getDefaultInstance()->makeConfig( 'main' ) );
		return new ResourceLoaderContext( $rl, new FauxRequest( $query ) );
	}

	protected function getFakeOptions() {
		return [
			'wiki' => wfWikiID(), // Don't call Hooks::loadForUser
			'source' => 'fakesource',
		];
	}

}
