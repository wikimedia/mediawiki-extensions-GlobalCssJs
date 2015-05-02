<?php

class ResourceLoaderGlobalSiteModuleTest extends ResourceLoaderGlobalModuleTestCase {

	public static function provideGetPages() {

		// format: array( array( config => value ), $expectedPages, $skin, $description )
		return array(
			array(
				array(),
				array(
					'MediaWiki:Global.js', 'MediaWiki:Global-skinname.js',
					'MediaWiki:Global.css', 'MediaWiki:Global-skinname.css'
				),
				'skinname',
				'With default settings, 4 global pages are loaded'
			),
			array(
				array( 'wgUseGlobalSiteCssJs' => false),
				array(),
				'skinname',
				'No pages are loaded with $wgUseGlobalSiteCssJs = false'
			),
			array(
				array( 'wgUseSiteCss' => false ),
				array(
					'MediaWiki:Global.js', 'MediaWiki:Global-skinname.js',
				),
				'skinname',
				'Only JS pages are loaded if $wgUseSiteCss = false'
			),
			array(
				array( 'wgUseSiteJs' => false ),
				array(
					'MediaWiki:Global.css', 'MediaWiki:Global-skinname.css',
				),
				'skinname',
				'Only CSS pages are loaded if $wgUseSiteJs = false'
			),
			array(
				array( 'wgUseSiteJs' => false, 'wgUseSiteCss' => false ),
				array(),
				'skinname',
				'No pages loaded if $wgUseSiteJs and $wgUseSiteCss are false'
			),
			array(
				array(),
				array(
					'MediaWiki:Global.js', 'MediaWiki:Global-monobook.js',
					'MediaWiki:Global.css', 'MediaWiki:Global-monobook.css'
				),
				'monobook',
				'Global-monobook.js/css pages are loaded if monobook is set as the skin'
			),
		);
	}

	/**
	 * @covers ResourceLoaderGlobalSiteModule::getPages
	 * @dataProvider provideGetPages
	 * @param $configOverrides
	 * @param $expectedPages
	 * @param $skin
	 * @param $desc
	 */
	public function testGetPages( $configOverrides, $expectedPages, $skin, $desc ) {
		// First set default config options
		$this->setMwGlobals( array_merge(
			$this->getDefaultGlobalSettings( $skin ),
			$configOverrides
		) );
		$module = new ResourceLoaderGlobalSiteModule( $this->getFakeOptions() );
		$context = $this->getContext( array( 'skin' => $skin ) );
		$getPages = new ReflectionMethod( $module , 'getPages' );
		$getPages->setAccessible( true );
		$out = $getPages->invoke( $module, $context );
		$pages = array_keys( $out );
		$this->assertEquals( $expectedPages, $pages, $desc );
	}
}
