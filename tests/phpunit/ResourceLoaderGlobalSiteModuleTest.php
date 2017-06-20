<?php

class ResourceLoaderGlobalSiteModuleTest extends ResourceLoaderGlobalModuleTestCase {

	public static function provideGetPages() {
		// format: array( $type, array( config => value ), $expectedPages, $skin, $description )
		return [
			[
				'style',
				[],
				[
					'MediaWiki:Global.css', 'MediaWiki:Global-skinname.css'
				],
				'skinname',
				'With default settings, 2 CSS global pages are loaded'
			],
			[
				'script',
				[],
				[
					'MediaWiki:Global.js', 'MediaWiki:Global-skinname.js',
				],
				'skinname',
				'With default settings, 2 JS global pages are loaded'
			],
			[
				'style',
				[ 'wgUseGlobalSiteCssJs' => false ],
				[],
				'skinname',
				'No CSS pages are loaded with $wgUseGlobalSiteCssJs = false'
			],
			[
				'script',
				[ 'wgUseGlobalSiteCssJs' => false ],
				[],
				'skinname',
				'No JS pages are loaded with $wgUseGlobalSiteCssJs = false'
			],
			[
				'script',
				[ 'wgUseSiteCss' => false ],
				[
					'MediaWiki:Global.js', 'MediaWiki:Global-skinname.js',
				],
				'skinname',
				'JS pages are loaded if $wgUseSiteCss = false'
			],
			[
				'style',
				[ 'wgUseSiteJs' => false ],
				[
					'MediaWiki:Global.css', 'MediaWiki:Global-skinname.css',
				],
				'skinname',
				'CSS pages are loaded if $wgUseSiteJs = false'
			],
			[
				'style',
				[ 'wgUseSiteJs' => false, 'wgUseSiteCss' => false ],
				[],
				'skinname',
				'No CSS pages loaded if $wgUseSiteJs and $wgUseSiteCss are false'
			],
			[
				'script',
				[ 'wgUseSiteJs' => false, 'wgUseSiteCss' => false ],
				[],
				'skinname',
				'No JS pages loaded if $wgUseSiteJs and $wgUseSiteCss are false'
			],
			[
				'style',
				[],
				[
					'MediaWiki:Global.css', 'MediaWiki:Global-monobook.css'
				],
				'monobook',
				'Global-monobook.css pages are loaded if monobook is set as the skin'
			],
			[
				'script',
				[],
				[
					'MediaWiki:Global.js', 'MediaWiki:Global-monobook.js'
				],
				'monobook',
				'Global-monobook.js pages are loaded if monobook is set as the skin'
			],
		];
	}

	/**
	 * @covers ResourceLoaderGlobalSiteModule::getPages
	 * @dataProvider provideGetPages
	 * @param $type
	 * @param $configOverrides
	 * @param $expectedPages
	 * @param $skin
	 * @param $desc
	 */
	public function testGetPages( $type, $configOverrides, $expectedPages, $skin, $desc ) {
		// First set default config options
		$this->setMwGlobals( array_merge(
			$this->getDefaultGlobalSettings( $skin ),
			$configOverrides
		) );
		$module = new ResourceLoaderGlobalSiteModule(
			[ 'type' => $type ] + $this->getFakeOptions()
		);
		$context = $this->getContext( [ 'skin' => $skin ] );
		$getPages = new ReflectionMethod( $module, 'getPages' );
		$getPages->setAccessible( true );
		$out = $getPages->invoke( $module, $context );
		$pages = array_keys( $out );
		$this->assertEquals( $expectedPages, $pages, $desc );
	}
}
