<?php

namespace Drupal\custom_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "custom_formatters",
 *   deriver = "Drupal\custom_formatters\Plugin\Derivative\CustomFormatters"
 * )
 */
class CustomFormatters extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\custom_formatters\FormatterInterface $formatter */
    $formatter = \Drupal::entityTypeManager()
      ->getStorage('formatter')
      ->load($this->getPluginDefinition()['formatter']);

    $element = $formatter->getFormatterType()
      ->viewElements($items, $langcode);
    if (!$element) {
      // @TODO - Fail better.
      return [];
    }

    // Transform strings into a renderable element.
    if (is_string($element)) {
      $element = [
        '#markup' => $element,
      ];
    }

    // Ensure we have a nested array.
    if (is_array($element) && !Element::children($element)) {
      $element = [$element];
    }

    foreach (Element::children($element) as $delta) {
      $element[$delta]['#cf_options'] = isset($display['#cf_options']) ? $display['#cf_options'] : [];
      $element[$delta]['#cache']['tags'] = $formatter->getCacheTags();
    }

    // Allow third party integrations a chance to alter the element.
    \Drupal::service('plugin.manager.custom_formatters.formatter_extras')
      ->alter('formatterViewElements', $formatter, $element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    // @TODO
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // @TODO - Re-add form builder functionality once ported.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // @TODO - Re-add form builder functionality once ported.
    return [];
  }

}
