<?php
namespace Famelo\Common\Annotations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Used to enable automatic generation of get, set, add, remove and has
 * methods for properties
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
final class Accessable {

	/**
	 * Path of a setting which should be injected into the property
	 *
	 * @var boolean
	 */
	public $get = TRUE;

	/**
	 * Path of a setting which should be injected into the property
	 *
	 * @var boolean
	 */
	public $set = TRUE;

	/**
	 * Path of a setting which should be injected into the property
	 *
	 * @var boolean
	 */
	public $add = TRUE;

	/**
	 * Path of a setting which should be injected into the property
	 *
	 * @var boolean
	 */
	public $remove = TRUE;

	/**
	 * Path of a setting which should be injected into the property
	 *
	 * @var boolean
	 */
	public $has = TRUE;

	/**
	 * @param array $values
	 */
	public function __construct(array $values) {
		foreach ($values as $key => $value) {
			if (strtolower($value) === 'false') {
				$value = FALSE;
			} else if (strtolower($value) === 'true') {
				$value = TRUE;
			}
			$this->$key = (boolean)$value;
		}
	}

}
