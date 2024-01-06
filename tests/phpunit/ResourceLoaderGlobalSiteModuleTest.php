<?php

namespace MediaWiki\GlobalCssJs\Test;

use MediaWiki\Config\HashConfig;
use MediaWiki\GlobalCssJs\ResourceLoaderGlobalSiteModule;
use ReflectionMethod;

/**
 * @covers \MediaWiki\GlobalCssJs\ResourceLoaderGlobalSiteModule
 */
class ResourceLoaderGlobalSiteModuleTest extends \MediaWikiIntegrationTestCase {
	use ResourceLoaderGlobalModuleTestTrait;

	public static function provideGetPages() {
		// format: array( $type, array( config => value ), $expectedPages, $skin, $description )
		return [
			'With default settings, 2 CSS global pages are loaded' => [
				'style',
				[],
				'skinname',
				[
					'MediaWiki:Global.css',
					'MediaWiki:Global-skinname.css',
				],
			],
			'With default settings, 2 JS global pages are loaded' => [
				'script',
				[],
				'skinname',
				[
					'MediaWiki:Global.js',
					'MediaWiki:Global-skinname.js',
				],
			],
			'No CSS pages are loaded with $wgUseGlobalSiteCssJs = false' => [
				'style',
				[ 'UseGlobalSiteCssJs' => false ],
				'skinname',
				[],
			],
			'No JS pages are loaded with $wgUseGlobalSiteCssJs = false' => [
				'script',
				[ 'UseGlobalSiteCssJs' => false ],
				'skinname',
				[],
			],
			'JS pages are loaded if $wgUseSiteCss = false' => [
				'script',
				[ 'UseSiteCss' => false ],
				'skinname',
				[
					'MediaWiki:Global.js',
					'MediaWiki:Global-skinname.js',
				],
			],
			'CSS pages are loaded if $wgUseSiteJs = false' => [
				'style',
				[ 'UseSiteJs' => false ],
				'skinname',
				[
					'MediaWiki:Global.css',
					'MediaWiki:Global-skinname.css',
				],
			],
			'No CSS pages loaded if $wgUseSiteJs and $wgUseSiteCss are false' => [
				'style',
				[ 'UseSiteJs' => false, 'UseSiteCss' => false ],
				'skinname',
				[],
			],
			'No JS pages loaded if $wgUseSiteJs and $wgUseSiteCss are false' => [
				'script',
				[ 'UseSiteJs' => false, 'UseSiteCss' => false ],
				'skinname',
				[],
			],
			'Global-monobook.css pages are loaded if monobook is set as the skin' => [
				'style',
				[],
				'monobook',
				[
					'MediaWiki:Global.css',
					'MediaWiki:Global-monobook.css',
				],
			],
			'Global-monobook.js pages are loaded if monobook is set as the skin' => [
				'script',
				[],
				'monobook',
				[
					'MediaWiki:Global.js',
					'MediaWiki:Global-monobook.js',
				],
			],
		];
	}

	/**
	 * @dataProvider provideGetPages
	 */
	public function testGetPages(
		$type,
		array $configOverrides,
		$skin,
		array $expectedPages
	) {
		$module = new ResourceLoaderGlobalSiteModule(
			[ 'type' => $type ] + $this->getFakeOptions()
		);
		$module->setConfig( new HashConfig( array_merge(
			$this->getTestSettings(),
			$configOverrides
		) ) );
		$context = $this->makeContext( [ 'skin' => $skin, 'user' => null ] );

		$getPages = new ReflectionMethod( $module, 'getPages' );
		$getPages->setAccessible( true );
		$out = $getPages->invoke( $module, $context );
		$pages = array_keys( $out );
		$this->assertEquals( $expectedPages, $pages, 'page names' );
	}

	public function testGroup() {
		$module = new ResourceLoaderGlobalSiteModule( [] );
		$this->assertIsString( $module->getGroup(), 'group' );
	}
}
