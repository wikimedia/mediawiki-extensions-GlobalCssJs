<?php

namespace MediaWiki\GlobalCssJs\Test;

use MediaWiki\GlobalCssJs\RemoveOldManualUserPages;
use MediaWikiTestCase;

require_once __DIR__ . '/../../maintenance/removeOldManualUserPages.php';

class RemoveOldManualUserPagesTest extends MediaWikiTestCase {

	public static function provideCheckJs() {
		return [
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js" .
					"&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'mw.loader.load with a proto-rel link',
			],
			[
				'mw.loader.load("//meta.wikimedia.org/w/index.php?title=User:UserName/global.js' .
					'&action=raw&ctype=text/javascript");',
				true,
				'UserName',
				'double quotes',
			],
			[
				"importScriptURI('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js" .
					"&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'using importScriptURI',
			],
			[
				"mw.loader.load('http://meta.wikimedia.org/w/index.php?title=User:UserName/" .
					"global.js&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'mw.loader.load with a http:// link',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:SomeOtherUserName/" .
					"global.js&action=raw&ctype=text/javascript');",
				false,
				'UserName',
				'Loading a different user\'s global.js',
			],
			[
				"mw.loader.load('//en.wikipedia.org/w/index.php?title=User:UserName/global.js" .
					"&action=raw&ctype=text/javascript');",
				false,
				'UserName',
				'Loading from a different site',
			],
			[
				"mw.loader.load('//en.wikipedia.org/w/index.php?title=User:UserName/common.js" .
					"&action=raw&ctype=text/javascript');",
				false,
				'UserName',
				'Loading from a different page',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js" .
					"&action=raw&ctype=text/javascript')",
				true,
				'UserName',
				'No trailing ;',
			],
			[
				"mw.loader.load ( '//meta.wikimedia.org/w/index.php?title=User:UserName/global.js" .
					"&action=raw&ctype=text/javascript' ) ;",
				true,
				'UserName',
				'Spaces around ( and )',
			],
			[
				"//some comment\n//another comment\nmw.loader.load('//meta.wikimedia.org/w/" .
					"index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'comments before the mw.loader.load call',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js" .
					"&action=raw&ctype=text/javascript');\nsomeOtherJavaScript();",
				false,
				'UserName',
				'page contains some other javascript',
			],
			[
				"\n\n//some comment\n\n\n//another comment\n\nmw.loader.load('//meta.wikimedia." .
					"org/w/index.php?title=User:UserName/global.js&action=raw&ctype=text/javascript');",
				true,
				'UserName',
				'empty lines are also stripped in between comments',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:UserName/global.js" .
					"&action=raw&ctype=text/javascript&smaxage=86400&maxage=86400');",
				true,
				'UserName',
				'(s)maxage parameters are accepted',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:John_F._Lewis/" .
					"global.js&action=raw&ctype=text/javascript');",
				true,
				'John F. Lewis',
				'A username with spaces in it using underscores',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:Jdforrester (WMF)/" .
					"global.js&action=raw&ctype=text/javascript');",
				true,
				'Jdforrester (WMF)',
				'A username with spaces in it using spaces',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:Jdforrester+(WMF)/" .
					"global.js&action=raw&ctype=text/javascript');",
				true,
				'Jdforrester (WMF)',
				'A username with spaces in it using +',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:Jdforrester%20(WMF)/" .
					"global.js&action=raw&ctype=text/javascript');",
				true,
				'Jdforrester (WMF)',
				'A username with spaces in it using %20',
			],
			[
				"mw.loader.load('//meta.wikimedia.org/w/index.php?title=User:संतोष दहिवळ/global.js" .
					"&action=raw&ctype=text/javascript');",
				true,
				'संतोष दहिवळ',
				'A username with spaces and unicode!',
			],
		];
	}

	/**
	 * @covers \MediaWiki\GlobalCssJs\RemoveOldManualUserPages::checkJs
	 * @dataProvider provideCheckJs
	 * @param string $text page content
	 * @param bool $expected should it match?
	 * @param string $userName to use
	 * @param string $desc description of test case
	 */
	public function testCheckJs( $text, $expected, $userName, $desc ) {
		$r = new RemoveOldManualUserPages();
		$matched = $r->checkJs( $text, 'meta\.wikimedia\.org', $userName );
		$this->assertEquals( $expected, $matched, $desc );
	}

	public static function provideCheckCss() {
		return [
			[
				"@import url('//meta.wikimedia.org/w/index.php?title=User:UserName/global.css" .
					"&action=raw&ctype=text/css');",
				true,
				'standard @import with proto-rel'
			],
			[
				"@import url('https://meta.wikimedia.org/w/index.php?title=User:UserName/" .
					"global.css&action=raw&ctype=text/css');",
				true,
				'standard @import with https'
			],
			[
				"@import url('//commons.wikimedia.org/w/index.php?title=User:UserName/global.css" .
					"&action=raw&ctype=text/css');",
				false,
				'loading from a different wiki'
			],
			[
				"@import url('//meta.wikimedia.org/w/index.php?title=User:UserName/global.css" .
					"&action=raw&ctype=text/css');\n body{ background-color: red; }",
				false,
				'some other CSS too',
			],
			[
				"@import url('//meta.wikimedia.org/w/index.php?title=User:SomeOtherUserName/" .
					"global.css&action=raw&ctype=text/css');",
				false,
				'loading another user\'s CSS',
			],
		];
	}

	/**
	 * @covers \MediaWiki\GlobalCssJs\RemoveOldManualUserPages::checkCss
	 * @dataProvider provideCheckCss
	 * @param string $text page content
	 * @param bool $expected should it match?
	 * @param string $desc description of test case
	 */
	public function testCheckCss( $text, $expected, $desc ) {
		$r = new RemoveOldManualUserPages();
		$matched = $r->checkCss( $text, 'meta\.wikimedia\.org', 'UserName' );
		$this->assertEquals( $expected, $matched, $desc );
	}

	public static function provideNormalizeUserName() {
		return [
			[
				'UserName',
				'UserName',
				'A regular name with no fancy things'
			],
			[
				'John F. Lewis',
				'John( |_|\+|%20)F\.( |_|\+|%20)Lewis',
				'A name with spaces and a period'
			],
			[
				'Jdforrester (WMF)',
				'Jdforrester( |_|\+|%20)\(WMF\)',
				'A name with spaces and parenthesis'
			],
			[
				'संतोष दहिवळ',
				'संतोष( |_|\+|%20)दहिवळ',
				'A name with spaces and unicode'
			],
		];
	}

	/**
	 * @covers \MediaWiki\GlobalCssJs\RemoveOldManualUserPages::normalizeUserName
	 * @dataProvider provideNormalizeUserName
	 */
	public function testNormalizeUserName( $name, $expected, $desc ) {
		$r = new RemoveOldManualUserPages();
		$this->assertEquals( $expected, $r->normalizeUserName( $name ), $desc );
	}
}
