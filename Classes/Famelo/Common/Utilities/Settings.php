<?php
namespace Famelo\Common\Utilities;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Common".         *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Form\Core\Model\AbstractSection;

/**
 *
 */
class Settings {
	/**
	 * @var \Famelo\Common\Utilities\Settings
	 */
	protected static $instance;

	/**
     * @Flow\Inject
     * @var \TYPO3\Flow\Configuration\ConfigurationManager
     */
   protected $configurationManager;

	public static function getInstance() {
		if (self::$instance === NULL) {
			self::$instance = new Settings();
		}
		return self::$instance;
	}

	public static function get($path = NULL) {
		return self::getInstance()->getSettings($path);
	}

	public function getSettings($path) {
		return $this->configurationManager->getConfiguration('Settings', $path);
	}
}

?>