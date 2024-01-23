<?php

namespace MediaWiki\GlobalCssJs\Test;

use MediaWiki\Config\HashConfig;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;

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
	 * Get a fake ResourceLoader Context object for testing.
	 *
	 * @param array $options
	 * @return Context
	 */
	protected function makeContext( array $options ) {
		$context = $this->createMock( Context::class );
		$context->method( 'getSkin' )->willReturn( $options['skin'] ?? 'vector' );
		$context->method( 'getUser' )->willReturn( $options['user'] );
		if ( $options['user'] === 'TestUser' ) {
			// Logged-in
			$user = $this->createMock( UserIdentity::class );
			$user->method( 'isRegistered' )->willReturn( true );
			$user->method( 'getName' )->willReturn( 'TestUser' );
			$context->method( 'getUserIdentity' )->willReturn( $user );
		} else {
			// Anon
			$context->method( 'getUserIdentity' )->willReturn( null );
		}
		$context->method( 'getLanguage' )->willReturn( 'en' );
		return $context;
	}

	/**
	 * @return array
	 */
	protected function getFakeOptions() {
		return [
			'wiki' => WikiMap::getCurrentWikiId(), // Satisfy Hooks::loadForUser
			'source' => 'fakesource',
		];
	}

}
