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

class CascadeMadnessController extends \TYPO3\Flow\Mvc\Controller\ActionController {

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
	 * @param string $baseUuid
	 * @return void
	 */
	public function indexAction($baseUuid = NULL) {
		if ($baseUuid !== NULL) {
			$cache = $this->cacheManager->getCache('Famelo_Common_CascadeMadness');
			$identifier = md5($baseUuid);

			$uuids = $cache->get($identifier);
			foreach ($uuids as $uuid => $data) {
				$entity = $this->persistenceManager->getObjectByIdentifier($uuid, $data['className']);
				$uuids[$uuid]['deleted'] = ($entity === NULL);
			}
			$this->view->assign('uuids', $uuids);
		}
	}


	/**
	 * @param string $baseUuid
	 * @param string $className
	 */
	public function gatherUuidsAction($baseUuid, $className) {
		$cache = $this->cacheManager->getCache('Famelo_Common_CascadeMadness');
		$identifier = md5($baseUuid);

		$cache->set($identifier, $this->gatherUuids($baseUuid, $className));

		$this->redirect('index', NULL, NULL, array('baseUuid' => $baseUuid));
	}

	/**
	 * @param string $uuid
	 * @param string $className
	 */
	public function deleteByUuidAction($uuid, $className) {
		$entity = $this->persistenceManager->getObjectByIdentifier($uuid, $className);
		$this->persistenceManager->remove($entity);
		$this->persistenceManager->persistAll();
		return 'done';
	}


	public function gatherUuids($baseUuid, $className) {
		$entity = $this->persistenceManager->getObjectByIdentifier($baseUuid, $className);

		$this->uuids[$baseUuid] = array(
			'path' => 'root',
			'className' => $className,
			'name' => is_object($entity) ? $entity->__toString() : ''
		);

		$this->gatherChildUuids($entity);

		return $this->uuids;
	}

	public function gatherChildUuids($entity, $path = array('root')) {
		$collectionClassNames = array(
			'SplObjectStorage',
			'Doctrine\Common\Collections\Collection',
			'Doctrine\Common\Collections\ArrayCollection',
			'Doctrine\ORM\PersistentCollection'
		);

		$className = ltrim($this->reflectionService->getClassNameByObject($entity), '\\');

		$propertyNames = $this->reflectionService->getClassPropertyNames($className);

		foreach ($propertyNames as $propertyName) {
			try {
				$propertyValue = ObjectAccess::getProperty($entity, $propertyName);
			} catch(\Exception $e) {
				continue;
			}
			if (!is_object($propertyValue)) {
				continue;
			}
			$propertyPath = $path;
			$propertyPath[] = $propertyName;

			$propertyClassName = ltrim($this->reflectionService->getClassNameByObject($propertyValue), '\\');

			if ($this->reflectionService->isClassAnnotatedWith($propertyClassName, '\TYPO3\Flow\Annotations\Entity')) {

				if($this->isAlreadyGathered($propertyValue) || $propertyPath == 'persistenceManager') {
					continue;
				}

				$childUuid = $this->persistenceManager->getIdentifierByObject($propertyValue);

				$this->uuids[$childUuid] = array(
					'path' => implode('.', $propertyPath),
					'className' => $propertyClassName,
					'name' => $propertyValue->__toString()
				);
				$this->gatherChildUuids($propertyValue, $propertyPath);

			} elseif (in_array($propertyClassName, $collectionClassNames)) {
				// $propertyPath[] = $propertyName;
				foreach ($propertyValue as $index => $childEntity) {
					if($this->isAlreadyGathered($childEntity)) {
						continue;
					}

					$childPath = $propertyPath;
					$childPath[] = $index;

					$childClassName = ltrim($this->reflectionService->getClassNameByObject($childEntity), '\\');
					$childUuid = $this->persistenceManager->getIdentifierByObject($childEntity);

					$this->uuids[$childUuid] = array(
						'path' => implode('.', $childPath),
						'className' => $childClassName,
						'name' => $childEntity->__toString()
					);
					$this->gatherChildUuids($childEntity, $childPath);
				}
			}
		}
	}

	public function isAlreadyGathered($entity) {
		$uuid = $this->persistenceManager->getIdentifierByObject($entity);
		return isset($this->uuids[$uuid]);
	}
}

?>