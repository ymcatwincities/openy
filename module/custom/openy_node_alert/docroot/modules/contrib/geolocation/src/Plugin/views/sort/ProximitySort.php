<?php

namespace Drupal\geolocation\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Sort handler for geolocaiton field.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("geolocation_sort_proximity")
 */
class ProximitySort extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    // Add source, lat, lng and filter.
    return [
      'proximity_field' => ['default' => ''],
    ] + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $proximity_fields = [];

    foreach ($this->view->getHandlers('field', $this->view->current_display) as $delta => $field) {
      if ($field['plugin_id'] === 'geolocation_field_proximity') {
        $proximity_fields[$delta] = $field['id'];
      }
    }

    if (empty($proximity_fields)) {
      $form['proximity_field'] = [
        '#markup' => $this->t('There are no proximity fields available in this display.'),
      ];
    }
    else {
      // Add the Filter selector.
      $form['proximity_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Select field.'),
        '#description' => $this->t('Select the field to use for sorting.'),
        '#options' => $proximity_fields,
        '#default_value' => $this->options['proximity_field'],
      ];
    }

    // Add the Drupal\views\Plugin\views\field\Numeric settings to the form.
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!($this->query instanceof Sql)) {
      return;
    }
    // Get the field for sorting.
    $field = isset($this->view->field[$this->options['proximity_field']]) ? $this->view->field[$this->options['proximity_field']] : NULL;
    if (!empty($field->field_alias) && $field->field_alias != 'unknown') {
      $this->query->addOrderBy(NULL, NULL, $this->options['order'], $field->field_alias);
      $this->tableAlias = $field->tableAlias;
    }
  }

}
