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
 * @author Szymon Świerkosz
 * @author Kunal Mehta
 */

/**
 * Base class for global modules.
 *
 * This module does not provide any resources directly.
 * It instructs ResourceLoader to load a module from a remote site.
 */
abstract class ResourceLoaderGlobalModule extends ResourceLoaderWikiModule {

	/**
	 * name of global wiki database
	 * @var string
	 */
	protected $wiki;

	/**
	 * name of a ResourceLoader source pointing to the global wiki
	 * @var string
	 */
	protected $source;

	/** @var string Position on the page to load this module at */
	protected $position = 'bottom';

	/**
	 * Either 'style' or 'script'
	 *
	 * @var string
	 */
	protected $type;

	public function __construct( $options ) {
		foreach ( $options as $member => $option ) {
			switch ( $member ) {
				case 'wiki':
				case 'source':
				case 'position':
					$this->{$member} = (string)$option;
					break;
				case 'type':
					if ( $option !== 'style' && $option !== 'script' ) {
						throw new InvalidArgumentException( "type must be either 'style' or 'script', not '$option'" );
					}
					$this->type = $option;
					break;
			}
		}
	}

	/**
	 * @return string
	 */
	public function getSource() {
		return wfWikiID() === $this->wiki ? 'local' : $this->source;
	}

	/**
	 * @return IDatabase
	 */
	protected function getDB() {
		if ( $this->wiki === wfWikiID() ) {
			return wfGetDB( DB_SLAVE );
		} else {
			return wfGetLB( $this->wiki )->getConnectionRef( DB_SLAVE, array(), $this->wiki );
		}
	}

	public function getPosition() {
		return $this->position;
	}
}
