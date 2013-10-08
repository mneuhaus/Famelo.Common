Famelo.Common
=============

AbstractInteractiveCommandController
--------------------------------------------------------

This abstract CommandController provides easy access to some console Helper from [Symfony/Console](http://symfony.com/doc/current/components/console/helpers/index.html)

- DialogHelper
    - select($question, $choices, $default = null, $attempts = false, $errorMessage = 'Value "%s" is invalid', $multiselect = false)
    - ask($question, $default = NULL, array $autocomplete = array())
    - askConfirmation($question, $default = true)
    - askHiddenResponse($question, $fallback = true)
    - askAndValidate($question, $validator, $attempts = false, $default = null, array $autocomplete = null)
    - askHiddenResponseAndValidate($question, $validator, $attempts = false, $fallback = true)
- ProgressHelper
    - progressStart($max = NULL)
    - progressSet($current)
    - progressAdvance($step = 1)
    - progressFinish()
- TableHelper
    - table($rows, $headers = NULL)

**Example**

```php
<?php
namespace Famelo\Bonzai\Command;

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class TestCommandController extends Famelo\Common\Command\AbstractInteractiveCommandController {

	/**
	 * An example command
	 *
	 * @param string $requiredArgument This argument is required
	 * @return void
	 */
	public function exampleCommand($requiredArgument) {
		$this->outputLine('You called the example command and passed "%s" as the first argument.', array($requiredArgument));

		$colors = array('red', 'blue', 'yellow');
		$color = $this->select(
			'Please select one color',
			$colors,
			'red'
		);
		$this->outputLine('You choose the color %s.', array($colors[$color]));

		$name = $this->ask('What\'s your name?' . chr(10), 'bob', array('bob', 'sally', 'blake'));
		$this->outputLine('Hello %s.', array($name));

		$response = $this->askConfirmation('Do you like dogs?' . chr(10));

		$this->progressStart(600);
		for ($i=0; $i < 300; $i++) {
			$this->progressAdvance();
			usleep(5000);
		}
		$this->progressFinish();

		$this->table(array(
			array('foo', 'bar', 'coo'),
			array('foo', 'bar', 'coo'),
			array('foo', 'bar', 'coo')
		),
		array('foo', 'bar', 'coo'));
	}

}

?>
```
