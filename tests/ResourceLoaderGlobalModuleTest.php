<?php

class ResourceLoaderGlobalModuleTest extends MediaWikiTestCase {

	/**
	 * @covers ResourceLoaderGlobalModule::getSource
	 * @dataProvider provideGetSource
	 */
	public function testGetSource( $params, $expected ) {
		$this->setMwGlobals( array(
			'wgDBname' => 'examplewiki',
			'wgDBprefix' => '',
		) );

		/** @var ResourceLoaderGlobalModule $module */
		$module = $this->getMockForAbstractClass( 'ResourceLoaderGlobalModule', array( $params ) );
		$this->assertEquals( $expected, $module->getSource() );
	}

	public static function provideGetSource() {
		return array(
			array(
				array(
					'wiki' => 'blahwiki',
					'source' => 'blahsource',
				),
				'blahsource',
			),
			array(
				array(
					'wiki' => 'examplewiki',
					'source' => 'blahsource',
				),
				'local',
			),
		);
	}

	/**
	 * Verify that all style modules are setting an explicit position
	 *
	 * @covers ResourceLoaderGlobalModule::isPositionDefault
	 */
	public function testIsPositionDefault() {
		$this->setMwGlobals(
			'wgGlobalCssJsConfig',
			array( 'wiki' => wfWikiID(), 'source' => 'fakesource' )
		);
		$rl = new ResourceLoader( ConfigFactory::getDefaultInstance()->makeConfig( 'main' ) );
		$this->assertTrue( !$rl->getModule( 'ext.globalCssJs.user.styles' )->isPositionDefault() );
		$this->assertTrue( !$rl->getModule( 'ext.globalCssJs.site.styles' )->isPositionDefault() );
	}
}
