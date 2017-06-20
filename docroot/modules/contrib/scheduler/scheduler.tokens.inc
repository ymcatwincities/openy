<?php

/**
 * @file
 * Builds placeholder replacement tokens for node scheduler data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function scheduler_token_info() {
  $info['tokens']['node']['scheduler-publish'] = [
    'name' => t('Publish on date'),
    'description' => t("The date the node will be published."),
    'type' => 'date',
  ];
  $info['tokens']['node']['scheduler-unpublish'] = [
    'name' => t('Unpublish on date'),
    'description' => t("The date the node will be unpublished."),
    'type' => 'date',
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function scheduler_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();
  $date_formatter = \Drupal::service('date.formatter');
  $language_code = isset($options['language']) ? $options['language']->language : NULL;
  $replacements = [];

  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'scheduler-publish':
          if (isset($node->publish_on->value)) {
            $replacements[$original] = $date_formatter->format($node->publish_on->value, 'medium', '', NULL, $language_code);
          }
          break;

        case 'scheduler-unpublish':
          if (isset($node->unpublish_on->value)) {
            $replacements[$original] = $date_formatter->format($node->unpublish_on->value, 'medium', '', NULL, $language_code);
          }
          break;
      }
    }

    // Chained token replacement.
    if (isset($node->publish_on->value) && $publish_tokens = $token_service->findWithPrefix($tokens, 'scheduler-publish')) {
      $replacements += $token_service->generate('date', $publish_tokens, ['date' => $node->publish_on->value], $options, $bubbleable_metadata);
    }
    if (isset($node->unpublish_on->value) && $unpublish_tokens = $token_service->findWithPrefix($tokens, 'scheduler-unpublish')) {
      $replacements += $token_service->generate('date', $unpublish_tokens, ['date' => $node->unpublish_on->value], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
