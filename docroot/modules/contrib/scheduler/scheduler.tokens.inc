<?php

/**
 * @file
 * Builds placeholder replacement tokens for node scheduler data.
 */

use Drupal\Component\Utility;

/**
 * Implements hook_token_info().
 */
function scheduler_token_info() {
  $info['tokens']['node']['scheduler-publish'] = array(
    'name' => t('Publish on date'),
    'description' => t("The date the node will be published."),
    'type' => 'date',
  );
  $info['tokens']['node']['scheduler-unpublish'] = array(
    'name' => t('Unpublish on date'),
    'description' => t("The date the node will be unpublished."),
    'type' => 'date',
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function scheduler_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $language_code = isset($options['language']) ? $options['language']->language : NULL;
  $replacements = array();

  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'scheduler-publish':
          if (isset($node->publish_on->value)) {
            $replacements[$original] = \Drupal::service('date.formatter')->format($node->publish_on->value, 'medium', '', NULL, $language_code);
          }
          break;
        case 'scheduler-unpublish':
          if (isset($node->unpublish_on->value)) {
            $replacements[$original] = \Drupal::service('date.formatter')->format($node->unpublish_on->value, 'medium', '', NULL, $language_code);
          }
          break;
      }
    }

    // Chained token replacement.
    if (isset($node->publish_on->value) && $publish_tokens = \Drupal::token()->findWithPrefix($tokens, 'scheduler-publish')) {
      $replacements += token_generate('date', $publish_tokens, array('date' => $node->publish_on->value), $options);
    }
    if (isset($node->unpublish_on->value) && $unpublish_tokens = \Drupal::token()->findWithPrefix($tokens, 'scheduler-unpublish')) {
      $replacements += token_generate('date', $unpublish_tokens, array('date' => $node->unpublish_on->value), $options);
    }
  }

  return $replacements;
}
