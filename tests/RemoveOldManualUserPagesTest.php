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
				'UserName',
				'mw.loader.load with a proto-rel link',
			),
			array(
				'mw.loader.load("//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript");',
				true,
				'UserName',
				'double quotes',
			),
			array(
				"importScriptURI('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'using importScriptURI',
			),
			array(
				"mw.loader.load('http://meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'mw.loader.load with a http:// link',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:SomeOtherUserName/global.js&action=raw&ctype=text/javascript');",
				false,
				'UserName',
				'Loading a different user\'s global.js',
			),
			array(
				"mw.loader.load('//en.wikipedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				false,
				'UserName',
				'Loading from a different site',
			),
			array(
				"mw.loader.load('//en.wikipedia.org/w/index.php?title=User:UserName/common.js&action=raw&ctype=text/javascript');",
				false,
				'UserName',
				'Loading from a different page',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript')",
				true,
				'UserName',
				'No trailing ;',
			),
			array(
				"mw.loader.load ( '//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript' ) ;",
				true,
				'UserName',
				'Spaces around ( and )',
			),
			array(
				"//some comment\n//another comment\nmw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'comments before the mw.loader.load call',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');\nsomeOtherJavaScript();",
				false,
				'UserName',
				'page contains some other javascript',
			),
			array(
				"\n\n//some comment\n\n\n//another comment\n\nmw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'empty lines are also stripped in between comments',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript&smaxage=86400&maxage=86400');",
				true,
				'UserName',
				'(s)maxage parameters are accepted',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:John_F._Lewis/global.js&action=raw&ctype=text/javascript');",
				true,
				'John F. Lewis',
				'A username with spaces in it using underscores',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:Jdforrester (WMF)/global.js&action=raw&ctype=text/javascript');",
				true,
				'Jdforrester (WMF)',
				'A username with spaces in it using spaces',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:Jdforrester+(WMF)/global.js&action=raw&ctype=text/javascript');",
				true,
				'Jdforrester (WMF)',
				'A username with spaces in it using +',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:Jdforrester%20(WMF)/global.js&action=raw&ctype=text/javascript');",
				true,
				'Jdforrester (WMF)',
				'A username with spaces in it using %20',
			),
			array(
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:संतोष दहिवळ/global.js&action=raw&ctype=text/javascript');",
				true,
				'संतोष दहिवळ',
				'A username with spaces and unicode!',
			),
		);
	}

	/**
	 * @covers RemoveOldManualUserPages::checkJs
	 * @dataProvider provideCheckJs
	 * @param string $text page content
	 * @param bool $expected should it match?
	 * @param string $userName to use
	 * @param string $desc description of test case
	 */
	public function testCheckJs( $text, $expected, $userName, $desc ) {
		$this->load();
		$r = new RemoveOldManualUserPages();
		$matched = $r->checkJs( $text, 'meta\.wikimedia\.org', $userName );
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
	public function testCheckCss( $text, $expected, $desc ) {
		$this->load();
		$r = new RemoveOldManualUserPages();
		$matched = $r->checkCss( $text, 'meta\.wikimedia\.org', 'UserName' );
		$this->assertEquals( $expected, $matched, $desc );
	}

	public static function provideNormalizeUserName() {
		return array(
			array( 'UserName', 'UserName', 'A regular name with no fancy things' ),
			array( 'John F. Lewis', 'John( |_|\+|%20)F\.( |_|\+|%20)Lewis', 'A name with spaces and a period' ),
			array( 'Jdforrester (WMF)', 'Jdforrester( |_|\+|%20)\(WMF\)', 'A name with spaces and parenthesis' ),
			array( 'संतोष दहिवळ', 'संतोष( |_|\+|%20)दहिवळ', 'A name with spaces and unicode' ),
		);
	}

	/**
	 * @covers RemoveOldManualUserPages::normalizeUserName
	 * @dataProvider provideNormalizeUserName
	 */
	public function testNormalizeUserName( $name, $expected, $desc ) {
		$this->load();
		$r = new RemoveOldManualUserPages();
		$this->assertEquals( $expected, $r->normalizeUserName( $name ), $desc );
	}
}
