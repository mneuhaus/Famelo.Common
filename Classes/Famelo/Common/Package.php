<?php
namespace Famelo\Common;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Common".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\Flow\Package\Package as BasePackage;

/**
 * The TYPO3.Expose package.
 *
 */
class Package extends BasePackage {
	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect('TYPO3\Flow\Object\DependencyInjection\ProxyClassBuilder', 'extendProxyClass', 'Famelo\Common\Proxy\AccessableProxyClassBuilder', 'buildAccessors');
		$dispatcher->connect('TYPO3\Flow\Command\CoreCommandController', 'additionalProxyClassBuilder', 'Famelo\Common\Proxy\AccessableProxyClassBuilder', 'build');
	}
}

?>