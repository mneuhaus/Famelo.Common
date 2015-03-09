<?php
namespace Famelo\Common\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Common".         *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;

class PackageController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var PackageManagerInterface
	 * @Flow\Inject
	 */
	protected $packageManager;

	/**
	 * @return void
	 */
	public function indexAction() {
		$packageGroups = array(
			'typo3-flow-package' => array()
		);
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			$packageType = $package->getPackageMetaData()->getPackageType();
			if (!isset($packageGroups[$packageType])) {
				$packageGroups[$packageType] = array();
			}
			$packageGroups[$packageType][] = array(
				'object' => $package,
				'active' => $this->packageManager->isPackageActive($package->getPackageKey()),
				'frozen' => $this->packageManager->isPackageFrozen($package->getPackageKey())
			);
		}
		$this->view->assign('packageGroups', $packageGroups);
	}

	/**
	 *
	 * @param string $type
	 * @return void
	 */
	public function freezeByTypeAction($type) {
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			$packageType = $package->getPackageMetaData()->getPackageType();
			if ($type != $packageType) {
				continue;
			}

			$this->packageManager->freezePackage($package->getPackageKey());
		}
		$this->redirect('index');
	}

	/**
	 *
	 * @param string $type
	 * @return void
	 */
	public function unfreezeByTypeAction($type) {
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			$packageType = $package->getPackageMetaData()->getPackageType();
			if ($type != $packageType) {
				continue;
			}

			$this->packageManager->unfreezePackage($package->getPackageKey());
		}
		$this->redirect('index');
	}

	/**
	 *
	 * @param string $package
	 * @return void
	 */
	public function freezePackageAction($package) {
		$this->packageManager->freezePackage($package);
		$this->redirect('index');
	}

	/**
	 *
	 * @param string $package
	 * @return void
	 */
	public function unfreezePackageAction($package) {
		$this->packageManager->unfreezePackage($package);
		$this->redirect('index');
	}
}

?>