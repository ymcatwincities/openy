<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'container' class.
 */
abstract class ContainerBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title' => '',
      // General settings.
      'description' => '',
      // Form display.
      'title_display' => '',
      // Form validation.
      'required' => FALSE,
      // Attributes.
      'attributes' => [],
    ] + $this->getDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
    $value = $view_builder->buildElements($element, $webform_submission, $options, $format);

    if (empty($value)) {
      return [];
    }

    // Add #first and #last property to $children.
    // This is used to remove return from #last with multiple lines of
    // text.
    // @see webform-element-base-text.html.twig
    reset($value);
    $first_key = key($value);
    if (isset($value[$first_key]['#options'])) {
      $value[$first_key]['#options']['first'] = TRUE;
    }

    end($value);
    $last_key = key($value);
    if (isset($value[$last_key]['#options'])) {
      $value[$last_key]['#options']['last'] = TRUE;
    }

    return [
      '#theme' => 'webform_container_base_' . $format,
      '#element' => $element,
      '#value' => $value,
      '#webform_submission' => $webform_submission,
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Containers are wrappers, therefore wrapper classes should be used by the
    // container element.
    $form['element_attributes']['attributes']['#classes'] = $this->configFactory->get('webform.settings')->get('element.wrapper_classes');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Containers should never have values and therefore should never have
    // a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
