<?php
/**
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
 * @author Kunal Mehta
 */

/**
 * Module for sitewide global customizations
 * This module does not do provide any resources directly.
 * It instructs ResourceLoader to load a module from a remote site.
 */
class ResourceLoaderGlobalSiteModule extends ResourceLoaderGlobalModule {

	protected $origin = self::ORIGIN_USER_SITEWIDE;

	/**
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		global $wgUseSiteCss, $wgUseSiteJs;
		$pages = array();

		if ( $wgUseSiteJs ) {
			$pages["MediaWiki:Global.js"] = array( 'type' => 'script' );
		}

		if ( $wgUseSiteCss ) {
			$pages["MediaWiki:Global.css"] = array( 'type' => 'style' );
		}

		return $pages;
	}

	/**
	 * @return string
	 */
	public function getGroup() {
		return 'site';
	}
}
