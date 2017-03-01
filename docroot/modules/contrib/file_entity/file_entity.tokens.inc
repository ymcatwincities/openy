<?php

/**
 * @file
 * Token integration for the file_entity module.
 */
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\file_entity\Entity\FileType;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function file_entity_token_info() {
  // File type tokens.
  $info['types']['file-type'] = array(
    'name' => t('File type'),
    'description' => t('Tokens associated with file types.'),
    'needs-data' => 'file_type',
  );
  $info['tokens']['file-type']['name'] = array(
    'name' => t('Name'),
    'description' => t('The name of the file type.'),
  );
  $info['tokens']['file-type']['machine-name'] = array(
    'name' => t('Machine-readable name'),
    'description' => t('The unique machine-readable name of the file type.'),
  );
  $info['tokens']['file-type']['count'] = array(
    'name' => t('File count'),
    'description' => t('The number of files belonging to the file type.'),
  );
  $info['tokens']['file-type']['edit-url'] = array(
    'name' => t('Edit URL'),
    'description' => t("The URL of the file type's edit page."),
  );

  // File tokens.
  $info['tokens']['file']['type'] = array(
    'name' => t('File type'),
    'description' => t('The file type of the file.'),
    'type' => 'file-type',
  );
  $info['tokens']['file']['download-url'] = array(
    'name' => t('Download URL'),
    'description' => t('The URL to download the file directly.'),
    'type' => 'url',
  );

  return $info;
}

/**
 * Implements hook_token_info_alter().
 */
function file_entity_token_info_alter(&$info) {
  $info['tokens']['file']['name']['description'] = t('The name of the file.');
}

/**
 * Implements hook_tokens().
 */
function file_entity_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $langcode = $options['langcode'];
    $url_options['language'] = \Drupal::languageManager()->getLanguage($langcode);
  }
  else {
    $langcode = NULL;
  }

  $sanitize = !empty($options['sanitize']);

  // File tokens.
  if ($type == 'file' && !empty($data['file'])) {
    $file = $data['file'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'type':
          if ($file_type = FileType::load($file->bundle())) {
            $bubbleable_metadata->addCacheableDependency($file_type);
            $replacements[$original] = $sanitize ? SafeMarkup::checkPlain($file_type->label()) : $file_type->label();
          }
          break;

        case 'download-url':
          $replacements[$original] = $file->downloadUrl($url_options)->toString();
          break;
      }
    }

    // Chained token relationships.
    $token_service = \Drupal::service('token');
    if (($file_type_tokens = $token_service->findWithPrefix($tokens, 'type')) && $file_type = FileType::load($file->bundle())) {
      $replacements += $token_service->generate('file-type', $file_type_tokens, array('file_type' => $file_type), $options, $bubbleable_metadata);
    }
    if ($download_url_tokens = $token_service->findWithPrefix($tokens, 'download-url')) {
      $replacements += $token_service->generate('url', $download_url_tokens, $file->downloadUrl()->toString(), $options, $bubbleable_metadata);
    }
  }

  // File type tokens.
  if ($type == 'file-type' && !empty($data['file_type'])) {
    $file_type = $data['file_type'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $replacements[$original] = $sanitize ? SafeMarkup::checkPlain($file_type->label()) : $file_type->label();
          break;

        case 'machine-name':
          // This is a machine name so does not ever need to be sanitized.
          $replacements[$original] = $file_type->id();
          break;

        case 'count':
          $query = db_select('file_managed');
          $query->condition('type', $file_type->id());
          $query->addTag('file_type_file_count');
          $count = $query->countQuery()->execute()->fetchField();
          $replacements[$original] = (int) $count;
          break;

        case 'edit-url':
          $replacements[$original] = Url::fromUri('admin/structure/file-types/manage/' . $file_type->type . '/fields', $url_options)->toString();
          break;
      }
    }
  }

  return $replacements;
}
