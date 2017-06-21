<?php

namespace Drupal\geolocation;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GeocoderBase.
 *
 * @package Drupal\geolocation
 */
abstract class GeocoderBase extends PluginBase implements GeocoderInterface {

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => t("No settings available."),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processOptionsForm($form_element) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function formAttachGeocoder(array &$render_array, $element_name) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function formValidateInput(FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function formProcessInput(array &$input, $element_name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address) {
    return NULL;
  }

}
