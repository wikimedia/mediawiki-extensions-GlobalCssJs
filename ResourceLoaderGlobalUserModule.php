<?php
/**
 * Resource loader module for global user customizations.
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
 * This module does not provide any resources directly.
 * It instructs ResourceLoader to load a module from a remote site.
 */
class ResourceLoaderGlobalUserModule extends ResourceLoaderGlobalModule {

	protected $origin = self::ORIGIN_USER_INDIVIDUAL;

	/**
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		global $wgAllowUserJs, $wgAllowUserCss;
		$username = $context->getUser();

		if ( $username === null ) {
			return array();
		}

		// Get the normalized title of the user's user page
		// Note this validates against the local site rather than
		// the target site.
		$userpageTitle = Title::makeTitleSafe( NS_USER, $username );

		if ( !$userpageTitle instanceof Title ) {
			return array();
		}

		$userpage = $userpageTitle->getDBkey();
		$pages = array();

		if ( $wgAllowUserJs ) {
			$pages["User:$userpage/global.js"] = array( 'type' => 'script' );
		}

		if ( $wgAllowUserCss ) {
			$pages["User:$userpage/global.css"] = array( 'type' => 'style' );
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