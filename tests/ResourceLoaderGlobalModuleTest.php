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
}
