<?php
/**
 * ResourceLoader module for global site customizations.
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
 * @author Kunal Mehta
 */

namespace MediaWiki\GlobalCssJs;

use MediaWiki\ResourceLoader\Context;

/**
 * Module for sitewide global customizations
 */
class ResourceLoaderGlobalSiteModule extends ResourceLoaderGlobalModule {

	/** @inheritDoc */
	protected $origin = self::ORIGIN_USER_SITEWIDE;

	/**
	 * @param Context $context
	 * @return array
	 */
	protected function getPages( Context $context ) {
		$config = $this->getConfig();
		if ( !$config->get( 'UseGlobalSiteCssJs' ) ) {
			return [];
		}

		$pages = [];

		if ( $this->type === 'style' && $config->get( 'UseSiteCss' ) ) {
			$pages["MediaWiki:Global.css"] = [ 'type' => 'style' ];
			$pages['MediaWiki:Global-' . $context->getSkin() . '.css'] = [ 'type' => 'style' ];
		} elseif ( $this->type === 'script' && $config->get( 'UseSiteJs' ) ) {
			$pages["MediaWiki:Global.js"] = [ 'type' => 'script' ];
			$pages['MediaWiki:Global-' . $context->getSkin() . '.js'] = [ 'type' => 'script' ];
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
