<?php

class GlobalCssJsHooksTest extends MediaWikiTestCase {

	public static function provideLoadForUser() {
		return array(
			array( false, false, 'Hook not called if "wiki" set to false' ),
			array( 'wikiid', false, 'Hook not called if "wiki" set to wfWikiId()' ),
			array( 'somewiki', true, 'Hook called if "wiki" set to "somewiki"' ),
		);
	}

	/**
	 * @covers GlobalCssJsHooks::loadForUser
	 * @dataProvider provideLoadForUser
	 */
	public function testLoadForUser( $wiki, $assert, $desc ) {
		$wiki = $wiki === 'wikiid' ? wfWikiID() : $wiki;
		$us = $this;

		$this->setMwGlobals( array(
			'wgGlobalCssJsConfig' => array(
				'wiki' => $wiki,
				'source' => 'fakesource'
			),
			'wgHooks' => array(
				'LoadGlobalCssJs' => array(
					function( $user, $wiki ) use ( $us, $assert, $desc ) {
						// Check whether the hook was run, and whether we wanted it to be.
						$us->assertTrue( $assert, $desc );
						return true;
					},
				)
			)
		) );

		GlobalCssJsHooks::loadForUser( new User );

		if ( $assert === false ) {
			$this->assertTrue( true ); // So the test isn't marked as risky.
		}
	}
}
