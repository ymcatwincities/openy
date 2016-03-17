<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\Batch.
 *
 * Helper functions for the Drupal batch API.
 * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
 */

namespace Drupal\simple_sitemap;

use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;


class Batch {
  private $batch;
  private $batch_info;

  const PLUGIN_ERROR_MESSAGE = "The simple_sitemap @plugin plugin has been omitted, as it does not return the required numeric array of path data sets. Each data sets must contain the required path element (relative path string or Drupal\\Core\\Url object) and optionally other elements, like lastmod.";
  const PATH_DOES_NOT_EXIST = "The path @faulty_path has been omitted from the XML sitemap, as it does not exist.";
  const PATH_DOES_NOT_EXIST_OR_NO_ACCESS = "The path @faulty_path has been omitted from the XML sitemap as it either does not exist, or it is not accessible to anonymous users.";
  const ANONYMOUS_USER_ID = 0;


  function __construct($from = 'form') {
    $this->batch = array(
      'title' => t('Generating XML sitemap'),
      'init_message' => t('Initializing batch...'),
      'error_message' => t('An error occurred'),
      'progress_message' => t('Processing @current out of @total link types.'),
      'operations' => array(),
      'finished' => __CLASS__ . '::finish_batch',
    );
    $config = \Drupal::config('simple_sitemap.settings')->get('settings');
    $this->batch_info = array(
      'from' => $from,
      'batch_process_limit' => $config['batch_process_limit'],
      'max_links' => $config['max_links'],
      'remove_duplicates' => $config['remove_duplicates'],
      'anonymous_user_account' => User::load(self::ANONYMOUS_USER_ID),
    );
  }

  /**
   * Starts the batch process depending on where it was requested from.
   */
  public function start() {
    batch_set($this->batch);
    switch ($this->batch_info['from']) {
      case 'form':
        break;
      case 'drush':
        print $this->batch['init_message'] . "\r\n";
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;
        if (function_exists('drush_backend_batch_process'))
          drush_backend_batch_process();
        else
          batch_process();
        break;
      case 'cron':
        $this->batch =& batch_get();
        $this->batch['progressive'] = FALSE;
        batch_process();
        break;
    }
  }

  /**
   * Adds operations to the batch of type 'entity_types' or 'custom_paths'.
   *
   * @param string $type
   * @param array $operations
   */
  public function add_operations($type, $operations) {
    switch ($type) {
      case 'entity_types':
        foreach ($operations as $operation) {
          $this->batch['operations'][] = array(
            __CLASS__ . '::generate_bundle_urls',
            array($operation['query'], $operation['info'], $this->batch_info)
          );
        };
        break;
      case 'custom_paths':
        $this->batch['operations'][] = array(
          __CLASS__ . '::generate_custom_urls',
          array($operations, $this->batch_info)
        );
        break;
    }
  }

  /**
   * Callback function called by the batch API when all operations are finished.
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function finish_batch($success, $results, $operations) {
    if ($success) {
      if (!empty($results) || is_null(db_query('SELECT MAX(id) FROM {simple_sitemap}')->fetchField())) {
        SitemapGenerator::generate_sitemap($results['generate']);
      }
      drupal_set_message(t("The <a href='@url' target='_blank'>XML sitemap</a> has been regenerated for all languages.",
        array('@url' => $GLOBALS['base_url'] . '/sitemap.xml')));
    }
    else {
    }
  }

  /**
   * Batch callback function which generates urls to entity paths.
   *
   * @param object $query
   * @param array $info
   * @param array $batch_info
   * @param array &$context
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  static function generate_bundle_urls($query, $info, $batch_info, &$context) {
    $languages = \Drupal::languageManager()->getLanguages();
    $default_language_id = Simplesitemap::get_default_lang_id();

    // Initializing batch.
    if (empty($context['sandbox'])) {
      self::initialize_batch($query->countQuery()->execute()->fetchField(), $context);
    }

    // Getting id field name from plugin info, if not defined assuming the name of the first field in the query to be the entity id field name.
    $fields = $query->getFields();
    if (isset($info['field_info']['entity_id']) && isset($fields[$info['field_info']['entity_id']])) {
      $id_field = $info['field_info']['entity_id'];
    }
    else {
      //todo: register error
    }

    // Getting the name of the route name field if any.
    if (!empty($info['field_info']['route_name'])) {
      $route_name_field = $info['field_info']['route_name'];
    }

    // Getting the name of the route parameter field if any.
    if (!empty($info['field_info']['route_parameters'])) {
      $route_params_field = $info['field_info']['route_parameters'];
    }

    // Creating a query limited to n=batch_process_limit entries.
    $query
      ->condition($id_field, $context['sandbox']['current_id'], '>')
      ->orderBy($id_field);
    if (!empty($batch_info['batch_process_limit']))
      $query->range(0, $batch_info['batch_process_limit']);
    $result = $query->execute()->fetchAll();

    foreach ($result as $row) {
      self::set_current_id($row->$id_field, $context);

      // Setting route parameters if they exist in the database (menu links).
      if (isset($route_params_field) && !empty($route_parameters = unserialize($row->$route_params_field))) {
        $route_parameters = array(key($route_parameters) => $route_parameters[key($route_parameters)]);
      }
      elseif (!empty($info['path_info']['entity_type'])) {
        $route_parameters = array($info['path_info']['entity_type'] => $row->$id_field);
      }
      else {
        $route_parameters = array();
      }

      // Getting the name of the options field if any.
      if (!empty($info['field_info']['options'])) {
        $options_field = $info['field_info']['options'];
      }

      // Setting options if they exist in the database (menu links)
      $options = isset($options_field) && !empty($options = unserialize($row->$options_field)) ? $options : array();
      $options['absolute'] = TRUE;

      // Setting route name if it exists in the database (menu links)
      if (isset($route_name_field)) {
        $route_name = $row->$route_name_field;
      }
      elseif (isset($info['path_info']['route_name'])) {
        $route_name = $info['path_info']['route_name'];
      }
      else {
        continue;
      }

      $url_object = Url::fromRoute($route_name, $route_parameters, $options);

      if (!$url_object->access($batch_info['anonymous_user_account']))
        continue;

      // Do not include path if it already exists.
      $path = $url_object->getInternalPath();
      if ($batch_info['remove_duplicates'] && self::path_processed($path, $context['results']['processed_paths']))
        continue;

      $urls = array();
      foreach ($languages as $language) {
        if ($language->getId() === $default_language_id) {
          $urls[$default_language_id] = $url_object->toString();
        }
        else {
          $options['language'] = $language;
          $urls[$language->getId()] = Url::fromRoute($route_name, $route_parameters, $options)
            ->toString();
        }
      }
      $context['results']['generate'][] = array(
        'path' => $path,
        'urls' => $urls,
        'options' => $url_object->getOptions(),
        'lastmod' => !empty($info['field_info']['lastmod']) ? date_iso8601($row->{$info['field_info']['lastmod']}) : NULL,
        'priority' => !empty($info['bundle_settings']['priority']) ? $info['bundle_settings']['priority'] : NULL,
      );
    }
    self::set_progress_info($context, $batch_info);
    self::process_segment($context, $batch_info);
  }

 /**
   * Batch function which generates urls to custom paths.
   *
   * @param array $custom_paths
   * @param array $batch_info
   * @param array &$context
   *
   * @see https://api.drupal.org/api/drupal/core!includes!form.inc/group/batch/8
   */
  public static function generate_custom_urls($custom_paths, $batch_info, &$context) {

    $languages = \Drupal::languageManager()->getLanguages();
    $default_language_id = Simplesitemap::get_default_lang_id();

    // Initializing batch.
    if (empty($context['sandbox'])) {
      self::initialize_batch(count($custom_paths), $context);
    }

    foreach($custom_paths as $i => $custom_path) {
      self::set_current_id($i, $context);

      $user_input = $custom_path['path'][0] === '/' ? $custom_path['path'] : '/' . $custom_path['path'];
      if (!\Drupal::service('path.validator')->isValid($custom_path['path'])) { //todo: Change to different function, as this also checks if current user has access. The user however varies depending if process was started from the web interface or via cron/drush.
        self::register_error(self::PATH_DOES_NOT_EXIST_OR_NO_ACCESS, array('@faulty_path' => $custom_path['path']), 'warning');
        continue;
      }
      $options = array('absolute' => TRUE, 'language' => $languages[$default_language_id]);
      $url_object = Url::fromUserInput($user_input, $options);

      if (!$url_object->access($batch_info['anonymous_user_account']))
        continue;

      $path = $url_object->getInternalPath();
      if ($batch_info['remove_duplicates'] && self::path_processed($path, $context['results']['processed_paths']))
        continue;

      $urls = array();
      foreach($languages as $language) {
        if ($language->getId() === $default_language_id) {
          $urls[$default_language_id] = $url_object->toString();
        }
        else {
          $options['language'] = $language;
          $urls[$language->getId()] = Url::fromUserInput($user_input, $options)->toString();
        }
      }

      $context['results']['generate'][] = array(
        'path' => $path,
        'urls' => $urls,
        'options' => $url_object->getOptions(),
        'priority' => !empty($custom_path['priority']) ? $custom_path['priority'] : NULL,
      );
    }
    self::set_progress_info($context, $batch_info);
    self::process_segment($context, $batch_info);
  }

  private static function path_processed($needle, &$path_pool) {
    if (in_array($needle, $path_pool)) {
      return TRUE;
    }
    $path_pool[] = $needle;
    return FALSE;
  }

  private static function initialize_batch($max, &$context) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_id'] = 0;
    $context['sandbox']['max'] = $max;
    $context['results']['generate'] = !empty($context['results']['generate']) ? $context['results']['generate'] : array();
    $context['results']['processed_paths'] = !empty($context['results']['processed_paths']) ? $context['results']['processed_paths'] : array();
  }

  private static function set_current_id($id, &$context) {
    $context['sandbox']['progress']++;
    $context['sandbox']['current_id'] = $id;
  }


  private static function set_progress_info(&$context, $batch_info) {
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      // Providing progress info to the batch API.
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      // Adding processing message after finishing every batch segment.
      end($context['results']['generate']);
      $last_key = key($context['results']['generate']);
      if (!empty($context['results']['generate'][$last_key]['path'])) {
        $context['message'] = t("Processing path @current out of @max: @path", array(
          '@current' => $context['sandbox']['progress'],
          '@max' => $context['sandbox']['max'],
          '@path' => HTML::escape($context['results']['generate'][$last_key]['path']),
        ));
      }
    }
  }

  private static function process_segment(&$context, $batch_info) {
    if (!empty($batch_info['max_links']) && count($context['results']['generate']) >= $batch_info['max_links']) {
      $chunks = array_chunk($context['results']['generate'], $batch_info['max_links']);
      foreach ($chunks as $i => $chunk_links) {
        if (count($chunk_links) == $batch_info['max_links']) {
          SitemapGenerator::generate_sitemap($chunk_links);
          $context['results']['generate'] = array_slice($context['results']['generate'], count($chunk_links));
        }
      }
    }
  }

  /**
   * Logs and displays an error.
   *
   * @param $message
   *  Untranslated message.
   * @param array $substitutions (optional)
   *  Substitutions (placeholder => substitution) which will replace placeholders
   *  with strings.
   * @param string $type (optional)
   *  Message type (status/warning/error).
   */
  private static function register_error($message, $substitutions = array(), $type = 'error') {
    $message = strtr(t($message), $substitutions);
    \Drupal::logger('simple_sitemap')->notice($message);
    drupal_set_message($message, $type);
  }
}
