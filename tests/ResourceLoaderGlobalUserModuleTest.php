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
		return array(
			array(
				'style',
				array(),
				array(),
				'TestUser',
				'With default settings, no pages are loaded'
			),
			array(
				'script',
				array( 'wgAllowUserJs' => true ),
				array(
					'User:TestUser/global.js',
				),
				'TestUser',
				'JS page is loaded if $wgAllowUserJs = true'
			),
			array(
				'style',
				array( 'wgAllowUserCss' => true ),
				array(
					'User:TestUser/global.css',
				),
				'TestUser',
				'CSS page is loaded if $wgAllowUserCss = true'
			),
			array(
				'style',
				array( 'wgLanguageCode' => 'zh', 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array( 'User:TestUser/global.css' ),
				'TestUser',
				'User: namespace used in page titles even if $wgLanguageCode != "en"'
			),
			array(
				'script',
				array( 'wgGlobalCssJsConfig' => array( 'wiki' => false ) ),
				array(),
				'TestUser',
				"If \$wgGlobalCssJsConfig['wiki'] = false, no pages are loaded",
			),
			array(
				'style',
				array( 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array(),
				null,
				'No pages loaded if $username = null',
			),
			array(
				'script',
				array( 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array(),
				'[Invalid@Username]',
				'No pages loaded if username is invalid',
			),
			array(
				'style',
				array( 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array(),
				'UserThatHopefullyDoesntExist12',
				'No pages loaded if user doesnt exist',
			)
		);
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
			array( 'type' => $type ) + $this->getFakeOptions()
		);
		$context = $this->getContext( array( 'user' => $user ) );
		$getPages = new ReflectionMethod( $module, 'getPages' );
		$getPages->setAccessible( true );
		$out = $getPages->invoke( $module, $context );
		$pages = array_keys( $out );
		$this->assertEquals( $expectedPages, $pages, $desc );
	}
}
