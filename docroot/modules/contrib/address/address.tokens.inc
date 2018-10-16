<?php

/**
 * @file
 * Provides Token integration for Address.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Implements hook_token_info().
 */
function address_token_info() {
  $types = [];
  $tokens = [];
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
      continue;
    }
    $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity_type_id);
    if (empty($token_type)) {
      continue;
    }

    // Build country name tokens for all address fields.
    $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
    foreach ($fields as $field_name => $field) {
      if ($field->getType() != 'address') {
        continue;
      }

      $tokens[$token_type . '-' . $field_name]['country_name'] = [
        'name' => t('The country name'),
        'description' => NULL,
        'module' => 'address',
      ];
    }
  }

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function address_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if (!empty($data['field_property'])) {
    foreach ($tokens as $token => $original) {
      $delta = 0;
      $parts = explode(':', $token);
      if (is_numeric($parts[0])) {
        if (count($parts) > 1) {
          $delta = $parts[0];
          $property_name = $parts[1];
        }
        else {
          continue;
        }
      }
      else {
        $property_name = $parts[0];
      }
      if ($property_name != 'country_name') {
        continue;
      }
      if (!isset($data[$data['field_name']][$delta])) {
        continue;
      }

      $field_item = $data[$data['field_name']][$delta];

      $country_name = '';
      if ($country_code = $field_item->country_code) {
        $country_repository = \Drupal::service('address.country_repository');
        $locale = isset($options['langcode']) ? $options['langcode'] : NULL;
        if ($country = $country_repository->get($country_code, $locale)) {
          $country_name = $country->getName();
        }
      }
      $replacements[$original] = $country_name;
    }
  }

  return $replacements;
}
