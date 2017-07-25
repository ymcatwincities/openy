<?php

/**
 * @file
 * Generate test disabled elements.
 */

use Drupal\Core\Serialization\Yaml;

/**
 * Generate test elements with #disabled set to TRUE.
 *
 * @return array
 *   An array containing elements with #disabled set to TRUE.
 */
function webform_test_test_element_disabled() {
  // Elements to be ignored.
  $skipped_elements = [
    'hidden',
    'webform_flexbox',
    'webform_test',
    'webform_wizard_page',
  ];

  // Default element properties.
  $yaml = file_get_contents(drupal_get_path('module', 'webform_test') . '/includes/webform_test.example_elements.yml');
  $default_elements = Yaml::decode($yaml);

  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');
  $elements = $element_manager->getInstances();
  foreach ($elements as $element_type => $webform_element) {
    if (in_array($element_type, $skipped_elements)) {
      continue;
    }

    $element = [
      '#type' => $webform_element->getTypeName(),
      '#title' => (string) $webform_element->getPluginLabel(),
    ];

    // Skip elements with the disabled property.
    if (!$webform_element->hasProperty('disabled')) {
      continue;
    }

    // Set default element properties.
    if (isset($default_elements[$element_type])) {
      $element += $default_elements[$element_type];
    }

    // Set default options.
    if ($webform_element->hasProperty('options')) {
      $element['#options'] = [
        'One' => 'One',
        'Two' => 'Two',
        'Three' => 'Three',
      ];
    }

    // Set disabled.
    $element['#disabled'] = TRUE;

    $data[$element_type] = $element;
  }

  return $data;
}
