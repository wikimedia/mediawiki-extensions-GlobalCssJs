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

/**
 * Module for sitewide global customizations
 */
class ResourceLoaderGlobalSiteModule extends ResourceLoaderGlobalModule {

	protected $origin = self::ORIGIN_USER_SITEWIDE;

	/**
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		if ( !ConfigFactory::getDefaultInstance()->makeConfig( 'globalcssjs' )->get( 'UseGlobalSiteCssJs' ) ) {
			return array();
		}

		$config = $context->getResourceLoader()->getConfig();
		$pages = array();

		if ( $this->type === 'style' && $config->get( 'UseSiteCss' ) ) {
			$pages["MediaWiki:Global.css"] = array( 'type' => 'style' );
			$pages['MediaWiki:Global-' . $context->getSkin() . '.css'] = array( 'type' => 'style' );
		} elseif ( $this->type === 'script' && $config->get( 'UseSiteJs' ) ) {
			$pages["MediaWiki:Global.js"] = array( 'type' => 'script' );
			$pages['MediaWiki:Global-' . $context->getSkin() . '.js'] = array( 'type' => 'script' );
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
