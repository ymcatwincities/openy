<?php

namespace Drupal\panelizer\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'panelizer' formatter.
 *
 * @FieldFormatter(
 *   id = "panelizer",
 *   label = @Translation("Panelizer"),
 *   field_types = {
 *     "panelizer"
 *   }
 * )
 */
class PanelizerFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'item',
        '#title' => $item->view_mode,
        '#markup' => $this->viewValue($item),
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $description = '';
    if (!empty($item->default)) {
      $description = $this->t('Using default called "@default"', ['@default' => $item->default]);
    }
    else {
      $description = $this->t('Custom');
    }
    return $description;
  }

}
