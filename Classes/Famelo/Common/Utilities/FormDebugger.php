<?php
namespace Famelo\Common\Utilities;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Famelo.Common".         *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Form\Core\Model\AbstractSection;

class FormDebugger {

	public static function debugFormDefinition($formDefinition) {
		$pages = $formDefinition->getPages();
		foreach ($pages as $page) {
			echo self::debugFormElement($page);
		}
	}

	public static function debugFormElement($formElement, $depth = 0) {
		$output = '<link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">';
		$output.= '<table class="table table-bordered">';

		$parts = explode('.', $formElement->getIdentifier());
		$name = array_pop($parts);

		$dataType = $formElement->getRootForm()->getProcessingRule($formElement->getIdentifier())->getDataType();

		$output.= '<tr>';
		$output.= '<td><strong>' . $name . '</strong> ' .$formElement->getType() . ' => <small>' . $dataType . '</small></td>';
		$output.= '</tr>';

		$formDefinition = $formElement->getRootForm();

		if ($formElement instanceof AbstractSection) {
			$output.= '<tr><td style="padding-left: 20px;">';
			foreach ($formElement->getElements() as $childElement) {
				$output .= self::debugFormElement($childElement);
			}
			$output.= '</td></tr>';
		}

		$output.= '</table>';

		return $output;
	}
}

?>