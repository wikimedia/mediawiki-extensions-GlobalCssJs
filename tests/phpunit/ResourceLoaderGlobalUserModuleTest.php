<?php

/**
 * @group Database
 */
class ResourceLoaderGlobalUserModuleTest extends ResourceLoaderGlobalModuleTestCase {

	public function setUp() {
		parent::setUp();
		// Our user must exist in the database
		$user = User::newFromName( 'TestUser' );
		$user->addToDatabase();
	}

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
				[ 'wgAllowUserJs' => true ],
				[
					'User:TestUser/global.js',
				],
				'TestUser',
				'JS page is loaded if $wgAllowUserJs = true'
			],
			[
				'style',
				[ 'wgAllowUserCss' => true ],
				[
					'User:TestUser/global.css',
				],
				'TestUser',
				'CSS page is loaded if $wgAllowUserCss = true'
			],
			[
				'style',
				[ 'wgLanguageCode' => 'zh', 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ],
				[ 'User:TestUser/global.css' ],
				'TestUser',
				'User: namespace used in page titles even if $wgLanguageCode != "en"'
			],
			[
				'script',
				[ 'wgGlobalCssJsConfig' => [ 'wiki' => false ] ],
				[],
				'TestUser',
				"If \$wgGlobalCssJsConfig['wiki'] = false, no pages are loaded",
			],
			[
				'style',
				[ 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ],
				[],
				null,
				'No pages loaded if $username = null',
			],
			[
				'script',
				[ 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ],
				[],
				'[Invalid@Username]',
				'No pages loaded if username is invalid',
			],
			[
				'style',
				[ 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ],
				[],
				'UserThatHopefullyDoesntExist12',
				'No pages loaded if user doesnt exist',
			]
		];
	}

	/**
	 * @covers ResourceLoaderGlobalUserModule::getPages
	 * @dataProvider provideGetPages
	 * @param $type
	 * @param $configOverrides
	 * @param $expectedPages
	 * @param $user
	 * @param $desc
	 */
	public function testGetPages( $type, $configOverrides, $expectedPages, $user, $desc ) {
		// First set default config options
		$this->setMwGlobals( array_merge(
			$this->getDefaultGlobalSettings(),
			$configOverrides
		) );
		$module = new ResourceLoaderGlobalUserModule(
			[ 'type' => $type ] + $this->getFakeOptions()
		);
		$context = $this->getContext( [ 'user' => $user ] );
		$getPages = new ReflectionMethod( $module, 'getPages' );
		$getPages->setAccessible( true );
		$out = $getPages->invoke( $module, $context );
		$pages = array_keys( $out );
		$this->assertEquals( $expectedPages, $pages, $desc );
	}
}
