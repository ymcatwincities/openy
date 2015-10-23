<?php
/**
 * @file
 * File administration and module settings UI.
 */

require_once dirname(__FILE__) . '/file_entity.pages.inc';

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\file_entity\Entity\FileType;
use Drupal\user\Entity\User;

/**
 * List file administration filters that can be applied.
 */
function file_filters() {
  $visible_steam_wrappers = \Drupal::service('stream_wrapper_manager')->getWrappers(StreamWrapperInterface::VISIBLE);
  $options = array();
  foreach ($visible_steam_wrappers as $scheme => $information) {
    $options[$scheme] = SafeMarkup::checkPlain($information['name']);
  }
  $filters['uri'] = array(
    'title' => t('scheme'),
    'options' => array(
      '[any]' => t('any'),
    ) + $options,
  );
  $filters['type'] = array(
    'title' => t('type'),
    'options' => array(
      '[any]' => t('any'),
    ) + file_entity_type_get_names(),
  );
  return $filters;
}

/**
 * Apply filters for file administration filters based on session.
 *
 * @param object $query
 *   A SelectQuery to which the filters should be applied.
 */
function file_entity_build_filter_query(SelectQueryInterface $query) {
  // Build query.
  $filter_data = isset($_SESSION['file_entity_overview_filter']) ? $_SESSION['file_entity_overview_filter'] : array();
  foreach ($filter_data as $index => $filter) {
    list($key, $value) = $filter;
    switch ($key) {
      case 'uri':
        $query->condition('fm.' . $key, $value . '%', 'LIKE');
        break;

      case 'type':
        $query->condition('fm.' . $key, $value);
        break;

    }
  }
}

/**
 * Return form for file administration filters.
 */
function file_entity_filter_form() {
  $session = isset($_SESSION['file_entity_overview_filter']) ? $_SESSION['file_entity_overview_filter'] : array();
  $filters = file_filters();

  $i = 0;
  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => t('Show only items where'),
    '#theme' => 'exposed_filters__file_entity',
  );
  foreach ($session as $filter) {
    list($type, $value) = $filter;
    if ($type == 'term') {
      // Load term name from DB rather than search and parse options array.
      $value = module_invoke('taxonomy', 'term_load', $value);
      $value = $value->name;
    }
    else {
      $value = $filters[$type]['options'][$value];
    }
    $t_args = array('%property' => $filters[$type]['title'], '%value' => $value);
    if ($i++) {
      $form['filters']['current'][] = array('#markup' => t('and where %property is %value', $t_args));
    }
    else {
      $form['filters']['current'][] = array('#markup' => t('where %property is %value', $t_args));
    }
    if (in_array($type, array('type', 'uri'))) {
      // Remove the option if it is already being filtered on.
      unset($filters[$type]);
    }
  }

  $form['filters']['status'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('clearfix')),
    '#prefix' => ($i ? '<div class="additional-filters">' . t('and where') . '</div>' : ''),
  );
  $form['filters']['status']['filters'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('filters')),
  );
  foreach ($filters as $key => $filter) {
    $form['filters']['status']['filters'][$key] = array(
      '#type' => 'select',
      '#options' => $filter['options'],
      '#title' => $filter['title'],
      '#default_value' => '[any]',
    );
  }

  $form['filters']['status']['actions'] = array(
    '#type' => 'actions',
    '#attributes' => array('class' => array('container-inline')),
  );
  if (count($filters)) {
    $form['filters']['status']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => count($session) ? t('Refine') : t('Filter'),
    );
  }
  if (count($session)) {
    $form['filters']['status']['actions']['undo'] = array('#type' => 'submit', '#value' => t('Undo'));
    $form['filters']['status']['actions']['reset'] = array('#type' => 'submit', '#value' => t('Reset'));
  }

  drupal_add_js('misc/form.js');

  return $form;
}

/**
 * Process result from file administration filter form.
 */
function file_entity_filter_form_submit($form, &$form_state) {
  $filters = file_filters();
  switch ($form_state['values']['op']) {
    case t('Filter'):
    case t('Refine'):
      // Apply every filter that has a choice selected other than 'any'.
      foreach ($filters as $filter => $options) {
        if (isset($form_state['values'][$filter]) && $form_state['values'][$filter] != '[any]') {
          // Flatten the options array to accommodate hierarchical/nested
          // options.
          $flat_options = form_options_flatten($filters[$filter]['options']);
          // Only accept valid selections offered on the dropdown, block bad
          // input.
          if (isset($flat_options[$form_state['values'][$filter]])) {
            $_SESSION['file_entity_overview_filter'][] = array($filter, $form_state['values'][$filter]);
          }
        }
      }
      break;

    case t('Undo'):
      array_pop($_SESSION['file_entity_overview_filter']);
      break;

    case t('Reset'):
      $_SESSION['file_entity_overview_filter'] = array();
      break;

  }
}

/**
 * Menu callback: file administration.
 */
function file_entity_admin_file($form, $form_state) {
  if (isset($form_state['values']['operation']) && $form_state['values']['operation'] == 'delete') {
    return file_entity_multiple_delete_confirm($form, $form_state, array_filter($form_state['values']['files']));
  }
  $form['filter'] = file_entity_filter_form();
  $form['#submit'][] = 'file_entity_filter_form_submit';
  $form['admin'] = file_entity_admin_files();

  return $form;
}

/**
 * Process file_entity_admin_files form submissions.
 *
 * Execute the chosen 'Update option' on the selected files.
 */
function file_entity_admin_files_submit($form, &$form_state) {
  $operations = module_invoke_all('file_operations');
  $operation = $operations[$form_state['values']['operation']];
  // Filter out unchecked files.
  $files = array_filter($form_state['values']['files']);
  if ($function = $operation['callback']) {
    // Add in callback arguments if present.
    if (isset($operation['callback arguments'])) {
      $args = array_merge(array($files), $operation['callback arguments']);
    }
    else {
      $args = array($files);
    }
    call_user_func_array($function, $args);

    cache_clear_all();
  }
  else {
    // We need to rebuild the form to go to a second step. For example, to
    // show the confirmation form for the deletion of files.
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * File entity delete confirmation.
 */
function file_entity_multiple_delete_confirm($form, &$form_state, $files) {
  $form['files'] = array(
    '#prefix' => '<ul>',
    '#suffix' => '</ul>',
    '#tree' => TRUE,
  );
  // array_filter returns only elements with TRUE values.
  foreach ($files as $fid => $value) {
    $filename = db_query('SELECT filename FROM {file_managed} WHERE fid = :fid', array(':fid' => $fid))->fetchField();
    $form['files'][$fid] = array(
      '#type' => 'hidden',
      '#value' => $fid,
      '#prefix' => '<li>',
      '#suffix' => check_plain($filename) . "</li>\n",
    );
  }
  $form['operation'] = array('#type' => 'hidden', '#value' => 'delete');
  $form['#submit'][] = 'file_entity_multiple_delete_confirm_submit';
  $confirm_question = format_plural(count($files),
                                  'Are you sure you want to delete this item?',
                                  'Are you sure you want to delete these items?');
  return confirm_form($form,
                    $confirm_question,
                    'admin/content/file', t('This action cannot be undone.'),
                    t('Delete'), t('Cancel'));
}

/**
 * Submit handler for delete confirmation.
 */
function file_entity_multiple_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    file_delete_multiple(array_keys($form_state['values']['files']));
    $count = count($form_state['values']['files']);
    watchdog('file_entity', 'Deleted @count files.', array('@count' => $count));
    drupal_set_message(format_plural($count, 'Deleted 1 file.', 'Deleted @count files.'));
  }
  $form_state['redirect'] = 'admin/content/file';
}

/**
 * Form callback; presents file display settings for a given view mode.
 */
function file_entity_file_display_form($form, &$form_state, $file_type, $view_mode) {
  $form['#file_type'] = $file_type->type;
  $form['#view_mode'] = $view_mode;
  $form['#tree'] = TRUE;
  $form['#attached']['js'][] = drupal_get_path('module', 'file_entity') . '/file_entity.admin.js';

  // Retrieve available formatters for this file type and load all configured
  // filters for existing text formats.
  $formatters = file_info_formatter_types();
  foreach ($formatters as $name => $formatter) {
    if (!empty($formatter['hidden'])) {
      unset($formatters[$name]);
    }
    if (isset($formatter['mime types'])) {
      if (file_entity_match_mimetypes($formatter['mime types'], $file_type->mimetypes)) {
        continue;
      }
      unset($formatters[$name]);
    }
  }
  $current_displays = file_displays_load($file_type->type, $view_mode, TRUE);
  foreach ($current_displays as $name => $display) {
    $current_displays[$name] = (array) $display;
  }

  // Formatter status.
  $form['displays']['status'] = array(
    '#type' => 'item',
    '#title' => t('Enabled displays'),
    '#prefix' => '<div id="file-displays-status-wrapper">',
    '#suffix' => '</div>',
  );
  $i = 0;
  foreach ($formatters as $name => $formatter) {
    $form['displays']['status'][$name] = array(
      '#type' => 'checkbox',
      '#title' => check_plain($formatter['label']),
      '#default_value' => !empty($current_displays[$name]['status']),
      '#description' => isset($formatter['description']) ? filter_xss($formatter['description']) : NULL,
      '#parents' => array('displays', $name, 'status'),
      '#weight' => (isset($formatter['weight']) ? $formatter['weight'] : 0) + ($i / 1000),
    );
    $i++;
  }

  // Formatter order (tabledrag).
  $form['displays']['order'] = array(
    '#type' => 'item',
    '#title' => t('Display precedence order'),
    '#theme' => 'file_entity_file_display_order',
  );
  foreach ($formatters as $name => $formatter) {
    $form['displays']['order'][$name]['label'] = array(
      '#markup' => check_plain($formatter['label']),
    );
    $form['displays']['order'][$name]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $formatter['label'])),
      '#title_display' => 'invisible',
      '#delta' => 50,
      '#default_value' => isset($current_displays[$name]['weight']) ? $current_displays[$name]['weight'] : 0,
      '#parents' => array('displays', $name, 'weight'),
    );
    $form['displays']['order'][$name]['#weight'] = $form['displays']['order'][$name]['weight']['#default_value'];
  }

  // Formatter settings.
  $form['display_settings_title'] = array(
    '#type' => 'item',
    '#title' => t('Display settings'),
  );
  $form['display_settings'] = array(
    '#type' => 'vertical_tabs',
  );
  $i = 0;
  foreach ($formatters as $name => $formatter) {
    if (isset($formatter['settings callback']) && ($function = $formatter['settings callback']) && function_exists($function)) {
      $defaults = !empty($formatter['default settings']) ? $formatter['default settings'] : array();
      $settings = !empty($current_displays[$name]['settings']) ? $current_displays[$name]['settings'] : array();
      $settings += $defaults;
      $settings_form = $function($form, $form_state, $settings, $name, $file_type->type, $view_mode);
      if (!empty($settings_form)) {
        $form['displays']['settings'][$name] = array(
          '#type' => 'fieldset',
          '#title' => check_plain($formatter['label']),
          '#parents' => array('displays', $name, 'settings'),
          '#group' => 'display_settings',
          '#weight' => (isset($formatter['weight']) ? $formatter['weight'] : 0) + ($i / 1000),
        ) + $settings_form;
      }
    }
    $i++;
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Process file display settings form submissions.
 */
function file_entity_file_display_form_submit($form, &$form_state) {
  $file_type = $form['#file_type'];
  $view_mode = $form['#view_mode'];
  $displays = isset($form_state['values']['displays']) ? $form_state['values']['displays'] : array();
  $displays_original = file_displays_load($file_type, $view_mode, TRUE);
  foreach ($displays as $formatter_name => $display) {
    $display_original = isset($displays_original[$formatter_name]) ? $displays_original[$formatter_name] : file_display_new($file_type, $view_mode, $formatter_name);
    $display += (array) $display_original;
    file_display_save((object) $display);
  }
  drupal_set_message(t('Your settings have been saved.'));
}

/**
 * Returns HTML for a file display's display order table.
 */
function theme_file_entity_file_display_order($variables) {
  $element = $variables['element'];

  $rows = array();
  foreach (element_children($element, TRUE) as $name) {
    $element[$name]['weight']['#attributes']['class'][] = 'file-display-order-weight';
    $rows[] = array(
      'data' => array(
        drupal_render($element[$name]['label']),
        drupal_render($element[$name]['weight']),
      ),
      'class' => array('draggable'),
    );
  }
  $output = drupal_render_children($element);
  $output .= theme('table', array('rows' => $rows, 'attributes' => array('id' => 'file-displays-order')));
  drupal_add_tabledrag('file-displays-order', 'order', 'sibling', 'file-display-order-weight', NULL, NULL, TRUE);

  return $output;
}
