<?php
namespace Famelo\Common\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Common".         *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;

class CloneMadnessController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var CacheManager
	 * @Flow\Inject
	 */
	protected $cacheManager;
	/**
	 * @var ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var array()
	 */
	protected $uuids = array();

	/**
	 * @Flow\Inject
	 * @var \Famelo\Common\Services\CloneService
	 */
	protected $cloneService;

	/**
	 * @param string $sourceUuid
	 * @param string $cloneUuid
	 * @param string $className
	 * @return void
	 */
	public function indexAction($sourceUuid = NULL, $cloneUuid = NULL, $className = NULL) {
		if ($sourceUuid !== NULL) {
			$source = $this->persistenceManager->getObjectByIdentifier($sourceUuid, $className);
			$clone = $this->persistenceManager->getObjectByIdentifier($cloneUuid, $className);
			$this->uuids[$sourceUuid] = 'root';
			$uuids[$sourceUuid] = array(
				'path' => 'root',
				'className' => $className,
				'cloned' => $sourceUuid !== $cloneUuid,
				'sourceUuid' => $sourceUuid,
				'cloneUuid' => $cloneUuid,
				'name' => is_object($source) ? $source->__toString() : '',
				'children' => $this->gatherChildUuids($source, $clone)
			);

			$this->view->assign('uuids', $uuids);
			$this->view->assign('sourceUuid', $sourceUuid);
			$this->view->assign('cloneUuid', $cloneUuid);
			$this->view->assign('className', $className);
		}
	}


	/**
	 * @param string $sourceUuid
	 * @param string $className
	 */
	public function cloneEntityAction($sourceUuid, $className) {
		$source = $this->persistenceManager->getObjectByIdentifier($sourceUuid, $className);
		$clone = $this->cloneService->deepCopy($source);
		$this->persistenceManager->add($clone);
		$cloneUuid = $this->persistenceManager->getIdentifierByObject($clone);

		$this->redirect('index', NULL, NULL, array(
			'sourceUuid' => $sourceUuid,
			'cloneUuid' => $cloneUuid,
			'className' => $className
		));
	}

	public function gatherChildUuids($source, $clone, $path = array('root'), $depth = 0) {
		$childUuids = array();
		if ($depth > 3) {
			return $childUuids;
		}
		$collectionClassNames = array(
			'SplObjectStorage',
			'Doctrine\Common\Collections\Collection',
			'Doctrine\Common\Collections\ArrayCollection',
			'Doctrine\ORM\PersistentCollection'
		);

		$className = ltrim($this->reflectionService->getClassNameByObject($source), '\\');

		$propertyNames = $this->reflectionService->getClassPropertyNames($className);

		foreach ($propertyNames as $propertyName) {
			try {
				$sourcePropertyValue = ObjectAccess::getProperty($source, $propertyName);
				$clonePropertyValue = ObjectAccess::getProperty($clone, $propertyName);
			} catch(\Exception $e) {
				continue;
			}

			if (!is_object($sourcePropertyValue)) {
				continue;
			}

			$propertyPath = $path;
			$propertyPath[] = $propertyName;

			$propertyClassName = ltrim($this->reflectionService->getClassNameByObject($sourcePropertyValue), '\\');

			if ($this->reflectionService->isClassAnnotatedWith($propertyClassName, '\TYPO3\Flow\Annotations\Entity')) {

				if($this->isAlreadyGathered($sourcePropertyValue) || $propertyPath == 'persistenceManager') {
					continue;
				}

				$childUuid = $this->persistenceManager->getIdentifierByObject($sourcePropertyValue);
				$cloneChildUuid = $this->persistenceManager->getIdentifierByObject($clonePropertyValue);

				$childUuids[$childUuid] = array(
					'path' => implode('.', $propertyPath),
					'className' => $propertyClassName,
					'cloned' => $childUuid !== $cloneChildUuid,
					'sourceUuid' => $childUuid,
					'cloneUuid' => $cloneChildUuid,
					'name' => $sourcePropertyValue->__toString(),
					'children' => $this->gatherChildUuids($sourcePropertyValue, $clonePropertyValue, $propertyPath, $depth + 1)
				);
				$this->uuids[$childUuid] = implode('.', $propertyPath);

			} elseif (in_array($propertyClassName, $collectionClassNames)) {
				$cloneChildren = array();
				foreach ($clonePropertyValue as $index => $childEntity) {
					$cloneChildren[$index] = $childEntity;
				}
				foreach ($sourcePropertyValue as $index => $childEntity) {
					if($this->isAlreadyGathered($childEntity)) {
						continue;
					}
					$cloneChildEntity = $clonePropertyValue[$index];

					$childPath = $propertyPath;
					$childPath[] = $index;

					$childClassName = ltrim($this->reflectionService->getClassNameByObject($childEntity), '\\');
					$childUuid = $this->persistenceManager->getIdentifierByObject($childEntity);
					if (is_object($cloneChildEntity)) {
						$cloneChildUuid = $this->persistenceManager->getIdentifierByObject($cloneChildEntity);
					} else {
						$cloneChildUuid = 'NULL';
					}

					$childUuids[$childUuid] = array(
						'path' => implode('.', $childPath),
						'className' => $childClassName,
						'cloned' => $childUuid !== $cloneChildUuid,
						'sourceUuid' => $childUuid,
						'cloneUuid' => $cloneChildUuid,
						'name' => $childEntity->__toString(),
						'children' => $this->gatherChildUuids($childEntity, $cloneChildEntity, $childPath, $depth + 1)
					);
					$this->uuids[$childUuid] = implode('.', $childPath);
				}
			}
		}
		return $childUuids;
	}

	public function isAlreadyGathered($source) {
		$uuid = $this->persistenceManager->getIdentifierByObject($source);
		return isset($this->uuids[$uuid]);
	}
}

?>