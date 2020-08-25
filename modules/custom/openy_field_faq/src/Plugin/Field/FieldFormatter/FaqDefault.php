<?php

namespace Drupal\openy_field_faq\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'faq_default' formatter.
 *
 * @FieldFormatter(
 *   id = "faq_default",
 *   label = @Translation("Faq default"),
 *   field_types = {
 *     "faq"
 *   }
 * )
 */
class FaqDefault extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $answer = [
        '#type' => 'processed_text',
        '#text' => $item->answer,
        '#format' => 'full_html',
      ];
      $elements[$delta] = [
        '#theme' => 'openy_faq_item',
        '#question' => $item->question,
        '#answer' => render($answer),
      ];
    }

    return $elements;
  }

}
