<?php

/**
 * @file
 * Builds placeholder replacement tokens for contact form submission data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function webforms_token_info() {
  $type = array(
    'name' => t('Messages'),
    'description' => t('Tokens related to individual contact form submission items, or "messages".'),
    'needs-data' => 'contact_message',
  );

  // Core tokens for webforms.
  $message['mid'] = array(
    'name' => t("Message ID"),
    'description' => t('The unique ID of the content item, or "message".'),
  );

  // Chained tokens for webforms.
  $message['created'] = array(
    'name' => t("Date submitted"),
    'type' => 'date',
  );

  return array(
    'types' => array('contact_message' => $type),
    'tokens' => array('contact_message' => $message),
  );
}

/**
 * Implements hook_tokens().
 */
function webforms_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $replacements = array();

  if ($type == 'contact_message' && !empty($data['contact_message'])) {
    /** @var \Drupal\contact\MessageInterface $message */
    $message = $data['contact_message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the message.
        case 'mid':
          $replacements[$original] = $message->id();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($message->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $message->getCreatedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
