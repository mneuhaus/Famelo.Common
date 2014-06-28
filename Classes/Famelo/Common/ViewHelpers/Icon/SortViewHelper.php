<?php
namespace Famelo\Common\ViewHelpers\Icon;

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
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 */
class SortViewHelper extends AbstractTagBasedViewHelper {
	/**
	 * @var string
	 */
	protected $tagName = 'span';

	/**
	 * Render the link.
	 *
	 * @return string The rendered link
	 * @throws ViewHelper\Exception
	 * @api
	 */
	public function render() {
		$sorting = $this->viewHelperVariableContainer->get('Famelo\Common\ViewHelpers\Query\SortViewHelper', 'sorting');
		$property = $this->viewHelperVariableContainer->get('Famelo\Common\ViewHelpers\Link\SortViewHelper', 'property');
		if ($property !== $sorting['property']) {
			$this->tag->addAttribute('class', 'glyphicon glyphicon-sort-by-alphabet-alt');
		} else if ($sorting['direction'] == 'ASC') {
			$this->tag->addAttribute('class', 'glyphicon glyphicon-sort-by-alphabet');
		} else if ($sorting['direction'] == 'DESC') {
			$this->tag->addAttribute('class', 'glyphicon glyphicon-sort');
		}
		return $this->tag->render();
	}
}

?>