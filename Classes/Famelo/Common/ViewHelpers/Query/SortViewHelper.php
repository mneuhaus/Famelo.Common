<?php
namespace Famelo\Common\ViewHelpers\Query;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 */
class SortViewHelper extends AbstractViewHelper {
	
	/**
	 * NOTE: This property has been introduced via code migration to ensure backwards-compatibility.
	 * @see AbstractViewHelper::isOutputEscapingEnabled()
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;
	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 * @Flow\Inject
	 */
	protected $configurationManager;

	/**
	 *
	 * @param mixed $objects
	 * @param string $as
	 * @param string $sortingAs
	 * @return string Rendered string
	 * @author Marc Neuhaus <apocalip@gmail.com>
	 * @api
	 */
	public function render($objects = null, $as = "sortedObjects", $sortingAs = "currentSorting", $defaultProperty = NULL, $defaultDirection = 'ASC') {
		$this->objects = $objects;
		$this->query = $objects->getQuery();

		$this->request = $this->controllerContext->getRequest();

		$property = $defaultProperty;
		$direction = $defaultDirection;

		if( $this->request->hasArgument("sort") ){
			$property = $this->request->getArgument("sort");

			if( $this->request->hasArgument("direction") ){
				$direction = $this->request->getArgument("direction");
			} else {
				$direction = "DESC";
			}
		}

		if ($property !== NULL) {
			$this->query->setOrderings(array(
				$property => $direction
			));
		}

		$sorting = array(
			"property" => $property,
			"direction"=> $direction,
			"oppositeDirection"=> $direction == "ASC" ? "DESC" : "ASC"
		);

		$this->viewHelperVariableContainer->add('Famelo\Common\ViewHelpers\Query\SortViewHelper', 'sorting', $sorting);
		$this->templateVariableContainer->add($sortingAs, $sorting);
		$this->templateVariableContainer->add($as, $this->query->execute());
		$content = $this->renderChildren();
		$this->templateVariableContainer->remove($sortingAs);
		$this->templateVariableContainer->remove($as);
		$this->viewHelperVariableContainer->remove('Famelo\Common\ViewHelpers\Query\SortViewHelper', 'sorting');

		return $content;
	}
}

?>