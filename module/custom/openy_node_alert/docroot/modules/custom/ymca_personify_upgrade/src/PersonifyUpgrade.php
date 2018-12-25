<?php

namespace Drupal\ymca_personify_upgrade;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Defines a Personify Upgrade service.
 */
class PersonifyUpgrade {

  /**
   * The base path.
   */
  const BASE_PATH  = 'https://www.ymcamn.org//';

  /**
   * The logger factory.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The config factory.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Creates a new PersonifyUpgrade.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, ConfigFactory $config_factory) {
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * Run Upgrade.
   */
  public function run() {
    $csvFile = file(drupal_get_path('module', 'ymca_personify_upgrade') . '/source/url_translation.csv');
    // Prepare links data from source csv file.
    $data = $this->prepareLinksArray($csvFile);
    // Make links replacement in entities without save.
    $entities = $this->replaceLinks($data);
    // Setup batch process to save entities.
    $operations = [];
    $entities = array_chunk($entities, 10, TRUE);
    foreach ($entities as $row) {
      $operations[] = [
        '\Drupal\ymca_personify_upgrade\PersonifyUpgrade::saveEntities',
        [$row]
      ];
    }
    $batch = array(
      'title' => t('Updating Links...'),
      'operations' => $operations,
      'finished' => '\Drupal\ymca_personify_upgrade\PersonifyUpgrade::finishedCallback',
    );
    batch_set($batch);
  }

  /**
   * Save Entities.
   */
  public static function saveEntities($entities, &$context) {
    $message = 'Updating Links...';
    if (empty($context['results'])) {
      $context['results'] = 0;
    }
    foreach ($entities as $entity) {
      $context['results']++;
      $entity->save();
    }
    $context['message'] = $message;
  }

  /**
   * Helper function to replace links by mapping.
   */
  protected function replaceLinks($data) {
    $config = $this->configFactory->get('ymca_personify_upgrade.settings');
    $fields_data = array_shift($config->get('tables_whitelist'));
    $data_content = $entities_to_save = [];
    $count = 0;
    // Load content.
    foreach ($data['paths'] as $type => $ids) {
      $data_content[$type] = \Drupal::service('entity.manager')->getStorage($type)->loadMultiple($ids);
    }
    foreach ($data_content as $type => $entities) {
      foreach ($entities as $entity) {
        foreach ($fields_data as $field_name => $field_data) {
          $field_name = str_replace($type . '__', '', $field_name);
          if ($entity->hasField($field_name)) {
            $value = $entity->get($field_name)->getValue();
            $columns = [];
            foreach ($field_data['parse_columns'] as $col => $n) {
              $columns[] = str_replace($field_name . '_', '', $col);
            }
            foreach ($columns as $col) {
              foreach ($value as $key => $val) {
                if (isset($val[$col])) {
                  // Replace old links by new ones in each defined field.
                  $content = $val[$col];
                  preg_match_all("/<a href=\".*:\/\/ygtcprod2.personifycloud.com.*<\/a>/", $content, $output_array);
                  if ($output_array[0] == []) {
                    preg_match_all("/^.*:\/\/ygtcprod2.personifycloud.com\S+/", $content, $output_array);
                  }
                  foreach ($output_array as $lid => $link) {
                    preg_match_all('/<a\s+href=["\']([^"\']+)["\']/i', implode(',', $link), $links);
                    if ($links[1] == []) {
                      $list_of_links = $output_array[0];
                    }
                    else {
                      $list_of_links = $links[1];
                    }
                    foreach ($list_of_links as $old_link) {
                      if (isset($data['mapping'][$old_link])) {
                        $new_link = $data['mapping'][$old_link];
                        $content = str_replace($old_link, $new_link, $content);
                        $value[$key][$col] = $content;
                        $used_links[$old_link] = $new_link;
                        $count++;
                      }
                      else {
                        $not_found_links[] = $old_link;
                      }
                    }
                  }
                }
              }
            }
            $entity->set($field_name, $value);
            // Fix published node with workflow, but without value.
            if ($entity->hasField('field_state') && $entity->status->value == 1) {
              if (empty($entity->field_state->value)) {
                $entity->set('field_state', 'workflow_published');
              }
            }
            $entities_to_save[] = $entity;
          }
        }

      }
    }
    drupal_set_message(t('@count links have been successfully updated.', ['@count' => $count]));
    if (isset($used_links)) {
      $unused_links = array_diff($data['mapping'], $used_links);
      $unused_links = implode(' ', $unused_links);
      if (!empty($unused_links)) {
        drupal_set_message(t('Next links were not used for replacing: @links', ['@links' => $unused_links]));
      }
    }
    if (isset($not_found_links)) {
      $not_found_links = implode(' ', $not_found_links);
      drupal_set_message(t('New links were not found for : @links', ['@links' => $not_found_links]));
    }
    return $entities_to_save;
  }

  /**
   * Helper function to retrieve links mapping from csv file.
   */
  protected function prepareLinksArray($csvFile) {
    $paths = $mapping = [];
    foreach ($csvFile as $line) {
      // Read line as comma-separated.
      $line = str_getcsv($line);
      // Explode lines by ; for detecting url pieces.
      $old_line = explode(';', $line[0]);
      $new_line = explode(';', $line[1]);
      // Get internal path.
      $path = str_replace($this::BASE_PATH, '', $old_line[0]);
      // Remove unnecessary url pieces and implode back.
      unset($old_line[0]);
      unset($new_line[0]);
      $old_line = implode(';', $old_line);
      $new_line = implode(';', $new_line);
      // Remove unnecessary quotes.
      $old_line = ltrim($old_line, '"');
      $new_line = ltrim($new_line, '"');
      $old_line = rtrim($old_line, '"');
      $new_line = rtrim($new_line, '"');

      $paths[$path] = $path;
      $mapping[$old_line] = $new_line;
    }
    // Normalize paths for better handling later.
    $paths = $this->normalizePaths($paths);
    // Collect data.
    $data = [
      'paths' => $paths,
      'mapping' => $mapping,
    ];
    return $data;
  }

  /**
   * Helper function to retrieve internal paths.
   */
  protected function normalizePaths($paths) {
    $normalized_paths = [];
    $types = ['block', 'node', 'redirect'];
    foreach ($paths as $path) {
      $parts = explode('/', $path);
      $id = NULL;
      foreach ($parts as $part) {
        if (is_numeric($part)) {
          $id = $part;
        }
      }
      foreach ($parts as $part) {
        if (in_array($part, $types) && !is_null($id)) {
          if ($part == 'block') {
            $part = 'block_content';
          }
          $normalized_paths[$part][] = $id;
        }
      }
    }
    return $normalized_paths;
  }

  /**
   * Finish callback for batch process.
   */
  public function finishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        $results,
        'One post processed.', '@count posts processed.'
      );
      drupal_flush_all_caches();
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
