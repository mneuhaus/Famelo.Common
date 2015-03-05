<?php
namespace Famelo\Common\Services;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "CE.Reports".            *
 *                                                                        *
 *                                                                        */

use Doctrine\Common\Collections\ArrayCollection;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * @Flow\Scope("singleton")
 */
class CloneService {
	/**
	 * @Flow\Inject
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	public function deepCopy($source, $copy = NULL) {
		$className = $this->reflectionService->getClassNameByObject($source);
		if ($copy === NULL) {
			$copy = new $className();
		}
		$propertyNames = $this->reflectionService->getClassPropertyNames($className);
		foreach ($propertyNames as $propertyName) {
			if (ObjectAccess::isPropertySettable($copy, $propertyName)) {
				if ($this->reflectionService->isPropertyAnnotatedWith($className, $propertyName, 'Famelo\Common\Annotations\CloneProperty')) {
					if ($this->reflectionService->isPropertyAnnotatedWith($className, $propertyName, 'Doctrine\ORM\Mapping\OneToMany')
						|| $this->reflectionService->isPropertyAnnotatedWith($className, $propertyName, 'Doctrine\ORM\Mapping\ManyToMany')) {
						$collection = new ArrayCollection();
						foreach (ObjectAccess::getProperty($source, $propertyName) as $childSource) {
							if ($childSource !== NULL) {
								$childCopy = $this->deepCopy($childSource);
								$this->persistenceManager->add($childCopy);
								$collection->add($childCopy);
							}
						}
						ObjectAccess::setProperty($copy, $propertyName, $collection);
					} else {
						if (ObjectAccess::getProperty($source, $propertyName) !== NULL) {
							$childCopy = $this->deepCopy(ObjectAccess::getProperty($source, $propertyName));
							$this->persistenceManager->add($childCopy);
							ObjectAccess::setProperty($copy, $propertyName, $childCopy);
						}
					}
				} else {
					$sourceValue = ObjectAccess::getProperty($source, $propertyName);
					ObjectAccess::setProperty($copy, $propertyName, $sourceValue);
				}
			}
		}

		if ($this->reflectionService->isClassAnnotatedWith($className, 'Famelo\Common\Annotations\CloneReference')) {
			$annotations = $this->reflectionService->getClassAnnotations($className, 'Famelo\Common\Annotations\CloneReference');
			foreach ($annotations as $annotation) {
				$parts = explode('.', $annotation->path);
				$targetPropertyName = array_pop($parts);
				$targetObjectPath = implode('.', $parts);
				if ($this->reflectionService->isPropertyAnnotatedWith($className, $targetObjectPath, 'Doctrine\ORM\Mapping\OneToMany')
						|| $this->reflectionService->isPropertyAnnotatedWith($className, $targetObjectPath, 'Doctrine\ORM\Mapping\ManyToMany')) {
					$collection = ObjectAccess::getPropertyPath($copy, $targetObjectPath);
					foreach ($collection as $targetObject) {
						if (is_object($targetObject)) {
							ObjectAccess::setProperty($targetObject, $targetPropertyName, $copy);
						}
					}
				} else {
					$targetObject = ObjectAccess::getPropertyPath($copy, $targetObjectPath);
					if (is_object($targetObject)) {
						ObjectAccess::setProperty($targetObject, $targetPropertyName, $copy);
					}
				}
			}
		}
		return $copy;
	}
}