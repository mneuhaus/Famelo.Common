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
 * path that needs contains a reference to an entity, that needs to be
 * updated when the entity is cloned
 *
 * @Annotation
 * @Target("CLASS")
 */
final class CloneReference {
	/**
	 * @var string
	 */
	public $path = NULL;

	/**
	 * @param array $values
	 */
	public function __construct(array $values) {
		if (isset($values['value'])) {
			$this->path = $values['value'];
		}
		if (isset($values['path'])) {
			$this->path = $values['path'];
		}
	}

}
