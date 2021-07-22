<?php

namespace MediaWiki\GlobalCssJs\Test;

use HashConfig;
use MediaWiki\MediaWikiServices;
use ResourceLoaderContext;
use Title;
use User;

/**
 * Helper class for testing subclasses of ResourceLoaderGlobalModule
 */
trait ResourceLoaderGlobalModuleTestTrait {
	public function setUp(): void {
		parent::setUp();

		$this->registerInConfigFactory();
	}

	protected function registerInConfigFactory() {
		// Hacky stub so that Hooks::loadForUser is satisfied.
		MediaWikiServices::getInstance()->getConfigFactory()->register(
			'globalcssjs',
			new HashConfig( [ 'GlobalCssJsConfig' => $this->getFakeOptions() ] )
		);
	}

	/**
	 * Get the default test settings for a HashConfig instance.
	 * @return array
	 */
	protected function getTestSettings() {
		return [
			'UseSiteCss' => true,
			'UseSiteJs' => true,
			'UseGlobalSiteCssJs' => true,
			'AllowUserJs' => false,
			'AllowUserCss' => false,
		];
	}

	/**
	 * Get a fake ResourceLoaderContext object for testing.
	 *
	 * @param array $options
	 * @return ResourceLoaderContext
	 */
	protected function makeContext( array $options ) {
		$context = $this->createMock( ResourceLoaderContext::class );
		$context->method( 'getSkin' )->willReturn( $options['skin'] ?? 'vector' );
		$context->method( 'getUser' )->willReturn( $options['user'] );
		if ( $options['user'] === 'TestUser' ) {
			// Logged-in
			$user = $this->createMock( User::class );
			$user->method( 'isAnon' )->willReturn( false );
			$user->method( 'getUserPage' )->willReturn( Title::makeTitle( NS_USER, 'TestUser' ) );
			$context->method( 'getUserObj' )->willReturn( $user );
		} else {
			// Anon
			$context->method( 'getUserObj' )->willReturn( new User );
		}
		$context->method( 'getLanguage' )->willReturn( 'en' );
		return $context;
	}

	/**
	 * @return array
	 */
	protected function getFakeOptions() {
		return [
			'wiki' => wfWikiID(), // Satisfy Hooks::loadForUser
			'source' => 'fakesource',
		];
	}

}
