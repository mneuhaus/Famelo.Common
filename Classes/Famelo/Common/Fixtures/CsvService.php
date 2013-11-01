<?php
namespace Famelo\Common\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Common".         *
 *                                                                        *
 *                                                                        */

use DavidBadura\Fixtures\FixtureManager\FixtureManager;
use DavidBadura\Fixtures\Fixture\FixtureCollection;
use DavidBadura\Fixtures\Fixture\FixtureData;
use DavidBadura\Fixtures\ServiceProvider\FixtureAwareServiceInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Form\Core\Model\AbstractSection;

class CsvService {
	/**
	 * @var array
	 */
	protected $row = array();

	public function injectCsv($event) {
		foreach ($event->getCollection() as $fixture) {
			$properties = $fixture->getProperties();
			if ($properties->has('csv')) {
				$configuration = $properties->get('csv');
				$source = $configuration['source'];
				$rows = $this->getRows($source, $configuration);

				if (isset($configuration['headerRow'])) {
					$offset = intval($configuration['headerRow']);
					if ($offset === 0) {
						$offset = 1;
					}
					$rows = array_slice($rows, $offset);
				}

				$fixtureDataTemplate = $this->getFixtureDataTemplate($fixture);
				$fixture->remove($fixtureDataTemplate);
				$provider = $event->getFixtureManager()->getServiceProvider();

				$keys = array();
	   	     	$event->getFixtureManager()->addService('csv', $this);
				foreach ($rows as $key => $row) {
					$this->row = $row;

					$newKey = str_replace('{key}', $key, $fixtureDataTemplate->getKey());
					$this->replaceServicePlaceholder($newKey, $provider);

					if (in_array($newKey, $keys)) {
						continue;
					}
					$keys[] = $newKey;

					$fixtureData = new FixtureData($newKey, $fixtureDataTemplate->getData());
					$this->replaceServicePlaceholders($fixtureData, $provider);
					$fixture->add($fixtureData);
				}
				$event->getFixtureManager()->removeService('csv');
			}
        }
	}

	public function getRows($source, $configuration) {
		$configuration = array_merge(array(
			'delimiter' => ';',
			'enclosure' => '"'
		), $configuration);

		$content = file_get_contents(FLOW_PATH_ROOT . 'Data/Fixtures/' . $source);
		$rows = explode("\r", $content);
		foreach ($rows as $key => $value) {
			$rows[$key] = str_getcsv($value, $configuration['delimiter'], $configuration['enclosure']);
		}
		return $rows;
	}

	public function getFixtureDataTemplate($fixtures) {
		foreach ($fixtures as $fixture) {
			return $fixture;
		}
	}

    /**
     *
     * @param Fixture $fixture
     */
    protected function replaceServicePlaceholders(FixtureData $fixtureData, $provider) {
        $data = $fixtureData->getData();

        array_walk_recursive($data, function(&$item) use ($provider) {
        	$this->replaceServicePlaceholder($item, $provider);
        });

        $fixtureData->setData($data);
    }

    public function replaceServicePlaceholder(&$item, $provider) {
		$matches = array();
        if (preg_match(FixtureManager::SERVICE_PLACEHOLDER_PATTERN, $item, $matches)) {
            $service = $provider->get($matches[1]);
            $attributes = explode(',', $matches[3]);
            $result = call_user_func_array(array($service, $matches[2]), $attributes);
            $item = str_replace($matches[0], $result, $item);
        }
    }

	public function column($id) {
		return $this->row[$id];
	}

	public function columnSha1($id) {

		return sha1($this->column($id));
	}
}

?>