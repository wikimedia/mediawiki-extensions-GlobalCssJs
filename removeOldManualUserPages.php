<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Script to remove manually created user .js and .css pages
 * by users. You should run this script on every wiki where the user
 * has an account.
 */
class RemoveOldManualUserPages extends Maintenance {

	/**
	 * @var bool
	 */
	private $ignoreRevisionLimit;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Remove reundant user script pages that import global.js and/or global.css';
		$this->addOption( 'user', 'User name', true, true );
		$this->addOption( 'ignorerevisionlimit', 'Whether to ignore the 1 revision limit', false, false );
	}

	public function execute() {
		$this->ignoreRevisionLimit = $this->hasOption( 'ignorerevisionlimit' );
		$userName = $this->getOption( 'user' );
		$user = User::newFromName( $userName );
		if ( !class_exists( 'GlobalCssJsHooks' ) ) {
			$this->error( 'The GlobalCssJs extension is not enabled on this wiki.', 1 );
		}

		if ( !$user->getId() || !GlobalCssJsHooks::loadForUser( $user ) ) {
			$this->output( "$userName does not load global modules on this wiki.\n" );
			return;
		}

		$skins = array_keys( Skin::getAllowedSkins() );
		$skins[] = 'common';

		// Batch look up the existence of pages
		$lb = new LinkBatch();
		foreach ( $skins as $name ) {
			$lb->addObj( $user->getUserPage()->getSubpage( "$name.js" ) );
			$lb->addObj( $user->getUserPage()->getSubpage( "$name.css" ) );
		}
		$lb->execute();

		foreach ( $skins as $name ) {
			$this->removeJS( $user, $name );
			$this->removeCSS( $user, $name );
		}
	}

	/**
	 * Generic checks to see if we should work on a title.
	 *
	 * @param Title $title
	 * @return Revision|bool
	 */
	private function checkTitle( Title $title ) {
		if ( !$title->exists() ) {
			$this->output( "{$title->getPrefixedText()} does not exist on this wiki.\n" );
			return false;
		}


		$rev = Revision::newFromTitle( $title );
		if ( !$this->ignoreRevisionLimit && $title->getPreviousRevisionID( $rev->getId() ) !== false ) {
			$this->output( "{$title->getPrefixedText()} has more than one revision, skipping.\n" );
			return false;
		}

		return $rev;
	}

	/**
	 * Returns the domain name of the central wiki
	 * escaped to use in a regex.
	 *
	 * @return string
	 */
	private function getCentralWikiDomain() {
		global $wgGlobalCssJsConfig;
		$rl = new ResourceLoader;
		$sources = $rl->getSources();
		// Use api.php instead of load.php because it's more likely to be on the same domain
		$api = $sources[$wgGlobalCssJsConfig['source']]['apiScript'];
		$parsed = wfParseUrl( $api );
		return preg_quote( $parsed['host'] );
	}

	private function deletePage( Title $title, $reason ) {
		$page = WikiPage::factory( $title );
		$user = User::newFromName( 'GlobalCssJs migration script' );
		$errors = array();
		$page->doDeleteArticleReal( wfMessage( $reason )->inContentLanguage()->text(), false, 0, true, $errors, $user );
		$this->output( "{$title->getPrefixedText()} was deleted.\n" );
	}

	/**
	 * Given a username, normalize and escape it to be
	 * safely used in regex
	 *
	 * @param string $userName
	 * @return string
	 */
	public function normalizeUserName( $userName ) {
		$userName = preg_quote( $userName );
		// Spaces can be represented as space, underscore, plus, or %20.
		$userName = str_replace( ' ', '( |_|\+|%20)', $userName );
		return $userName;
	}

	public function checkCss( $text, $domain, $userName ) {
		$userName = $this->normalizeUserName( $userName );
		preg_match( "/@import url\('(https?:)?\/\/$domain\/w\/index\.php\?title=User:$userName\/global\.css&action=raw&ctype=text\/css'\);/", $text, $matches );
		return isset( $matches[0] ) ? $matches[0] === $text : false;
	}

	private function removeCSS( User $user, $skin ) {
		$userName = $user->getName();
		$title = $user->getUserPage()->getSubpage( $skin . '.css' );
		$rev = $this->checkTitle( $title );
		if ( !$rev ) {
			return;
		}

		/** @var CssContent $content */
		$content = $rev->getContent();
		$text = trim( $content->getNativeData() );
		$domain = $this->getCentralWikiDomain();
		if ( !$this->checkCss( $text, $domain, $userName ) ) {
			$this->output( "{$title->getPrefixedText()} did not match the specified regular expression. Skipping.\n" );
			return;
		}

		// Delete!
		$this->deletePage( $title, 'globalcssjs-delete-css' );
	}

	/**
	 * Remove lines that are entirely comments, by checking if they start with //
	 * Also get rid of empty lines while we're at it.
	 *
	 * @param string $js
	 * @return string
	 */
	private function stripComments( $js ) {
		$exploded = explode( "\n", $js );
		$new = array();
		foreach ( $exploded as $line ) {
			$trimmed = trim( $line );
			if ( $trimmed !== '' && substr( $trimmed, 0, 2) !== '//' ) {
				$new[] = $line;
			}
		}

		return implode( '\n', $new );
	}

	public function checkJs( $text, $domain, $userName ) {
		$text = $this->stripComments( $text );
		$userName = $this->normalizeUserName( $userName );
		preg_match( "/(mw\.loader\.load|importScriptURI)\s*\(\s*('|\")(https?:)?\/\/$domain\/w\/index\.php\?title=User:$userName\/global\.js&action=raw&ctype=text\/javascript(&smaxage=\d*?)?(&maxage=\d*?)?('|\")\s*\)\s*;?/", $text, $matches );
		return isset( $matches[0] ) ? $matches[0] === $text : false;
	}

	private function removeJS( User $user, $skin ) {
		$userName = $user->getName();
		$title = $user->getUserPage()->getSubpage( $skin . '.js' );
		$rev = $this->checkTitle( $title );
		if ( !$rev ) {
			return;
		}

		/** @var JavaScriptContent $content */
		$content = $rev->getContent();
		$text = trim( $content->getNativeData() );
		$domain = $this->getCentralWikiDomain();
		if ( !$this->checkJs( $text, $domain, $userName ) ) {
			$this->output( "{$title->getPrefixedText()} did not match the specified regular expression. Skipping.\n" );
			return;
		}

		// Delete!
		$this->deletePage( $title, 'globalcssjs-delete-js' );
	}

}

$maintClass = "RemoveOldManualUserPages";
require_once( RUN_MAINTENANCE_IF_MAIN );
