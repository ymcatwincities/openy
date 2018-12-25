<?php

namespace Drupal\geolocation\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Field;

/**
 * Field handler for geolocaiton field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("geolocation_field")
 */
class GeolocationField extends Field {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remote the click sort field selector.
    unset($form['click_sort_column']);
  }

}
