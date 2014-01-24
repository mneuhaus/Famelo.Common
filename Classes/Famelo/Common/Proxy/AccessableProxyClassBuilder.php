<?php
namespace Famelo\Common\Proxy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Object\Configuration\Configuration;
use TYPO3\Flow\Object\Configuration\ConfigurationArgument;
use TYPO3\Flow\Object\Configuration\ConfigurationProperty;
use TYPO3\Flow\Utility\Arrays;

/**
 * A Proxy Class Builder which adds accessablility functions
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class AccessableProxyClassBuilder {
	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Add access functions for annotated properties
	 *
	 * @param string $className
	 * @param \TYPO3\Flow\Object\Proxy\ProxyClass $proxyClass
	 * @return void
	 */
	public function buildAccessors($className, $proxyClass) {
		$properties = $this->reflectionService->getClassPropertyNames($className);
		$classSchema = $this->reflectionService->getClassSchema($className);

		if ($classSchema === NULL) {
			return;
		}

		$classMethods = get_class_methods($className);
		$classAnnotation = $this->reflectionService->getClassAnnotation($className, 'Famelo\Common\Annotations\Accessable');
		foreach ($properties as $propertyName => $propertyConfiguration) {
			$propertyAnnotation = $this->reflectionService->getPropertyAnnotation($className, $propertyName, 'Famelo\Common\Annotations\Accessable');
			if ($propertyAnnotation === NULL) {
				if ($classAnnotation === NULL) {
					continue;
				}
				$propertyAnnotation = $classAnnotation;
			}

			$notAccessableAnnotation = $this->reflectionService->getPropertyAnnotation($className, $propertyName, 'Famelo\Common\Annotations\NotAccessable');
			if ($notAccessableAnnotation !== NULL) {
				continue;
			}

			$getMethodName = 'get' . ucfirst($propertyName);
			if ($propertyAnnotation->get === TRUE && !in_array($getMethodName, $classMethods)) {
				$proxyMethod = $proxyClass->getMethod($getMethodName);
				$proxyMethod->setMethodBody('return $this->' . $propertyName . ';');
			}

			$setMethodName = 'set' . ucfirst($propertyName);
			if ($propertyAnnotation->set === TRUE && !in_array($setMethodName, $classMethods)) {
				$proxyMethod = $proxyClass->getMethod($setMethodName);
				$proxyMethod->setMethodParametersCode('$' . $propertyName);
				$proxyMethod->setMethodBody('$this->' . $propertyName . ' = $' . $propertyName . ';');
			}

			if ($classSchema->isMultiValuedProperty($propertyName) === FALSE) {
				continue;
			}

			$singularPropertName = Inflector::singularize($propertyName);

			$addMethodName = 'add' . ucfirst($singularPropertName);
			if ($propertyAnnotation->add === TRUE && !in_array($addMethodName, $classMethods)) {
				$proxyMethod = $proxyClass->getMethod($addMethodName);
				$proxyMethod->setMethodParametersCode('$' . $singularPropertName);
				if ($propertyConfiguration['type'] === 'array') {
					$proxyMethod->setMethodBody('$this->' . $propertyName . '[] = $' . $singularPropertName . ';');
				} else {
					$proxyMethod->setMethodBody('$this->' . $propertyName . '->add($' . $singularPropertName . ');');
				}
			}

			$removeMethodName = 'remove' . ucfirst($singularPropertName);
			if ($propertyAnnotation->add === TRUE && !in_array($removeMethodName, $classMethods)) {
				$proxyMethod = $proxyClass->getMethod($removeMethodName);
				$proxyMethod->setMethodParametersCode('$' . $singularPropertName);
				if ($propertyConfiguration['type'] === 'array') {
					$proxyMethod->setMethodBody('unset($this->' . $propertyName . '[array_search($' . $singularPropertName . ', $this->' . $propertyName . ')]);');
				} else {
					$proxyMethod->setMethodBody('$this->' . $propertyName . '->remove($' . $singularPropertName . ');');
				}
			}

			$hasMethodName = 'has' . ucfirst($singularPropertName);
			if ($propertyAnnotation->add === TRUE && !in_array($hasMethodName, $classMethods)) {
				$proxyMethod = $proxyClass->getMethod($hasMethodName);
				$proxyMethod->setMethodParametersCode('$' . $singularPropertName);
				if ($propertyConfiguration['type'] === 'array') {
					$proxyMethod->setMethodBody('return in_array($' . $singularPropertName . ', $this->' . $propertyName . ');');
				} else {
					$proxyMethod->setMethodBody('return $this->' . $propertyName . '->contains($' . $singularPropertName . ');');
				}
			}
		}
	}

}
