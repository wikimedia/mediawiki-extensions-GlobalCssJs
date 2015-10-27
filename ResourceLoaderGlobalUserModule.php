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

/**
 * Module for user customizations - runs on all wikis
 */
class ResourceLoaderGlobalUserModule extends ResourceLoaderGlobalModule {

	protected $origin = self::ORIGIN_USER_INDIVIDUAL;

	/**
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		$username = $context->getUser();

		if ( $username === null ) {
			return array();
		}

		// Note, this will validate the user's name against
		// the local site rather than the target site
		$user = User::newFromName( $username );
		if ( !$user || !$user->getId() ) {
			return array();
		}

		if ( !GlobalCssJsHooks::loadForUser( $user ) ) {
			return array();
		}

		$userpage = $user->getUserPage()->getDBkey();
		$config = $context->getResourceLoader()->getConfig();
		$pages = array();

		if ( $this->type === 'style' && $config->get( 'AllowUserCss' ) ) {
			$pages["User:$userpage/global.css"] = array( 'type' => 'style' );
		} elseif ( $this->type === 'script' && $config->get( 'AllowUserJs' ) ) {
			$pages["User:$userpage/global.js"] = array( 'type' => 'script' );
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
