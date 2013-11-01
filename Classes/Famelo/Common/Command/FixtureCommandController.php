<?php
namespace Famelo\Common\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Satisfy".            *
 *                                                                        *
 *                                                                        */

use DavidBadura\Fixtures\Exception\RuntimeException;
use DavidBadura\Fixtures\FixtureEvents;
use DavidBadura\Fixtures\FixtureManager\FixtureManager;
use DavidBadura\Fixtures\Loader\DirectoryLoader;
use DavidBadura\Fixtures\Loader\FilterLoader;
use DavidBadura\Fixtures\Loader\JsonLoader;
use DavidBadura\Fixtures\Loader\MatchLoader;
use DavidBadura\Fixtures\Loader\PhpLoader;
use DavidBadura\Fixtures\Loader\TomlLoader;
use DavidBadura\Fixtures\Loader\YamlLoader;
use DavidBadura\Fixtures\Persister\PersisterInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\CommitOrderCalculator;
use Famelo\Common\Command\AbstractInteractiveCommandController;
use Famelo\Common\Fixtures\CsvService;
use Famelo\Common\Fixtures\FlowService;
use Famelo\Satisfy\Domain\Model\User;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Mapping\ClassMetadata;
use TYPO3\Party\Domain\Model\PersonName;

/**
 * satisfy command controller for the Famelo.Satisfy package
 *
 * @Flow\Scope("singleton")
 */
class FixtureCommandController extends AbstractInteractiveCommandController {

    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $entityManager;

    /**
     *
     * @param string $fixturePath
     * @return void
     */
    public function importCommand($fixturePath) {
        $matchLoader = new MatchLoader();
        $matchLoader
            ->add(new PhpLoader(), '*.php')
            ->add(new YamlLoader(), '*.yml')
            ->add(new YamlLoader(), '*.yaml')
            ->add(new JsonLoader(), '*.json')
            ->add(new TomlLoader(), '*.toml')
        ;

        $loader = new DirectoryLoader(
            new FilterLoader($matchLoader)
        );

        $executor = \DavidBadura\Fixtures\Executor\Executor::createDefaultExecutor();
        $objectManager = $this->entityManager;

        if ($objectManager instanceof PersisterInterface) {
            $persister = $objectManager;
        } elseif ($objectManager instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $persister = new \DavidBadura\Fixtures\Persister\MongoDBPersister($objectManager);
        } elseif ($objectManager instanceof \Doctrine\Common\Persistence\ObjectManager) {
            $persister = new \DavidBadura\Fixtures\Persister\DoctrinePersister($objectManager);
        } else {
            throw new RuntimeException();
        }

        $fixtureManager = new FixtureManager($loader, $executor, $persister);

        $fakerService = \Faker\Factory::create();
        $fixtureManager->addService('faker', $fakerService);

        $csvService = new CsvService();
        $fixtureManager->getEventDispatcher()->addListener(FixtureEvents::onPreExecute, array($csvService, 'injectCsv'));

        $servicesClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('\Famelo\Common\Fixtures\FixtureServiceInterface');
        foreach ($servicesClassNames as $servicesClassName) {
            $service = new $servicesClassName();
            $fixtureManager->addService($service->getName(), $service);
        }

        $fixtureManager->load($fixturePath);
    }
}

?>