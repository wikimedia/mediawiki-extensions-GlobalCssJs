<?php

class RemoveOldManualUserPagesTest extends MediaWikiTestCase {

	/**
	 * No autoloader for maintenance scripts
	 */
	private function load() {
		require_once dirname( __DIR__ ) . '/removeOldManualUserPages.php';
	}

	public static function provideCheckJs() {
		return array(
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'mw.loader.load with a proto-rel link',
			),
			array(
				"importScriptURI('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'using importScriptURI',
			),
			array(
				"mw.loader.load('http://meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'mw.loader.load with a http:// link',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:SomeOtherUserName/global.js&action=raw&ctype=text/javascript');",
				false,
				'Loading a different user\'s global.js',
			),
			array(
				"mw.loader.load('//en.wikipedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				false,
				'Loading from a different site',
			),
			array(
				"mw.loader.load('//en.wikipedia.org/w/index.php?title=User:UserName/common.js&action=raw&ctype=text/javascript');",
				false,
				'Loading from a different page',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript')",
				true,
				'No trailing ;',
			),
			array(
				"mw.loader.load ( '//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript' ) ;",
				true,
				'Spaces around ( and )',
			),
			array(
				"//some comment\n//another comment\nmw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'comments before the mw.loader.load call',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');\nsomeOtherJavaScript();",
				false,
				'page contains some other javascript',
			),
			array(
				"\n\n//some comment\n\n\n//another comment\n\nmw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'empty lines are also stripped in between comments',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript&smaxage=86400&maxage=86400');",
				true,
				'(s)maxage parameters are accepted',
			),
		);
	}

	/**
	 * @covers RemoveOldManualUserPages::checkJs
	 * @dataProvider provideCheckJs
	 * @param string $text page content
	 * @param bool $expected should it match?
	 * @param string $desc description of test case
	 */
	public function testCheckJs( $text, $expected, $desc) {
		$this->load();
		$r = new RemoveOldManualUserPages();
		$matched = $r->checkJs( $text, 'meta\.wikimedia\.org', 'UserName' );
		$this->assertEquals( $expected, $matched, $desc );
	}

	public static function provideCheckCss() {
		return array(
			array(
				"@import url('//meta.wikimedia.org/w/index.php?title=User:UserName/global.css&action=raw&ctype=text/css');",
				true,
				'standard @import with proto-rel'
			),
			array(
				"@import url('https://meta.wikimedia.org/w/index.php?title=User:UserName/global.css&action=raw&ctype=text/css');",
				true,
				'standard @import with https'
			),
			array(
				"@import url('//commons.wikimedia.org/w/index.php?title=User:UserName/global.css&action=raw&ctype=text/css');",
				false,
				'loading from a different wiki'
			),
			array(
				"@import url('//meta.wikimedia.org/w/index.php?title=User:UserName/global.css&action=raw&ctype=text/css');\n body{ background-color: red; }",
				false,
				'some other CSS too',
			),
			array(
				"@import url('//meta.wikimedia.org/w/index.php?title=User:SomeOtherUserName/global.css&action=raw&ctype=text/css');",
				false,
				'loading another user\'s CSS',
			),
		);
	}

	/**
	 * @covers RemoveOldManualUserPages::checkCss
	 * @dataProvider provideCheckCss
	 * @param string $text page content
	 * @param bool $expected should it match?
	 * @param string $desc description of test case
	 */
	public function testCheckCss( $text, $expected, $desc) {
		$this->load();
		$r = new RemoveOldManualUserPages();
		$matched = $r->checkCss( $text, 'meta\.wikimedia\.org', 'UserName' );
		$this->assertEquals( $expected, $matched, $desc );
	}
}
