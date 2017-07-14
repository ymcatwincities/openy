<?php

namespace Drupal\openy_svg_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'openy_svg_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "openy_svg_formatter",
 *   label = @Translation("OpenY SVG formatter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class OpenYSvgFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ($item->entity) {
        $uri = $item->entity->getFileUri();
        $svg = file_get_contents($uri);
        $elements[$delta] = [
          '#type' => 'inline_template',
          '#template' => $svg,
          '#context' => [],
        ];
      }
    }

    return $elements;
  }

}
