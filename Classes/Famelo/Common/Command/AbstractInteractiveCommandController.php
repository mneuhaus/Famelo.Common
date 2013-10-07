<?php
namespace Famelo\Common\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Common".         *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
abstract class AbstractInteractiveCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * Constructs the controller
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
		$this->dialog = new \Symfony\Component\Console\Helper\DialogHelper();
		$this->progress = new \Symfony\Component\Console\Helper\ProgressHelper();
		$this->table = new \Symfony\Component\Console\Helper\TableHelper();
	}

	/**
	 * Outputs specified text to the console window
	 * You can specify arguments that will be passed to the text via sprintf
	 * @see http://www.php.net/sprintf
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 */
	protected function output($text, array $arguments = array()) {
		if ($arguments !== array()) {
			$text = vsprintf($text, $arguments);
		}
		$this->output->write($text);
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		foreach ($this->arguments as $argument) {
			$argumentName = $argument->getName();

			if ($this->request->hasArgument($argumentName)) {
				$argument->setValue($this->request->getArgument($argumentName));
			} elseif ($argument->isRequired()) {
				$argumentValue = $this->ask('Please specify the argument: ' . $argumentName . chr(10));

				if (empty($argumentValue)) {
					$argumentValue = $this->ask('Please specify the argument: ' . $argumentName . chr(10));
				}

				if (empty($argumentValue)) {
					$exception = new \TYPO3\Flow\Mvc\Exception\CommandException('Required argument "' . $argumentName  . '" is not set.', 1306755520);
					$this->forward('error', 'TYPO3\Flow\Command\HelpCommandController', array('exception' => $exception));
				} else {
					$argument->setValue($argumentValue);
				}
			}
		}
	}

	/**
     * Asks the user to select a value.
     *
     * @param string|array    $question     The question to ask
     * @param array           $choices      List of choices to pick from
     * @param Boolean         $default      The default answer if the user enters nothing
     * @param Boolean|integer $attempts Max number of times to ask before giving up (false by default, which means infinite)
     * @param string          $errorMessage Message which will be shown if invalid value from choice list would be picked
     * @param Boolean         $multiselect  Select more than one value separated by comma
     *
     * @return integer|string|array The selected value or values (the key of the choices array)
     *
     * @throws \InvalidArgumentException
     */
    public function select($question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid', $multiselect = false) {
        return $this->dialog->select($this->output, $question, $choices, $default, $attempts, $errorMessage, $multiselect);
    }

    /**
     * Asks a question to the user.
     *
     * @param string|array    $question     The question to ask
     * @param string          $default      The default answer if none is given by the user
     * @param array           $autocomplete List of values to autocomplete
     *
     * @return string The user answer
     *
     * @throws \RuntimeException If there is no data to read in the input stream
     */
    public function ask($question, $default = NULL, array $autocomplete = array()) {
    	return $this->dialog->ask($this->output, $question, $default, $autocomplete);
    }

    /**
     * Asks a confirmation to the user.
     *
     * The question will be asked until the user answers by nothing, yes, or no.
     *
     * @param string|array    $question The question to ask
     * @param Boolean         $default  The default answer if the user enters nothing
     *
     * @return Boolean true if the user has confirmed, false otherwise
     */
    public function askConfirmation($question, $default = true) {
        return $this->dialog->askConfirmation($this->output, $question, $default);
    }

    /**
     * Asks a question to the user, the response is hidden
     *
     * @param string|array    $question The question
     * @param Boolean         $fallback In case the response can not be hidden, whether to fallback on non-hidden question or not
     *
     * @return string         The answer
     *
     * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
     */
    public function askHiddenResponse($output, $question, $fallback = true) {
		return $this->dialog->askHiddenResponse($this->output, $question, $fallback);
    }

    /**
     * Asks for a value and validates the response.
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @param string|array    $question     The question to ask
     * @param callable        $validator    A PHP callback
     * @param integer         $attempts     Max number of times to ask before giving up (false by default, which means infinite)
     * @param string          $default      The default answer if none is given by the user
     * @param array           $autocomplete List of values to autocomplete
     *
     * @return mixed
     *
     * @throws \Exception When any of the validators return an error
     */
    public function askAndValidate($output, $question, $validator, $attempts = false, $default = null, array $autocomplete = null) {
        return $this->dialog->askAndValidate($this->output, $question, $validator, $attempts, $default, $autocomplete);
    }

    /**
     * Asks for a value, hide and validates the response.
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @param OutputInterface $output    An Output instance
     * @param string|array    $question  The question to ask
     * @param callable        $validator A PHP callback
     * @param integer         $attempts  Max number of times to ask before giving up (false by default, which means infinite)
     * @param Boolean         $fallback  In case the response can not be hidden, whether to fallback on non-hidden question or not
     *
     * @return string         The response
     *
     * @throws \Exception        When any of the validators return an error
     * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
     *
     */
    public function askHiddenResponseAndValidate($question, $validator, $attempts = false, $fallback = true) {
        return $this->dialog->askHiddenResponseAndValidate($this->output, $question, $validator, $attempts, $fallback);
    }

    /**
     * Starts the progress output.
     *
     * @param integer         $max    Maximum steps
     */
    public function progressStart($max = null) {
        $this->progress->start($this->output, $max);
    }

    /**
     * Advances the progress output X steps.
     *
     * @param integer $step   Number of steps to advance
     * @param Boolean $redraw Whether to redraw or not
     *
     * @throws \LogicException
     */
    public function progressAdvance($step = 1, $redraw = false) {
    	$this->progress->advance($step, $redraw);
    }

    /**
     * Sets the current progress.
     *
     * @param integer $current The current progress
     * @param Boolean $redraw  Whether to redraw or not
     *
     * @throws \LogicException
     */
    public function progressSet($current, $redraw = false) {
        $this->progress->setCurrent($current, $redraw);
    }

    /**
     * Finishes the progress output.
     */
    public function progressFinish() {
        $this->progress->finish();
    }

    public function table($rows, $headers = NULL) {
        if ($headers !== NULL) {
        	$this->table->setHeaders($headers);
        }
        $this->table->setRows($rows);
        $this->table->render($this->output);
    }
}

?>