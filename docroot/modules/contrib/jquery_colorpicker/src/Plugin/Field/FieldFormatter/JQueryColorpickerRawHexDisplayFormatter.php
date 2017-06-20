<?php

namespace Drupal\jquery_colorpicker\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Formatter class for jquery_colorpicker Field
 *
 * @FieldFormatter(
 *   id = "jquery_colorpicker_raw_hex_display",
 *   label = @Translation("Raw Hexidecimal"),

 *   field_types = {
 *      "jquery_colorpicker"
 *   }
 * )
 */
class JQueryColorpickerRawHexDisplayFormatter extends FormatterBase
{
	/**
	 * {@inheritdoc}
	 */
	public function settingsSummary()
	{
		$summary = [];
		$settings = $this->getSettings();
	
		$summary[] = t('Displays a hexidecimal representation of the color, with no HTML wrappers nor the # prefix');
	
		return $summary;
	}

	/**
	 * {@inheritdoc}
	 */
	public function viewElements(FieldItemListInterface $items, $langcode)
	{
		$element = [];
		foreach($items as $delta => $item)
		{
			$element[$delta] = [
				'#markup' => $item->value,
			];
		}

		return $element;
	}
}
