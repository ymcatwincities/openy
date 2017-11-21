<?php

namespace Drupal\custom_formatters;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface FormatterInterface.
 */
interface FormatterTypeInterface extends PluginInspectionInterface {

  /**
   * Calculates dependencies and stores them in the dependency property.
   *
   * @return array
   *   A keyed array of dependencies.
   */
  public function calculateDependencies();

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode);

  /**
   * Formatter type plugin settings form submit callback.
   *
   * @param array $form
   *   The Form API array.
   * @param FormStateInterface $form_state
   *   The Form state interface.
   */
  public function submitForm(array $form, FormStateInterface $form_state);

}
