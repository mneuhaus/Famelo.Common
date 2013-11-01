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

class FlowService implements FixtureServiceInterface{
	/**
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 * @Flow\Inject
	 */
	protected $hashService;

	public function getName() {
		return 'flow';
	}

	public function hash($password, $strategy = 'default') {
		return $this->hashService->hashPassword($password, $strategy);
	}

	public function askPassword($question) {
		$output = new \Symfony\Component\Console\Output\ConsoleOutput();
		$dialog = new \Symfony\Component\Console\Helper\DialogHelper();
		$answer = $dialog->askHiddenResponse($output, $question . chr(10));
		return $this->hash($answer);
	}

	public function ask($question) {
		$output = new \Symfony\Component\Console\Output\ConsoleOutput();
		$dialog = new \Symfony\Component\Console\Helper\DialogHelper();
		return $dialog->ask($output, $question . chr(10));
	}
}

?>