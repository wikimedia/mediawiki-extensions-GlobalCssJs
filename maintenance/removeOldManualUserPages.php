<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 * @author Szymon Świerkosz
 * @author Kunal Mehta
 */

namespace MediaWiki\GlobalCssJs;

use CssContent;
use JavaScriptContent;
use LinkBatch;
use Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\RevisionRecord;
use Title;
use User;

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
		$this->addDescription( 'Remove redundant user script pages that ' .
			'import global.js and/or global.css' );
		$this->addOption( 'user', 'User name', true, true );
		$this->addOption( 'ignorerevisionlimit',
			'Whether to ignore the 1 revision limit', false, false );
		$this->requireExtension( 'GlobalCssJs' );
	}

	public function execute() {
		$this->ignoreRevisionLimit = $this->hasOption( 'ignorerevisionlimit' );
		$userName = $this->getOption( 'user' );
		$user = User::newFromName( $userName );

		if ( !$user->getId() || !Hooks::loadForUser( $user ) ) {
			$this->output( "$userName does not load global modules on this wiki.\n" );
			return;
		}
		$skinFactory = MediaWikiServices::getInstance()->getSkinFactory();
		$skins = array_keys( $skinFactory->getAllowedSkins() );
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
	 * @return RevisionRecord|bool
	 */
	private function checkTitle( Title $title ) {
		if ( !$title->exists() ) {
			$this->output( "{$title->getPrefixedText()} does not exist on this wiki.\n" );
			return false;
		}

		$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		$rev = $revisionLookup->getRevisionByTitle( $title );
		if ( !$this->ignoreRevisionLimit && $revisionLookup->getPreviousRevision( $rev ) ) {
			$this->output( "{$title->getPrefixedText()} has more than one revision, skipping.\n" );
			return false;
		}

		return $rev;
	}

	/**
	 * Returns the domain name of the central wiki escaped to use in a regex.
	 *
	 * @return string
	 */
	private function getCentralWikiDomain() {
		global $wgGlobalCssJsConfig;
		$rl = MediaWikiServices::getInstance()->getResourceLoader();
		$sources = $rl->getSources();
		// Use api.php instead of load.php because it's more likely to be on the same domain
		$api = $sources[$wgGlobalCssJsConfig['source']]['apiScript'];
		$parsed = wfParseUrl( $api );
		return preg_quote( $parsed['host'] );
	}

	/**
	 * @param Title $title
	 * @param string $reason
	 * @param string $userName
	 */
	private function deletePage( Title $title, $reason, $userName ) {
		// phpcs:ignore MediaWiki.Usage.DeprecatedGlobalVariables.Deprecated$wgUser
		global $wgUser;
		$services = MediaWikiServices::getInstance();
		$page = $services->getWikiPageFactory()->newFromTitle( $title );
		$user = $services->getUserFactory()->newFromName( 'Maintenance script' );
		'@phan-var \MediaWiki\User\UserIdentity $user';
		$services->getUserGroupManager()->addUserToGroup( $user, 'bot' );

		// For hooks not using RequestContext (e.g. AbuseFilter)
		$wgUser = $user;
		$errors = [];
		'@phan-var \MediaWiki\User\UserIdentity $user';
		$status = $page->doDeleteArticleReal(
			wfMessage( $reason, $userName )->inContentLanguage()->text(),
			$user, false, true, $errors, null, [], 'delete', true
		);
		if ( $status->isGood() ) {
			$this->output( "{$title->getPrefixedText()} was deleted.\n" );
		} else {
			$this->output( "{$title->getPrefixedText()} could not be deleted:\n" .
				$status->getWikiText() . "\n" );
		}
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

	/**
	 * @param string $text
	 * @param string $domain
	 * @param string $userName
	 * @return bool
	 */
	public function checkCss( $text, $domain, $userName ) {
		$userName = $this->normalizeUserName( $userName );
		preg_match( "/@import url\('(https?:)?\/\/$domain\/w\/index\.php\?title=User:$userName" .
			"\/global\.css&action=raw&ctype=text\/css'\);/", $text, $matches );
		return isset( $matches[0] ) ? $matches[0] === $text : false;
	}

	/**
	 * @param User $user
	 * @param string $skin
	 */
	private function removeCSS( User $user, $skin ) {
		$userName = $user->getName();
		$title = $user->getUserPage()->getSubpage( $skin . '.css' );
		$rev = $this->checkTitle( $title );
		if ( !$rev ) {
			return;
		}

		/** @var CssContent $content */
		$content = $rev->getContent( SlotRecord::MAIN );
		$text = trim( $content->getNativeData() );
		$domain = $this->getCentralWikiDomain();
		if ( !$this->checkCss( $text, $domain, $userName ) ) {
			$this->output( "{$title->getPrefixedText()} did not match the specified regular " .
				"expression. Skipping.\n" );
			return;
		}

		// Delete!
		$this->deletePage( $title, 'globalcssjs-delete-css', $userName );
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
		$new = [];
		foreach ( $exploded as $line ) {
			$trimmed = trim( $line );
			if ( $trimmed !== '' && substr( $trimmed, 0, 2 ) !== '//' ) {
				$new[] = $line;
			}
		}

		return implode( '\n', $new );
	}

	/**
	 * @param string $text
	 * @param string $domain
	 * @param string $userName
	 * @return bool
	 */
	public function checkJs( $text, $domain, $userName ) {
		$text = $this->stripComments( $text );
		$userName = $this->normalizeUserName( $userName );
		preg_match( "/(mw\.loader\.load|importScriptURI)\s*\(\s*('|\")(https?:)?\/\/$domain" .
			"\/w\/index\.php\?title=User:$userName\/global\.js&action=raw&ctype=text\/javascript" .
			"(&smaxage=\d*?)?(&maxage=\d*?)?('|\")\s*\)\s*;?/", $text, $matches );
		return isset( $matches[0] ) ? $matches[0] === $text : false;
	}

	/**
	 * @param User $user
	 * @param string $skin
	 */
	private function removeJS( User $user, $skin ) {
		$userName = $user->getName();
		$title = $user->getUserPage()->getSubpage( $skin . '.js' );
		$rev = $this->checkTitle( $title );
		if ( !$rev ) {
			return;
		}

		/** @var JavaScriptContent $content */
		$content = $rev->getContent( SlotRecord::MAIN );
		$text = trim( $content->getNativeData() );
		$domain = $this->getCentralWikiDomain();
		if ( !$this->checkJs( $text, $domain, $userName ) ) {
			$this->output( "{$title->getPrefixedText()} did not match the specified regular " .
				"expression. Skipping.\n" );
			return;
		}

		// Delete!
		$this->deletePage( $title, 'globalcssjs-delete-js', $userName );
	}

}

$maintClass = RemoveOldManualUserPages::class;
require_once RUN_MAINTENANCE_IF_MAIN;
