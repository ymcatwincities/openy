<?php

/**
 * @file
 * Supports file operations including View, Edit, and Delete.
 */

/**
 * Menu callback; view a single file entity.
 */
function file_entity_view_page($file) {
  drupal_set_title($file->filename);

  $uri = entity_uri('file', $file);
  // Set the file path as the canonical URL to prevent duplicate content.
  drupal_add_html_head_link(array('rel' => 'canonical', 'href' => url($uri['path'], $uri['options'])), TRUE);
  // Set the non-aliased path as a default shortlink.
  drupal_add_html_head_link(array('rel' => 'shortlink', 'href' => url($uri['path'], array_merge($uri['options'], array('alias' => TRUE)))), TRUE);

  return file_view($file, 'full');
}

/**
 * Form submission handler for file_entity_delete_form().
 */
function file_entity_delete_form_submit($form, &$form_state) {
  if ($form_state['values']['confirm'] && $file = file_load($form_state['values']['fid'])) {
    // Use file_delete_multiple() rather than file_delete() since we want to
    // avoid unwanted validation and usage checking.
    file_delete_multiple(array($file->fid));

    $args = array(
      '@type' => file_entity_type_get_name($file),
      '%title' => entity_label('file', $file),
    );
    watchdog('file', '@type: deleted %title.', $args);
    drupal_set_message(t('@type %title has been deleted.', $args));
  }

  $form_state['redirect'] = '<front>';

  // Clear the page and block caches.
  cache_clear_all();
}

/**
 * Form constructor for file deletion confirmation form.
 *
 * @param array $files
 *   An array of file objects.
 */
function file_entity_multiple_delete_form($form, &$form_state, array $files) {
  $form['files'] = array(
    '#prefix' => '<ul>',
    '#suffix' => '</ul>',
    '#tree' => TRUE,
  );

  $files_have_usage = FALSE;
  foreach ($files as $fid => $file) {
    $title = entity_label('file', $file);
    $usage = file_usage_list($file);
    if (!empty($usage)) {
      $files_have_usage = TRUE;
      $title = t('@title (in use)', array('@title' => $title));
    }
    else {
      $title = check_plain($title);
    }
    $form['files'][$fid] = array(
      '#type' => 'hidden',
      '#value' => $fid,
      '#prefix' => '<li>',
      '#suffix' => $title . "</li>\n",
    );
  }

  $form['operation'] = array(
    '#type' => 'hidden',
    '#value' => 'delete',
  );

  $description = t('This action cannot be undone.');
  if ($files_have_usage) {
    $description .= ' ' . t('Some of the files are currently in use and may cause problems if deleted.');
  }

  return confirm_form(
    $form,
    format_plural(count($files), 'Are you sure you want to delete this file?', 'Are you sure you want to delete these files?'),
    'admin/content/file',
    $description,
    t('Delete')
  );
}

/**
 * Form submission handler for file_entity_multiple_delete_form().
 */
function file_entity_multiple_delete_form_submit($form, &$form_state) {
  if ($form_state['values']['confirm'] && $fids = array_keys($form_state['values']['files'])) {
    file_delete_multiple($fids);
    $count = count($fids);
    watchdog('file', 'Deleted @count files.', array('@count' => $count));
    drupal_set_message(format_plural($count, 'Deleted one file.', 'Deleted @count files.'));
  }
  $form_state['redirect'] = 'admin/content/file';

  // Clear the page and block caches.
  cache_clear_all();
}

function file_entity_upload_archive_form($form, &$form_state) {
  $options = array(
    'file_extensions' => archiver_get_extensions(),
  );

  $form['upload'] = array(
    '#type' => 'managed_file',
    '#title' => t('Upload an archive file'),
    '#upload_location' => NULL, // Upload to the temporary directory.
    '#upload_validators' => file_entity_get_upload_validators($options),
    '#progress_indicator' => 'bar',
    '#required' => TRUE,
    '#pre_render' => array('file_managed_file_pre_render', 'file_entity_upload_validators_pre_render'),
  );

  $form['pattern'] = array(
    '#type' => 'textfield',
    '#title' => t('Pattern'),
    '#description' => t('Only files matching this pattern will be imported. For example, to import all jpg and gif files, the pattern would be <em>*.jpg|*.gif</em>. Use <em>.*</em> to extract all files in the archive.'),
    '#default_value' => '.*',
    '#required' => TRUE,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  form_load_include($form_state, 'inc', 'file_entity', 'file_entity.pages');

  return $form;
}

/**
 * Upload a file.
 */
function file_entity_upload_archive_form_submit($form, &$form_state) {
  $form_state['files'] = array();

  if ($archive = file_load($form_state['values']['upload'])) {
    if ($archiver = archiver_get_archiver($archive->uri)) {
      $files = $archiver->listContents();

      $extract_dir = file_default_scheme() . '://' . pathinfo($archive->filename, PATHINFO_FILENAME);
      $extract_dir = file_destination($extract_dir, FILE_EXISTS_RENAME);
      if (!file_prepare_directory($extract_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY)) {
        throw new Exception(t('Unable to prepar, e directory %dir for extraction.', array('%dir' => $extract_dir)));
      }

      $archiver->extract($extract_dir);
      $pattern = '/' . $form_state['values']['pattern'] . '/';
      if ($files = file_scan_directory($extract_dir, $pattern)) {
        foreach ($files as $file) {
          $file->status = FILE_STATUS_PERMANENT;
          $file->uid = $archive->uid;
          file_save($file);
          $form_state['files'][$file->fid] = $file;
        }
      }
      drupal_set_message(t('Extracted %file and added @count new files.', array('%file' => $archive->filename, '@count' => count($files))));
    }
    else {
      throw new Exception(t('Cannot extract %file, not a valid archive.', array('%file' => $archive->uri)));
    }
  }

  // Redirect to the file edit page.
  if (file_entity_access('edit') && module_exists('multiform')) {
    $destination = array('destination' => 'admin/content/file');
    if (isset($_GET['destination'])) {
      $destination = drupal_get_destination();
      unset($_GET['destination']);
    }
    $form_state['redirect'] = array('admin/content/file/edit-multiple/' . implode(' ', array_keys($form_state['files'])), array('query' => $destination));
  }
  else {
    $form_state['redirect'] = 'admin/content/file';
  }
}
