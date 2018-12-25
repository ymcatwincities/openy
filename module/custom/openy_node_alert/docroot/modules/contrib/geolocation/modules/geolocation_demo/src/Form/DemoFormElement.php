<?php

namespace Drupal\geolocation_demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Returns responses for geolocation_demo module routes.
 */
class DemoFormElement extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geolocation_demo_form_elements';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['geolocation_map_input'] = [
      '#type' => 'geolocation_google_map_input',
      '#title' => $this->t('Simple Location Input'),
      '#latitude' => 40.6700,
      '#longitude' => -73.9400,
      '#height' => 120,
    ];

    $form['geolocation_map_input_code'] = [
      '#type' => 'details',
      '#title' => $this->t('Location Input Code'),
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => '
        $elements[\'geolocation_map_input\'] = [
          \'#type\' => \'geolocation_google_map_input\',
          \'#title\' => $this->t(\'Simple Location Input\'),
          \'#latitude\' => 40.6700,
          \'#longitude\' => -73.9400,
          \'#height\' => 120,
        ];
        ',
      ],
    ];

    $form['geolocation_map_input_complex'] = [
      '#type' => 'geolocation_google_map_input',
      '#title' => $this->t('Complex Location Input'),
      '#height' => 320,
      '#controls' => TRUE,
      '#max_locations' => 2,
      '#locations' => [
        [
          'latitude' => 6.8,
          'longitude' => -1.616667,
        ],
        [
          'latitude' => 6.6,
          'longitude' => -1.616667,
        ],
      ],
    ];

    $form['geolocation_map_input_complex_code'] = [
      '#type' => 'details',
      '#title' => $this->t('Complex Location Input Code'),
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => '
        $form[\'geolocation_map_input_complex\'] = [
          \'#type\' => \'geolocation_google_map_input\',
          \'#title\' => $this->t(\'Complex Location Input\'),
          \'#height\' => 320,
          \'#controls\' => TRUE,
          \'#max_locations\' => 2,
          \'#locations\' => [
            [
              \'latitude\' => 6.8,
              \'longitude\' => -1.616667,
            ],
            [
              \'latitude\' => 6.6,
              \'longitude\' => -1.616667,
            ],
          ],
        ];
        ',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit test form'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
