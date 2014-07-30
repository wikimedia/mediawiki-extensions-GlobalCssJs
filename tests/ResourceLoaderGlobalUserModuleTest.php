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
				'With default settings, no pages are loaded'
			),
			array(
				array( 'wgAllowUserJs' => true ),
				array(
					'User:TestUser/global.js',
				),
				'Only JS page is loaded if $wgAllowUserJs = true'
			),
			array(
				array( 'wgAllowUserCss' => true ),
				array(
					'User:TestUser/global.css',
				),
				'Only CSS page is loaded if $wgAllowUserCss = true'
			),
			array(
				array( 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array( 'User:TestUser/global.js', 'User:TestUser/global.css' ),
				'2 global pages loaded if both $wgAllowUserCss and $wgAllowUserJs = true'
			),
			array(
				array( 'wgLanguageCode' => 'zh', 'wgAllowUserCss' => true, 'wgAllowUserJs' => true ),
				array( 'User:TestUser/global.js', 'User:TestUser/global.css' ),
				'User: namespace used in page titles even if $wgLanguageCode != "en"'
			),
			array(
				array( 'wgGlobalCssJsConfig' => array( 'wiki' => false ) ),
				array(),
				"If \$wgGlobalCssJsConfig['wiki'] = false, no pages are loaded",
			)
		);
	}

	/**
	 * @covers ResourceLoaderGlobalUserModule::getPages
	 * @dataProvider provideGetPages
	 * @param $configOverrides
	 * @param $expectedPages
	 * @param $desc
	 */
	public function testGetPages( $configOverrides, $expectedPages, $desc ) {
		// First set default config options
		$this->setMwGlobals( array_merge(
			self::getDefaultGlobalSettings(),
			$configOverrides
		) );
		$module = new ResourceLoaderGlobalUserModule( self::getFakeOptions() );
		$context = self::getContext();
		$out = $module->getDefinitionSummary( $context );
		$pages = array_keys( $out['pages'] );
		$this->assertEquals( $expectedPages, $pages, $desc );
	}
}
