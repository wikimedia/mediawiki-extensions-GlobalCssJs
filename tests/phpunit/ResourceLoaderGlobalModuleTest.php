<?php

namespace MediaWiki\GlobalCssJs\Test;

use MediaWiki\GlobalCssJs\ResourceLoaderGlobalModule;
use MediaWikiTestCase;

class ResourceLoaderGlobalModuleTest extends MediaWikiTestCase {

	/**
	 * @covers \MediaWiki\GlobalCssJs\ResourceLoaderGlobalModule::getSource
	 * @dataProvider provideGetSource
	 */
	public function testGetSource( $params, $expected ) {
		$this->setMwGlobals( [
			'wgDBname' => 'examplewiki',
			'wgDBprefix' => '',
		] );

		/** @var ResourceLoaderGlobalModule $module */
		$module = $this->getMockForAbstractClass(
			ResourceLoaderGlobalModule::class,
			[ $params ]
		);
		$this->assertEquals( $expected, $module->getSource() );
	}

	public static function provideGetSource() {
		return [
			[
				[
					'wiki' => 'blahwiki',
					'source' => 'blahsource',
				],
				'blahsource',
			],
			[
				[
					'wiki' => 'examplewiki',
					'source' => 'blahsource',
				],
				'local',
			],
		];
	}
}
