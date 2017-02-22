<?php

/**
 * @file
 * Hooks provided by the File Entity module.
 */

/**
 * Control access to listings of files.
 *
 * @param object $query
 *   A query object describing the composite parts of a SQL query related to
 *   listing files.
 *
 * @see hook_query_TAG_alter()
 * @ingroup file_entity_access
 */
function hook_query_file_entity_access_alter(QueryAlterableInterface $query) {
  // Only show files that have been uploaded more than an hour ago.
  $query->condition('timestamp', REQUEST_TIME - 3600, '<=');
}

/**
 * Alter file download headers.
 *
 * @param array $headers
 *   Array of download headers.
 * @param object $file
 *   File object.
 */
function hook_file_download_headers_alter(array &$headers, $file) {
  // Instead of being powered by PHP, tell the world this resource was powered
  // by your custom module!
  $headers['X-Powered-By'] = 'My Module';
}

/**
 * React to a file being downloaded.
 */
function hook_file_transfer($uri, array $headers) {
  // Redirect a download for an S3 file to the actual location.
  if (file_uri_scheme($uri) == 's3') {
    $url = file_create_url($uri);
    drupal_goto($url);
  }
}

/**
 * Decides which file type (bundle) should be assigned to a file entity.
 *
 * @param object $file
 *   File object.
 *
 * @return array
 *   Array of file type machine names that can be assigned to a given file type.
 *   If there are more proposed file types the one, that was returned the first,
 *   wil be chosen. This can be, however, changed in alter hook.
 *
 * @see hook_file_type_alter()
 */
function hook_file_type($file) {
  // Assign all files uploaded by anonymous users to a special file type.
  if (user_is_anonymous()) {
    return array('untrusted_files');
  }
}

/**
 * Alters list of file types that can be assigned to a file.
 *
 * @param array $types
 *   List of proposed types.
 * @param object $file
 *   File object.
 */
function hook_file_type_alter(&$types, $file) {
  // Choose a specific, non-first, file type.
  $types = array($types[4]);
}

/**
 * Provides metadata information.
 *
 * @todo Add documentation.
 *
 * @return array
 *   An array of metadata information.
 */
function hook_file_metadata_info() {

}

/**
 * Alters metadata information.
 *
 * @todo Add documentation.
 *
 * @return array
 *   an array of metadata information.
 */
function hook_file_metadata_info_alter() {

}
