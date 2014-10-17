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
use TYPO3\Flow\Utility\Files;
use TYPO3\Party\Domain\Model\PersonName;

/**
 * satisfy command controller for the Famelo.Satisfy package
 *
 * @Flow\Scope("singleton")
 */
class DbCommandController extends AbstractInteractiveCommandController {

    /**
     * @var \TYPO3\Flow\Cache\CacheManager
     * @Flow\Inject
     */
    protected $cacheManager;

	/**
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * Use this command to quickly truncate all doctrine tables in the current configured database (except flow_doctrine_migrationstatus)
	 *
	 * @param boolean $noConfirmation don't ask for confirmation
	 * @return void
	 */
	public function purgeAllCommand($noConfirmation = FALSE) {
		if ($noConfirmation === TRUE || $this->askConfirmation('Are you sure, you want to truncate all existing Tables? [yes/no]' . chr(10))) {
			$connection = $this->entityManager->getConnection();
			$orderedTables = $this->getOrderedTables();
        	$platform = $this->entityManager->getConnection()->getDatabasePlatform();

            $skipTables = array();
			$connection->executeUpdate("SET foreign_key_checks = 0;");
			foreach($orderedTables as $table) {
                if (in_array($table, $skipTables)) {
                    continue;
                }
				$this->outputLine('Truncating: ' . $table);
        		$connection->executeUpdate($platform->getTruncateTableSQL($table, true));
        	}
        	$connection->executeUpdate("SET foreign_key_checks = 1;");


            // Flush some cache to trigger the regenration of the Roles
            $this->cacheManager->getCache('Flow_Security_Policy')->flush();
            $objectConfigurationCache = $this->cacheManager->getCache('Flow_Object_Configuration');
            $objectConfigurationCache->remove('allAspectClassesUpToDate');
            $objectConfigurationCache->remove('allCompiledCodeUpToDate');
            $objectClassesCache = $this->cacheManager->getCache('Flow_Object_Classes');
            $objectClassesCache->flush();

        	$this->outputLine();
        	$this->outputLine('Done');
		}
	}

    /**
     * Use this command to quickly drop all doctrine tables in the current configured database
     *
     * @param boolean $noConfirmation don't ask for confirmation
     * @return void
     */
    public function dropAllCommand($noConfirmation = FALSE) {
        if ($noConfirmation === TRUE || $this->askConfirmation('Are you sure, you want to drop all existing Tables? [yes/no]' . chr(10))) {
            $connection = $this->entityManager->getConnection();
            $orderedTables = $this->getOrderedTables();
            $platform = $this->entityManager->getConnection()->getDatabasePlatform();

            $skipTables = array();
            $connection->executeUpdate("SET foreign_key_checks = 0;");
            $orderedTables[] = 'flow_doctrine_migrationstatus';
            foreach($orderedTables as $table) {
                if (in_array($table, $skipTables)) {
                    continue;
                }
                $this->outputLine('Dropping: ' . $table);
                try {
                    $connection->executeUpdate($platform->getDropTableSQL($table, true));
                } catch(\Exception $e) {

                }
            }
            $connection->executeUpdate("SET foreign_key_checks = 1;");

            // Flush some cache to trigger the regenration of the Roles
            $this->cacheManager->getCache('Flow_Security_Policy')->flush();
            $objectConfigurationCache = $this->cacheManager->getCache('Flow_Object_Configuration');
            $objectConfigurationCache->remove('allAspectClassesUpToDate');
            $objectConfigurationCache->remove('allCompiledCodeUpToDate');
            $objectClassesCache = $this->cacheManager->getCache('Flow_Object_Classes');
            $objectClassesCache->flush();

            $this->outputLine();
            $this->outputLine('Done');
        }
    }

	public function getOrderedTables() {
  		$classes = array();
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metadatas as $metadata) {
            if ( ! $metadata->isMappedSuperclass) {
                $classes[] = $metadata;
            }
        }

        $commitOrder = $this->getCommitOrder($this->entityManager, $classes);

        // Drop association tables first
        $orderedTables = $this->getAssociationTables($commitOrder);

        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

        // Drop tables in reverse commit order
        for ($i = count($commitOrder) - 1; $i >= 0; --$i) {
            $class = $commitOrder[$i];

            if (($class->isInheritanceTypeSingleTable() && $class->name != $class->rootEntityName)
                || $class->isMappedSuperclass) {
                continue;
            }

            $orderedTables[] = $class->getQuotedTableName($platform);
        }
        return $orderedTables;
    }

    protected function getCommitOrder(EntityManager $em, array $classes) {
        $calc = new CommitOrderCalculator;

        foreach ($classes as $class) {
            $calc->addClass($class);

            // $class before its parents
            foreach ($class->parentClasses as $parentClass) {
                $parentClass = $em->getClassMetadata($parentClass);

                if ( ! $calc->hasClass($parentClass->name)) {
                    $calc->addClass($parentClass);
                }

                $calc->addDependency($class, $parentClass);
            }

            foreach ($class->associationMappings as $assoc) {
                if ($assoc['isOwningSide']) {
                    $targetClass = $em->getClassMetadata($assoc['targetEntity']);

                    if ( ! $calc->hasClass($targetClass->name)) {
                        $calc->addClass($targetClass);
                    }

                    // add dependency ($targetClass before $class)
                    $calc->addDependency($targetClass, $class);

                    // parents of $targetClass before $class, too
                    foreach ($targetClass->parentClasses as $parentClass) {
                        $parentClass = $em->getClassMetadata($parentClass);

                        if ( ! $calc->hasClass($parentClass->name)) {
                            $calc->addClass($parentClass);
                        }

                        $calc->addDependency($parentClass, $class);
                    }
                }
            }
        }

        return $calc->getCommitOrder();
    }

    protected function getAssociationTables(array $classes) {
        $associationTables = array();

        foreach ($classes as $class) {
            foreach ($class->associationMappings as $assoc) {
                if ($assoc['isOwningSide'] && $assoc['type'] == ClassMetadata::MANY_TO_MANY) {
                    $associationTables[] = $assoc['joinTable']['name'];
                }
            }
        }

        return $associationTables;
    }

    /**
     * Make a snapshot of the current database
     *
     * @return void
     */
    public function snapshotCommand() {
        $connection = $this->entityManager->getConnection();
        $host = $connection->getHost();
        $database = $connection->getDatabase();
        $username = $connection->getUsername();
        $password = $connection->getPassword();

        $commandParts = array(
            'mysqldump',
            '-u' . $username,
            '-h' . $host
        );
        if ($password !== NULL) {
            $commandParts[] = '-p' . $password;
        }

        $commandParts[] = $database;

        $date = date('d.m.Y h-i-s');
        $snapshotFile = FLOW_PATH_DATA . '/Snapshots/' . $date . '.sql';

        Files::createDirectoryRecursively(dirname($snapshotFile));

        $commandParts[] = '> "' . $snapshotFile . '"';
        $command = implode(' ', $commandParts);
        system($command);

        $this->outputLine('Created snapshot "' . $date . '"');
    }

    /**
     * Make a snapshot of the current database
     *
     * @param boolean $latest
     * @return void
     */
    public function restoreSnapshotCommand() {
        $connection = $this->entityManager->getConnection();
        $host = $connection->getHost();
        $database = $connection->getDatabase();
        $username = $connection->getUsername();
        $password = $connection->getPassword();

        $commandParts = array(
            'mysql',
            '-u' . $username,
            '-h' . $host
        );
        if ($password !== NULL) {
            $commandParts[] = '-p' . $password;
        }

        $commandParts[] = $database;

        $snapshotDirectory = FLOW_PATH_DATA . '/Snapshots/';
        $snapshots = Files::readDirectoryRecursively($snapshotDirectory, 'sql');

        rsort($snapshots);
        $choices = array();
        foreach ($snapshots as $snapshot) {
            $choices[] = basename($snapshot);
        }
        $choice = $this->select('Please select a snapshot', $choices);
        $snapshotFile = $snapshots[$choice];

        Files::createDirectoryRecursively(dirname($snapshotFile));

        $commandParts[] = '< "' . $snapshotFile . '"';
        $command = implode(' ', $commandParts);

        $orderedTables = $this->getOrderedTables();
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

        $connection->executeUpdate("SET foreign_key_checks = 0;");
        $orderedTables[] = 'flow_doctrine_migrationstatus';
        $this->outputLine('Dropping existing tables');
        foreach($orderedTables as $table) {
            try {
                $connection->executeUpdate($platform->getDropTableSQL($table, true));
            } catch(\Exception $e) {

            }
        }
        $connection->executeUpdate("SET foreign_key_checks = 1;");


        system($command);
        $this->outputLine('restored snapshot "' . $choices[$choice] . '"');
    }
}

?>