<?php
/**
 * ResourceLoader module for global user customizations.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Szymon Świerkosz
 * @author Kunal Mehta
 */

namespace MediaWiki\GlobalCssJs;

use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;

/**
 * Module for user customizations - runs on all wikis
 */
class ResourceLoaderGlobalUserModule extends ResourceLoaderGlobalModule {

	/** @inheritDoc */
	protected $origin = self::ORIGIN_USER_INDIVIDUAL;

	/**
	 * @param Context $context
	 * @return array
	 */
	protected function getPages( Context $context ) {
		// Note: When computing meta data on a local wiki (not the central wiki),
		// this will produce a UserIdentity object based on the local database, not the
		// foreign/central wiki. Use this object very carefully.
		$user = $context->getUserIdentity();
		$tempUserConfig = MediaWikiServices::getInstance()->getTempUserConfig();
		if ( !$user || !$user->isRegistered() || $tempUserConfig->isTempName( $user->getName() ) ) {
			return [];
		}

		if ( !Hooks::loadForUser( $user ) ) {
			return [];
		}

		$userpage = strtr( $user->getName(), ' ', '_' );
		$config = $this->getConfig();
		$pages = [];

		// Note: This uses the canonical namespace prefix to ensure the same array
		// being returned on both the local and remote wiki. This matters because
		// this method informs getVersionHash() which is used by the browser in the
		// request URI for the central wiki, where it should match its version hash.
		if ( $this->type === 'style' && $config->get( 'AllowUserCss' ) ) {
			$pages["User:$userpage/global.css"] = [ 'type' => 'style' ];
		} elseif ( $this->type === 'script' && $config->get( 'AllowUserJs' ) ) {
			$pages["User:$userpage/global.js"] = [ 'type' => 'script' ];
		}

		return $pages;
	}

	/**
	 * @return string
	 */
	public function getGroup() {
		return 'user';
	}
}
