<?php

/**
 * @file
 * API extensions of Drupal core's file.inc.
 */
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;
use Drupal\file_entity\FileTypeInterface;
use Drupal\file\Entity\File;

/**
 * The {file_managed}.type value when the file type has not yet been determined.
 */
define('FILE_TYPE_NONE', 'undefined');

/**
 * Returns information about file formatters from hook_file_formatter_info().
 *
 * @param string $formatter_type
 *   (optional) A file formatter type name. If ommitted, all file formatter
 *   will be returned.
 *
 * @return string|array
 *   Either a file formatter description, as provided by
 *   hook_file_formatter_info(), or an array of all existing file formatters,
 *   keyed by formatter type name.
 */
function file_info_formatter_types($formatter_type = NULL) {
  $info = &drupal_static(__FUNCTION__);
  if (!isset($info)) {
    $info = module_invoke_all('file_formatter_info');
    drupal_alter('file_formatter_info', $info);
    uasort($info, '_file_entity_sort_weight_label');
  }
  if ($formatter_type) {
    if (isset($info[$formatter_type])) {
      return $info[$formatter_type];
    }
  }
  else {
    return $info;
  }
}

/**
 * Clears caches that are related to file entity.
 *
 * Clears all cached configuration related to file types, formatters, and
 * display settings.
 */
function file_info_cache_clear() {
  // Clear the formatter type cache, managed by file_info_formatter_types().
  drupal_static_reset('file_info_formatter_types');

  // Clear file type caches.
  drupal_static_reset('file_type_get_names');
}

/**
 * Generate an array for rendering just the file portion of a file entity.
 *
 * @param object $file
 *   A file object.
 * @param string|array $displays
 *   Can be either:
 *   - the name of a view mode;
 *   - or an array of custom display settings, as returned by file_displays().
 * @param string $langcode
 *   (optional) A language code to use for rendering. Defaults to the global
 *   content language of the current request.
 *
 * @return array
 *   An array as expected by drupal_render().
 */
function file_view_file($file, $displays = 'full', $langcode = NULL) {
  if (!isset($langcode)) {
    $langcode = $GLOBALS['language_content']->language;
  }

  // Prepare incoming display specifications.
  if (is_string($displays)) {
    $view_mode = $displays;
    $displays = file_displays($file->type, $view_mode);
  }
  else {
    $view_mode = '_custom_display';
  }
  drupal_alter('file_displays', $displays, $file, $view_mode);
  _file_sort_array_by_weight($displays);

  // Attempt to display the file with each of the possible displays. Stop after
  // the first successful one. See file_displays() for details.
  $element = NULL;
  foreach ($displays as $formatter_type => $display) {
    if (!empty($display['status'])) {
      $formatter_info = file_info_formatter_types($formatter_type);
      // Under normal circumstances, the UI prevents enabling formatters for
      // incompatible MIME types. In case this was somehow circumvented (for
      // example, a module updated its formatter definition without updating
      // existing display settings), perform an extra check here.
      if (isset($formatter_info['mime types'])) {
        if (!file_entity_match_mimetypes($formatter_info['mime types'], $file->filemime)) {
          continue;
        }
      }
      if (isset($formatter_info['view callback']) && ($function = $formatter_info['view callback']) && function_exists($function)) {
        $display['type'] = $formatter_type;
        if (!empty($formatter_info['default settings'])) {
          if (empty($display['settings'])) {
            $display['settings'] = array();
          }
          $display['settings'] += $formatter_info['default settings'];
        }
        $element = $function($file, $display, $langcode);
        if (isset($element)) {
          break;
        }
      }
    }
  }

  // As a last resort, fall back to showing a link to the file.
  if (!isset($element)) {
    $element = array(
      '#theme' => 'file_link',
      '#file' => $file,
    );
  }

  // Add defaults and return the element.
  $element += array(
    '#file' => $file,
    '#view_mode' => $view_mode,
    '#language' => $langcode,
  );

  return $element;
}

/**
 * @defgroup file_displays File displays API
 * @{
 * Functions to load and save information about file displays.
 */

/**
 * Returns an array of displays to use for a file type in a given view mode.
 *
 * It is common for a site to be configured with broadly defined file types
 * (e.g., 'video'), and to have different files of this type require different
 * displays (for example, the code required to display a YouTube video is
 * different than the code required to display a local QuickTime video).
 * Therefore, the site administrator can configure multiple displays for a given
 * file type. This function returns all of the displays that the administrator
 * enabled for the given file type in the given view mode. file_view_file() then
 * invokes each of these, and passes the specific file to display. Each display
 * implementation can inspect the file, and either return a render array (if it
 * is capable of displaying the file), or return nothing (if it is incapable of
 * displaying the file). The first render array returned is the one used.
 *
 * @param string $file_type
 *   The type of file.
 * @param string $view_mode
 *   The view mode.
 *
 * @return array
 *   An array keyed by the formatter type name. Each item in the array contains
 *   the following key/value pairs:
 *   - status: Whether this display is enabled. If not TRUE, file_view_file()
 *     skips over it.
 *   - weight: An integer that determines the order of precedence within the
 *     returned array. The lowest weight display capable of displaying the file
 *     is used.
 *   - settings: An array of key/value pairs specific to the formatter type. See
 *     hook_file_formatter_info() for details.
 *
 * @see hook_file_formatter_info()
 * @see file_view_file()
 */
function file_displays($file_type, $view_mode) {
  $cache = &drupal_static(__FUNCTION__, array());

  // If the requested view mode isn't configured to use a custom display for its
  // fields, then don't use a custom display for its file either.
  if ($view_mode != 'default') {
    $view_mode_settings = field_view_mode_settings('file', $file_type);
    $view_mode = !empty($view_mode_settings[$view_mode]['custom_settings']) ? $view_mode : 'default';
  }

  if (!isset($cache[$file_type][$view_mode])) {
    // Load the display configurations for the file type and view mode. If none
    // exist for the view mode, use the default view mode.
    $displays = file_displays_load($file_type, $view_mode, TRUE);
    if (empty($displays) && $view_mode != 'default') {
      $cache[$file_type][$view_mode] = file_displays($file_type, 'default');
    }
    else {
      // Convert the display objects to arrays and remove unnecessary keys.
      foreach ($displays as $formatter_name => $display) {
        $displays[$formatter_name] = array_intersect_key((array) $display, drupal_map_assoc(array('status', 'weight', 'settings')));
      }
      $cache[$file_type][$view_mode] = $displays;
    }
  }

  return $cache[$file_type][$view_mode];
}

/**
 * Returns an array of {file_display} objects for the file type and view mode.
 */
function file_displays_load($file_type, $view_mode, $key_by_formatter_name = FALSE) {
  ctools_include('export');

  $display_names = array();
  $prefix = $file_type . '__' . $view_mode . '__';
  foreach (array_keys(file_info_formatter_types()) as $formatter_name) {
    $display_names[] = $prefix . $formatter_name;
  }
  $displays = ctools_export_load_object('file_display', 'names', $display_names);

  if ($key_by_formatter_name) {
    $prefix_length = strlen($prefix);
    $rekeyed_displays = array();
    foreach ($displays as $name => $display) {
      $rekeyed_displays[substr($name, $prefix_length)] = $display;
    }
    $displays = $rekeyed_displays;
  }

  return $displays;
}

/**
 * Saves a {file_display} object to the database.
 */
function file_display_save($display) {
  ctools_include('export');
  ctools_export_crud_save('file_display', $display);
  file_info_cache_clear();
}

/**
 * Creates a new {file_display} object.
 */
function file_display_new($file_type, $view_mode, $formatter_name) {
  ctools_include('export');
  $display = ctools_export_crud_new('file_display');
  file_info_cache_clear();
  $display->name = implode('__', array($file_type, $view_mode, $formatter_name));
  return $display;
}

/**
 * @} End of "defgroup file_displays".
 */

/**
 * Determines file type for a given file.
 *
 * @param \Drupal\file\FileInterface $file
 *   File object.
 *
 * @return string
 *   Machine name of file type that should be used for given file.
 */
function file_get_type(FileInterface $file) {
  $types = \Drupal::moduleHandler()->invokeAll('file_type', array($file));
  \Drupal::moduleHandler()->alter('file_type', $types, $file);

  return empty($types) ? NULL : reset($types);
}

/**
 * Sorts an array by weight.
 *
 * Helper function to sort an array by the value of each item's 'weight' key,
 * while preserving relative order of items that have equal weight.
 */
function _file_sort_array_by_weight(&$a) {
  $i = 0;
  foreach ($a as $key => $item) {
    if (!isset($a[$key]['weight'])) {
      $a[$key]['weight'] = 0;
    }
    $original_weight[$key] = $a[$key]['weight'];
    $a[$key]['weight'] += $i / 1000;
    $i++;
  }
  uasort($a, 'drupal_sort_weight');
  foreach ($a as $key => $item) {
    $a[$key]['weight'] = $original_weight[$key];
  }
}

/**
 * User sort function to sort by weight, then label/name.
 */
function _file_entity_sort_weight_label($a, $b) {
  $a_weight = isset($a['weight']) ? $a['weight'] : 0;
  $b_weight = isset($b['weight']) ? $b['weight'] : 0;
  if ($a_weight == $b_weight) {
    $a_label = isset($a['label']) ? $a['label'] : '';
    $b_label = isset($b['label']) ? $b['label'] : '';
    return strcasecmp($a_label, $b_label);
  }
  else {
    return $a_weight < $b_weight ? -1 : 1;
  }
}

/**
 * Returns a file object which can be passed to file_save().
 *
 * @param string $uri
 *   A string containing the URI, path, or filename.
 * @param bool $use_existing
 *   (Optional) If TRUE and there's an existing file in the {file_managed}
 *   table with the passed in URI, then that file object is returned.
 *   Otherwise, a new file object is returned. Default is TRUE.
 *
 * @return FileInterface|bool
 *   A file object, or FALSE on error.
 *
 * @todo This should probably be named
 *   file_load_by_uri($uri, $create_if_not_exists).
 * @todo Remove this function when http://drupal.org/node/685818 is fixed.
 */
function file_uri_to_object($uri, $use_existing = TRUE) {
  $file = FALSE;
  $uri = file_stream_wrapper_uri_normalize($uri);

  if ($use_existing) {
    // We should always attempt to re-use a file if possible.
    $files = entity_load_multiple_by_properties('file', array('uri' => $uri));
    $file = !empty($files) ? reset($files) : FALSE;
  }

  if (empty($file)) {
    $file = File::create(array(
      'uid' => \Drupal::currentUser()->id(),
      'filename' => drupal_basename($uri),
      'uri' => $uri,
      'filemime' => \Drupal::service('file.mime_type.guesser')->guess($uri),
      'filesize' => @filesize($uri),
      'created' => REQUEST_TIME,
      'status' => FILE_STATUS_PERMANENT,
    ));
  }

  return $file;
}
