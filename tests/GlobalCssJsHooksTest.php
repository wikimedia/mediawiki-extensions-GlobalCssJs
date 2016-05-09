<?php

class GlobalCssJsHooksTest extends MediaWikiTestCase {

	public static function provideLoadForUser() {
		return [
			[ false, false, 'Hook not called if "wiki" set to false' ],
			[ 'wikiid', false, 'Hook not called if "wiki" set to wfWikiId()' ],
			[ 'somewiki', true, 'Hook called if "wiki" set to "somewiki"' ],
		];
	}

	/**
	 * @covers GlobalCssJsHooks::loadForUser
	 * @dataProvider provideLoadForUser
	 */
	public function testLoadForUser( $wiki, $assert, $desc ) {
		$wiki = $wiki === 'wikiid' ? wfWikiID() : $wiki;
		$us = $this;

		$this->setMwGlobals( [
			'wgGlobalCssJsConfig' => [
				'wiki' => $wiki,
				'source' => 'fakesource'
			],
			'wgHooks' => [
				'LoadGlobalCssJs' => [
					function( $user, $wiki ) use ( $us, $assert, $desc ) {
						// Check whether the hook was run, and whether we wanted it to be.
						$us->assertTrue( $assert, $desc );
						return true;
					},
				]
			]
		] );

		GlobalCssJsHooks::loadForUser( new User );

		if ( $assert === false ) {
			$this->assertTrue( true ); // So the test isn't marked as risky.
		}
	}
}
