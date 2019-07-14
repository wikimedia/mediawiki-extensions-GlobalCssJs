<?php

namespace MediaWiki\GlobalCssJs\Test;

use MediaWiki\GlobalCssJs\ResourceLoaderGlobalModule;

/**
 * @covers \MediaWiki\GlobalCssJs\ResourceLoaderGlobalModule
 * @dataProvider provideGetSource
 */
class ResourceLoaderGlobalModuleTest extends \MediaWikiTestCase {

	/**
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
		$this->assertSame( $expected, $module->getSource(), 'source' );
	}

	public static function provideGetSource() {
		return [
			'foreign wiki' => [
				[
					'wiki' => 'blahwiki',
					'source' => 'blahsource',
				],
				'blahsource',
			],
			'same wiki' => [
				[
					'wiki' => 'examplewiki',
					'source' => 'blahsource',
				],
				'local',
			],
		];
	}
}
