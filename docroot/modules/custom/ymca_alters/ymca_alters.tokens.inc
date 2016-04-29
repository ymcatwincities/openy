<?php

/**
 * @file
 * Tokens implementation.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Implements hook_token_info().
 */
function ymca_alters_token_info() {
  return [
    'tokens' => [
      'node' => [
        'ymca_summary' => [
          'name' => t('YMCA Summary'),
          'description' => t('The summary of the node\'s content.'),
        ],
      ],
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function ymca_alters_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'ymca_summary':
          $output = '';
          $field = 'field_content';
          $translation = \Drupal::service('entity.manager')->getTranslationFromContext($node, $langcode, array('operation' => 'node_tokens'));

          if ($translation->hasField($field) && ($items = $translation->get($field)) && !$items->isEmpty()) {
            $item = $items[0];
            $output = strip_tags($item->processed);
            $output = trim(preg_replace('/\s+/', ' ', $output));
            $output = Unicode::truncate($output, 150, TRUE);
          }

          $replacements[$original] = $output;
          break;
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_tokens_alter().
 */
function ymca_alters_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
  $tokens = $context['tokens'];
  foreach ($tokens as $token_raw => $token_name) {
    if (!isset($replacements[$token_name])) {
      // Replace empty token with empty string for not letting its name instead.
      $replacements[$token_name] = '';
    }
  }
}
