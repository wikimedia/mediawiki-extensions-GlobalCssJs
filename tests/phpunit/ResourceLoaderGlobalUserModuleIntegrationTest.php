<?php

namespace MediaWiki\GlobalCssJs\Test;

use MediaWiki\Config\HashConfig;
use MediaWiki\GlobalCssJs\ResourceLoaderGlobalUserModule;
use MediaWiki\MainConfigNames;
use ReflectionMethod;

class ResourceLoaderGlobalUserModuleIntegrationTest extends \MediaWikiIntegrationTestCase {
	use ResourceLoaderGlobalModuleTestTrait;

	public static function provideGetPages() {
		return [
			'User: namespace used in page titles even if $wgLanguageCode != "en"' => [
				'style',
				[ MainConfigNames::AllowUserCss => true, MainConfigNames::AllowUserJs => true ],
				[ MainConfigNames::LanguageCode => 'zh' ],
				[ 'User:TestUser/global.css' ],
				'TestUser'
			]
		];
	}

	/**
	 * @covers \MediaWiki\GlobalCssJs\ResourceLoaderGlobalUserModule
	 * @covers \MediaWiki\GlobalCssJs\Hooks
	 * @dataProvider provideGetPages
	 * @param string $type
	 * @param array $moduleConfig
	 * @param array $siteConfig
	 * @param array $expectedPages
	 * @param string $user
	 */
	public function testGetPages( $type, $moduleConfig, $siteConfig, $expectedPages, $user ) {
		$module = new ResourceLoaderGlobalUserModule(
			[ 'type' => $type ] + $this->getFakeOptions()
		);
		$module->setConfig( new HashConfig( array_merge(
			$this->getTestSettings(),
			$moduleConfig
		) ) );
		$this->overrideConfigValues( $siteConfig );
		$this->registerInConfigFactory();

		$context = $this->makeContext( [ 'user' => $user ] );
		$getPages = new ReflectionMethod( $module, 'getPages' );
		$getPages->setAccessible( true );
		$out = $getPages->invoke( $module, $context );
		$pages = array_keys( $out );
		$this->assertEquals( $expectedPages, $pages );
	}
}
