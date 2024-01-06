<?php

namespace MediaWiki\GlobalCssJs\Test;

use MediaWiki\Config\HashConfig;
use MediaWiki\GlobalCssJs\ResourceLoaderGlobalUserModule;
use ReflectionMethod;

class ResourceLoaderGlobalUserModuleTest extends \MediaWikiIntegrationTestCase {
	use ResourceLoaderGlobalModuleTestTrait;

	public static function provideGetPages() {
		// format: array( $type, array( config => value ), $expectedPages, $description )
		return [
			[
				'style',
				[],
				[],
				'TestUser',
				'With default settings, no pages are loaded'
			],
			[
				'script',
				[ 'AllowUserJs' => true ],
				[
					'User:TestUser/global.js',
				],
				'TestUser',
				'JS page is loaded if $wgAllowUserJs = true'
			],
			[
				'style',
				[ 'AllowUserCss' => true ],
				[
					'User:TestUser/global.css',
				],
				'TestUser',
				'CSS page is loaded if $wgAllowUserCss = true'
			],
			[
				'script',
				[ 'GlobalCssJsConfig' => [ 'wiki' => false ] ],
				[],
				'TestUser',
				"If \$wgGlobalCssJsConfig['wiki'] = false, no pages are loaded",
			],
			[
				'style',
				[ 'AllowUserCss' => true, 'AllowUserJs' => true ],
				[],
				null,
				'No pages loaded if $username = null',
			],
			[
				'script',
				[ 'AllowUserCss' => true, 'AllowUserJs' => true ],
				[],
				'[Invalid@Username]',
				'No pages loaded if username is invalid',
			],
			[
				'style',
				[ 'AllowUserCss' => true, 'AllowUserJs' => true ],
				[],
				'UserThatHopefullyDoesntExist12',
				'No pages loaded if user doesnt exist',
			]
		];
	}

	/**
	 * @covers \MediaWiki\GlobalCssJs\ResourceLoaderGlobalUserModule::getPages
	 * @dataProvider provideGetPages
	 */
	public function testGetPages( $type, $configOverrides, $expectedPages, $user, $desc ) {
		$module = new ResourceLoaderGlobalUserModule(
			[ 'type' => $type ] + $this->getFakeOptions()
		);
		$module->setConfig( new HashConfig( array_merge(
			$this->getTestSettings(),
			$configOverrides
		) ) );
		$context = $this->makeContext( [ 'user' => $user ] );

		$getPages = new ReflectionMethod( $module, 'getPages' );
		$getPages->setAccessible( true );
		$out = $getPages->invoke( $module, $context );
		$pages = array_keys( $out );
		$this->assertEquals( $expectedPages, $pages, $desc );
	}
}
