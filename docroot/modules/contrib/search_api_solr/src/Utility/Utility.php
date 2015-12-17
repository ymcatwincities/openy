<?php

/**
 * @file
 * Contains \Drupal\search_api_solr\Utility\Utility.
 */

namespace Drupal\search_api_solr\Utility;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\ServerInterface;

class Utility {

  /**
   * Retrieves Solr-specific data for available data types.
   *
   * Returns the data type information for both the default Search API data types
   * and custom data types defined by hook_search_api_data_type_info(). Names for
   * default data types are not included, since they are not relevant to the Solr
   * service class.
   *
   * We're adding some extra Solr field information for the default search api
   * data types (as well as on behalf of a couple contrib field types). The
   * extra information we're adding is documented in
   * search_api_solr_hook_search_api_data_type_info(). You can use the same
   * additional keys in hook_search_api_data_type_info() to support custom
   * dynamic fields in your indexes with Solr.
   *
   * @param string|null $type
   *   (optional) A specific type for which the information should be returned.
   *   Defaults to returning all information.
   *
   * @return array|null
   *   If $type was given, information about that type or NULL if it is unknown.
   *   Otherwise, an array of all types. The format in both cases is the same as
   *   for search_api_get_data_type_info().
   *
   * @see search_api_get_data_type_info()
   * @see search_api_solr_hook_search_api_data_type_info()
   */
  public static function getDataTypeInfo($type = NULL) {
    $types = &drupal_static(__FUNCTION__);

    if (!isset($types)) {
      // Grab the stock search_api data types.
      /** @var \Drupal\search_api\DataType\DataTypePluginManager $data_type_service */
      $data_type_service = \Drupal::service('plugin.manager.search_api.data_type');
      $types = $data_type_service->getDefinitions();

      // Add our extras for the default search api fields.
      $types = NestedArray::mergeDeep($types, array(
        'text' => array(
          'prefix' => 't',
        ),
        'string' => array(
          'prefix' => 's',
        ),
        'integer' => array(
          'prefix' => 'i',
        ),
        'decimal' => array(
          'prefix' => 'f',
        ),
        'date' => array(
          'prefix' => 'd',
        ),
        'duration' => array(
          'prefix' => 'i',
        ),
        'boolean' => array(
          'prefix' => 'b',
        ),
        'uri' => array(
          'prefix' => 's',
        ),
        'tokens' => array(
          'prefix' => 't',
        ),
      ));

      // Extra data type info.
      $extra_types_info = array(
        'location' => array(
          'prefix' => 'loc',
        ),
        'geohash' => array(
          'prefix' => 'geo',
        ),
      );

      // For the extra types, only add our extra info if it's already been defined.
      foreach ($extra_types_info as $key => $info) {
        if (array_key_exists($key, $types)) {
          // Merge our extras into the data type info
          $types[$key] += $info;
        }
      }
    }

    // Return the info.
    if (isset($type)) {
      return isset($types[$type]) ? $types[$type] : NULL;
    }
    return $types;
  }

  /**
   * Returns a unique hash for the current site.
   *
   * This is used to identify Solr documents from different sites within a single
   * Solr server.
   *
   * @return string
   *   A unique site hash, containing only alphanumeric characters.
   */
  public static function getSiteHash() {
    // Copied from apachesolr_site_hash().
    if (!($hash = \Drupal::config('search_api_solr.settings')->get('site_hash'))) {
      global $base_url;
      $hash = substr(base_convert(sha1(uniqid($base_url, TRUE)), 16, 36), 0, 6);
      \Drupal::configFactory()->getEditable('search_api_solr.settings')->set('site_hash', $hash)->save();
    }
    return $hash;
  }

  /**
   * Retrieves a list of all config files of a server's Solr backend.
   *
   * @param \Drupal\search_api\ServerInterface $server
   *   The Solr server whose files should be retrieved.
   * @param string $dir_name
   *   (optional) The directory that should be searched for files. Defaults to the
   *   root config directory.
   *
   * @return array
   *   An associative array of all config files in the given directory. The keys
   *   are the file names, values are arrays with information about the file. The
   *   files are returned in alphabetical order and breadth-first.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   If a problem occurred while retrieving the files.
   */
  public static function getServerFiles(ServerInterface $server, $dir_name = NULL) {
    $response = $server->getBackend()->getFile($dir_name);

    // Search for directories and recursively merge directory files.
    $files_data = json_decode($response->getBody(), TRUE);
    $files_list = $files_data['files'];
    $dir_length = strlen($dir_name) + 1;
    $result = array('' => array());

    foreach ($files_list as $file_name => $file_info) {
      // Annoyingly, Solr 4.7 changed the way the admin/file handler returns
      // the file names when listing directory contents: the returned name is now
      // only the base name, not the complete path from the config root directory.
      // We therefore have to check for this case.
      if ($dir_name && substr($file_name, 0, $dir_length) !== "$dir_name/") {
        $file_name = "$dir_name/" . $file_name;
      }
      if (empty($file_info['directory'])) {
        $result[''][$file_name] = $file_info;
      }
      else {
        $result[$file_name] = static::getServerFiles($server, $file_name);
      }
    }

    ksort($result);
    ksort($result['']);
    return array_reduce($result, 'array_merge', array());
  }

  /**
   * Switches a server to use clean identifiers.
   *
   * Used as a submit callback in SearchApiSolrService::configurationForm().
   */
  public static function _search_api_solr_switch_to_clean_ids(array $form, FormStateInterface $form_state) {
    $server = $form_state->get('server');
    $server->options['clean_ids'] = TRUE;
    $server->save();
    drupal_set_message(t('The Solr server was successfully switched to use clean field identifiers.'));

    $count = 0;
    $conditions['server'] = $server->machine_name;
    $conditions['enabled'] = 1;
    /** @var \Drupal\search_api\Entity\Index[] $indexes */
    $indexes = entity_load_multiple_by_properties('search_api_index', $conditions);
    foreach ($indexes as $index) {
      if ($index->getOption('fields')) {
        foreach ($index->getOption('fields') as $key => $field) {
          if (strpos($key, ':') !== FALSE) {
            $index->reindex();
            ++$count;
            break;
          }
        }
      }
    }
    if ($count) {
      $msg = \Drupal::translation()->formatPlural($count, '1 index was scheduled for re-indexing.', '@count indexes were scheduled for re-indexing.');
      drupal_set_message($msg);
    }
  }

  /**
   * Switches a server to multi-site compatibility mode.
   *
   * Used as a submit callback in SearchApiSolrService::configurationForm().
   */
  public static function _search_api_solr_switch_to_site_hash(array $form, FormStateInterface $form_state) {
    $server = $form_state->get('server');

    try {
      $conditions['server'] = $server->machine_name;
      /** @var \Drupal\search_api\Entity\Index[] $indexes */
      $indexes = entity_load_multiple_by_properties('search_api_index', $conditions);
      if ($indexes) {
        foreach ($indexes as $index) {
          $index->reindex();
        }
        $msg = \Drupal::translation()->formatPlural(count($indexes), '1 index was cleared.', '@count indexes were cleared.');
        $server->deleteItems('index_id:(' . implode(' ', array_keys($indexes)) . ')');
        drupal_set_message($msg);
      }
    }
    catch (SearchApiException $e) {
      $variables = array('@server' => $server->name);
      watchdog_exception('search_api_solr', $e, '%type while attempting to enable multi-site compatibility mode for Solr server @server: !message in %function (line %line of %file).', $variables);
      drupal_set_message(t('An error occured while attempting to enable multi-site compatibility mode for Solr server @server. Check the logs for details.', $variables), 'error');
      return;
    }

    $server->options['site_hash'] = TRUE;
    $server->save();
    drupal_set_message(t('The Solr server was successfully switched to multi-site compatibility mode.'));
  }

  /**
   * Escapes a Search API field name for passing to Solr.
   *
   * Since field names can only contain one special character, ":", there is no
   * need to use the complete escape() method.
   *
   * @param string $value
   *   The field name to escape.
   *
   * @return string
   *   An escaped string suitable for passing to Solr.
   */
  public static function escapeFieldName($value) {
    $value = str_replace(':', '\:', $value);
    return $value;
  }

  /**
   * Changes highlighting tags from our custom, HTML-safe ones to HTML.
   *
   * @param string|array $snippet
   *   The snippet(s) to format.
   *
   * @return string|array
   *   The snippet(s), properly formatted as HTML.
   */
  public static function formatHighlighting($snippet) {
    return preg_replace('#\[(/?)HIGHLIGHT\]#', '<$1strong>', $snippet);
  }

  /**
   * Encodes field names to avoid characters that are not supported by solr.
   *
   * Solr doesn't restrict the characters used to build field names. But using
   * non java identifiers within a field name can cause different kind of
   * trouble when running querries. Java identifiers are only consist of
   * letters, digits, '$' and '_'. See
   * https://issues.apache.org/jira/browse/SOLR-3996 and
   * http://docs.oracle.com/cd/E19798-01/821-1841/bnbuk/index.html
   * For full compatibility the '$' has to be avoided, too. And there're more
   * restrictions regarding the field name itself. See
   * https://cwiki.apache.org/confluence/display/solr/Defining+Fields
   * "Field names should consist of alphanumeric or underscore characters only
   * and not start with a digit ... Names with both leading and trailing
   * underscores (e.g. _version_) are reserved." Field names starting with
   * digits or underscores are already avoided by our schema.
   *
   * This function therfore encodes all forbidden characters in their
   * hexadecimal equivalent encapsulted by underscores. Example:
   * "tm_entity:node/body" becomes "tm_5f_entity_3a_node_2f_body"
   *
   * @param string $field_name
   *
   * @return string
   */
  public static function encodeSolrDynamicFieldName($field_name) {
    return preg_replace_callback('/([^\da-zA-Z])/u',
      function ($matches) {
        // Convert non Java identifiers and '$' into byte-wise hexadecimal
        // values encapsulated by '_'.
        return '_' . bin2hex($matches[1]) . '_';
      },
      $field_name);
  }

/**
   * Decodes solr field names.
   *
   * See encodeSolrDynamicFieldName() for details.
   *
   * @param string $field_name
   *
   * @return string
   */
  public static function decodeSolrDynamicFieldName($field_name) {
    return preg_replace_callback('/_([\dabcdef]+?)_/',
      function ($matches) {
        // Convert byte-wise hexadecimal values encapsulated by '_' back into
        // characters.
        return hex2bin($matches[1]);
      },
      $field_name);
  }

}


