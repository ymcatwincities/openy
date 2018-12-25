<?php

namespace Drupal\jquery_colorpicker\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Formatter class for jquery_colorpicker Field
 *
 * @FieldFormatter(
 *   id = "jquery_colorpicker_text_display",
 *   label = @Translation("Text"),

 *   field_types = {
 *      "jquery_colorpicker"
 *   }
 * )
 */
class JQueryColorpickerTextDisplayFormatter extends FormatterBase
{
	/**
	 * {@inheritdoc}
	 */
	public function settingsSummary()
	{
		$summary = [];
		$settings = $this->getSettings();
	
		$summary[] = t('Displays textual representation of the color');
	
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
				'#theme' => 'jquery_colorpicker_text_display',
				'#entity_delta' => $delta,
				'#item' => $item,
				'#color' => $item->value,
			];
		}

		return $element;
	}
}
