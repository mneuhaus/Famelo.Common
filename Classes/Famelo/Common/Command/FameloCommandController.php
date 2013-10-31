<?php
namespace Famelo\Common\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Satisfy".            *
 *                                                                        *
 *                                                                        */

use DavidBadura\Fixtures\FixtureManager\FixtureManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\CommitOrderCalculator;
use Famelo\Common\Command\AbstractInteractiveCommandController;
use Famelo\Satisfy\Domain\Model\User;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Mapping\ClassMetadata;
use TYPO3\Party\Domain\Model\PersonName;

/**
 * satisfy command controller for the Famelo.Satisfy package
 *
 * @Flow\Scope("singleton")
 */
class FameloCommandController extends AbstractInteractiveCommandController {
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
	public function truncateDbCommand($noConfirmation = FALSE) {
		if ($noConfirmation === TRUE || $this->askConfirmation('Are you sure, you want to truncate all existing Tables? [yes/no]' . chr(10))) {
			$connection = $this->entityManager->getConnection();
			$orderedTables = $this->getOrderedTables();
        	$platform = $this->entityManager->getConnection()->getDatabasePlatform();

			$connection->executeUpdate("SET foreign_key_checks = 0;");
			foreach($orderedTables as $table) {
				$this->outputLine('Truncating: ' . $table);
        		$connection->executeUpdate($platform->getTruncateTableSQL($table, true));
        	}
        	$connection->executeUpdate("SET foreign_key_checks = 1;");

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
}

?>