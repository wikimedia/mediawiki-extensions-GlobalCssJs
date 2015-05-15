<?php

class ResourceLoaderGlobalSiteModuleTest extends ResourceLoaderGlobalModuleTestCase {

	public static function provideGetPages() {

		// format: array( array( config => value ), $expectedPages, $skin, $description )
		return array(
			array(
				'ResourceLoaderGlobalSiteCssModule',
				array(),
				array(
					'MediaWiki:Global.css', 'MediaWiki:Global-skinname.css'
				),
				'skinname',
				'With default settings, 2 CSS global pages are loaded'
			),
			array(
				'ResourceLoaderGlobalSiteJsModule',
				array(),
				array(
					'MediaWiki:Global.js', 'MediaWiki:Global-skinname.js',
				),
				'skinname',
				'With default settings, 2 JS global pages are loaded'
			),
			array(
				'ResourceLoaderGlobalSiteCssModule',
				array( 'wgUseGlobalSiteCssJs' => false),
				array(),
				'skinname',
				'No CSS pages are loaded with $wgUseGlobalSiteCssJs = false'
			),
			array(
				'ResourceLoaderGlobalSiteJsModule',
				array( 'wgUseGlobalSiteCssJs' => false),
				array(),
				'skinname',
				'No JS pages are loaded with $wgUseGlobalSiteCssJs = false'
			),
			array(
				'ResourceLoaderGlobalSiteJsModule',
				array( 'wgUseSiteCss' => false ),
				array(
					'MediaWiki:Global.js', 'MediaWiki:Global-skinname.js',
				),
				'skinname',
				'JS pages are loaded if $wgUseSiteCss = false'
			),
			array(
				'ResourceLoaderGlobalSiteCssModule',
				array( 'wgUseSiteJs' => false ),
				array(
					'MediaWiki:Global.css', 'MediaWiki:Global-skinname.css',
				),
				'skinname',
				'CSS pages are loaded if $wgUseSiteJs = false'
			),
			array(
				'ResourceLoaderGlobalSiteCssModule',
				array( 'wgUseSiteJs' => false, 'wgUseSiteCss' => false ),
				array(),
				'skinname',
				'No CSS pages loaded if $wgUseSiteJs and $wgUseSiteCss are false'
			),
			array(
				'ResourceLoaderGlobalSiteJsModule',
				array( 'wgUseSiteJs' => false, 'wgUseSiteCss' => false ),
				array(),
				'skinname',
				'No JS pages loaded if $wgUseSiteJs and $wgUseSiteCss are false'
			),
			array(
				'ResourceLoaderGlobalSiteCssModule',
				array(),
				array(
					'MediaWiki:Global.css', 'MediaWiki:Global-monobook.css'
				),
				'monobook',
				'Global-monobook.css pages are loaded if monobook is set as the skin'
			),
			array(
				'ResourceLoaderGlobalSiteJsModule',
				array(),
				array(
					'MediaWiki:Global.js', 'MediaWiki:Global-monobook.js'
				),
				'monobook',
				'Global-monobook.js pages are loaded if monobook is set as the skin'
			),
		);
	}

	/**
	 * @covers ResourceLoaderGlobalSiteModule::getPages
	 * @dataProvider provideGetPages
	 * @param $class
	 * @param $configOverrides
	 * @param $expectedPages
	 * @param $skin
	 * @param $desc
	 */
	public function testGetPages( $class, $configOverrides, $expectedPages, $skin, $desc ) {
		// First set default config options
		$this->setMwGlobals( array_merge(
			$this->getDefaultGlobalSettings( $skin ),
			$configOverrides
		) );
		$module = new $class( $this->getFakeOptions() );
		$context = $this->getContext( array( 'skin' => $skin ) );
		$getPages = new ReflectionMethod( $module , 'getPages' );
		$getPages->setAccessible( true );
		$out = $getPages->invoke( $module, $context );
		$pages = array_keys( $out );
		$this->assertEquals( $expectedPages, $pages, $desc );
	}
}
