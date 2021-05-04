<?php

namespace MediaWiki\GlobalCssJs\Test;

use MediaWiki\GlobalCssJs\ResourceLoaderGlobalModule;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \MediaWiki\GlobalCssJs\ResourceLoaderGlobalModule
 * @dataProvider provideGetSource
 */
class ResourceLoaderGlobalModuleTest extends \MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideGetSource
	 */
	public function testGetSource( $params, $expected ) {
		/** @var MockObject|ResourceLoaderGlobalModule $module */
		$module = $this->getMockBuilder(
			ResourceLoaderGlobalModule::class
		)->setConstructorArgs( [ $params ] )
			->onlyMethods( [ 'wfWikiID' ] )
			->getMockForAbstractClass();
		$module->expects( $this->any() )
			->method( 'wfWikiID' )
			->willReturn( 'examplewiki' );
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
