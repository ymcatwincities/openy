<?php

namespace Drupal\geolocation;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for geolocation geocoder plugins.
 */
interface GeocoderInterface extends PluginInspectionInterface {

  /**
   * Return additional options form.
   *
   * @return array
   *   Options form.
   */
  public function getOptionsForm();

  /**
   * Process the form built above.
   *
   * @param array $form_element
   *   Options form.
   *
   * @return array|null
   *   Settings to store or NULL.
   */
  public function processOptionsForm($form_element);

  /**
   * Attach geocoding logic to input element.
   *
   * @param array $render_array
   *   Form containing the input element.
   * @param string $element_name
   *   Name of the input element.
   *
   * @return array|null
   *   Updated form element or NULL.
   */
  public function formAttachGeocoder(array &$render_array, $element_name);

  /**
   * Process from as altered above.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Input values.
   *
   * @return bool
   *   True of false.
   */
  public function formValidateInput(FormStateInterface $form_state);

  /**
   * Process from as altered above.
   *
   * @param array $input
   *   Input values.
   * @param string $element_name
   *   Name of the input element.
   *
   * @return array|bool
   *   Location data.
   */
  public function formProcessInput(array &$input, $element_name);

  /**
   * Geocode an address.
   *
   * @param string $address
   *   Address to geocode.
   *
   * @return array||null
   *   Location or NULL.
   */
  public function geocode($address);

}
