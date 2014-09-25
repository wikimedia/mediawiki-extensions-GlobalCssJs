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

		// format: array( array( config => value ), $expectedPages, $description )
		return array(
			array(
				array(),
				array(),
				'TestUser',
				'With default settings, no pages are loaded'
			),
			array(
				array( 'wgAllowUserJs' => true ),
				array(
					'User:TestUser/global.js',
				),
				'TestUser',
				'Only JS page is loaded if $wgAllowUserJs = true'
			),
			array(
				array( 'wgAllowUserCss' => true ),
				array(
					'User:TestUser/global.css',
				),
				'TestUser',
				'Only CSS page is loaded if $wgAllowUserCss = true'
			),
			array(
				array( 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array( 'User:TestUser/global.js', 'User:TestUser/global.css' ),
				'TestUser',
				'2 global pages loaded if both $wgAllowUserCss and $wgAllowUserJs = true'
			),
			array(
				array( 'wgLanguageCode' => 'zh', 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array( 'User:TestUser/global.js', 'User:TestUser/global.css' ),
				'TestUser',
				'User: namespace used in page titles even if $wgLanguageCode != "en"'
			),
			array(
				array( 'wgGlobalCssJsConfig' => array( 'wiki' => false ) ),
				array(),
				'TestUser',
				"If \$wgGlobalCssJsConfig['wiki'] = false, no pages are loaded",
			),
			array(
				array( 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array(),
				null,
				'No pages loaded if $username = null',
			),
			array(
				array( 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array(),
				'[Invalid@Username]',
				'No pages loaded if username is invalid',
			),
			array(
				array( 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array(),
				'UserThatHopefullyDoesntExist12',
				'No pages loaded if user doesnt exist',
			),
		);
	}

	/**
	 * @covers ResourceLoaderGlobalUserModule::getPages
	 * @dataProvider provideGetPages
	 * @param $configOverrides
	 * @param $expectedPages
	 * @param $user
	 * @param $desc
	 */
	public function testGetPages( $configOverrides, $expectedPages, $user, $desc ) {
		// First set default config options
		$this->setMwGlobals( array_merge(
			self::getDefaultGlobalSettings(),
			$configOverrides
		) );
		$module = new ResourceLoaderGlobalUserModule( self::getFakeOptions() );
		$context = self::getContext( array( 'user' => $user ) );
		$out = $module->getDefinitionSummary( $context );
		$pages = array_keys( $out['pages'] );
		$this->assertEquals( $expectedPages, $pages, $desc );
	}
}
