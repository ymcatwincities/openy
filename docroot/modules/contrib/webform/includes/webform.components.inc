<?php

/**
 * @file
 * Webform module component handling.
 */

/**
 * Provides interface and database handling for editing components of a webform.
 *
 * @author Nathan Haug <nate@lullabot.com>
 */

/**
 * Form to configure a webform component.
 */
function webform_component_edit_form($form, $form_state, $node, $component, $clone = FALSE) {
  drupal_set_title(t('Edit component: @name', array('@name' => $component['name'])), PASS_THROUGH);

  $form['#node'] = $node;
  $form['#tree'] = TRUE;

  // Print the correct field type specification.
  // We always need: name and description.
  $form['type'] = array(
    '#type' => 'value',
    '#value' => $component['type'],
  );
  $form['nid'] = array(
    '#type' => 'value',
    '#value' => $node->id(),
  );
  $form['cid'] = array(
    '#type' => 'value',
    '#value' => isset($component['cid']) ? $component['cid'] : NULL,
  );
  $form['clone'] = array(
    '#type' => 'value',
    '#value' => $clone,
  );

  if (webform_component_feature($component['type'], 'title')) {
    $form['name'] = array(
      '#type' => 'textfield',
      '#default_value' => $component['name'],
      '#title' => t('Label'),
      '#description' => t('This is used as a descriptive label when displaying this form element.'),
      '#required' => TRUE,
      '#weight' => -10,
      '#maxlength' => NULL,
    );
  }

  $form['form_key'] = array(
    '#type' => 'textfield',
    '#default_value' => empty($component['form_key']) ? _webform_safe_name($component['name']) : $component['form_key'],
    '#title' => t('Field Key'),
    '#description' => t('Enter a machine readable key for this form element. May contain only alphanumeric characters and underscores. This key will be used as the name attribute of the form element. This value has no effect on the way data is saved, but may be helpful if doing custom form processing.'),
    '#required' => TRUE,
    '#weight' => -9,
  );

  $form['extra'] = array();
  if (webform_component_feature($component['type'], 'description')) {
    $form['extra']['description'] = array(
      '#type' => 'textarea',
      '#default_value' => isset($component['extra']['description']) ? $component['extra']['description'] : '',
      '#title' => t('Description'),
      '#description' => t('A short description of the field used as help for the user when he/she uses the form.') . ' ' . theme('webform_token_help'),
      '#weight' => -1,
    );
  }

  // Display settings.
  $form['display'] = array(
    '#type' => 'fieldset',
    '#title' => t('Display'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => 8,
  );
  if (webform_component_feature($component['type'], 'title_display')) {
    $inline_option = webform_component_feature($component['type'], 'title_inline') ? array('inline' => t('Inline')) : array();
    $internal_option = webform_component_feature($component['type'], 'title_internal') ? array('internal' => t('Inside the component')) : array();
    if ($inline_option || $internal_option) {
      $form['display']['title_display'] = array(
        '#type' => 'select',
        '#title' => t('Label display'),
        '#default_value' => !empty($component['extra']['title_display']) ? $component['extra']['title_display'] : 'before',
        '#options' => array('before' => t('Above')) +
                      $inline_option +
                      $internal_option +
                      array('none' => t('None')),
        '#description' => t('Determines the placement of the component\'s label.'),
      );
    }
    else {
      $form['display']['title_display'] = array(
        '#type' => 'checkbox',
        '#title' => t('Hide label'),
        '#default_value' => strcmp($component['extra']['title_display'], 'none') === 0,
        '#return_value' => 'none',
        '#description' => t('Do not display the label of this component.'),
      );
    }
    $form['display']['title_display']['#weight'] = 8;
    $form['display']['title_display']['#parents'] = array('extra', 'title_display');
  }

  if (webform_component_feature($component['type'], 'private')) {
    // May user mark fields as Private?
    $form['display']['private'] = array(
      '#type' => 'checkbox',
      '#title' => t('Private'),
      '#default_value' => ($component['extra']['private'] == '1' ? TRUE : FALSE),
      '#description' => t('Private fields are shown only to users with results access.'),
      '#weight' => 45,
      '#parents' => array('extra', 'private'),
      '#disabled' => empty($node->id()) ? FALSE : !webform_results_access($node),
    );
  }

  if (webform_component_feature($component['type'], 'wrapper_classes')) {
    $form['display']['wrapper_classes'] = array(
      '#type' => 'textfield',
      '#title' => t('Wrapper CSS classes'),
      '#default_value' => isset($component['extra']['wrapper_classes']) ? $component['extra']['wrapper_classes'] : '',
      '#description' => t('Apply a class to the wrapper around both the field and its label. Separate multiple by spaces.'),
      '#weight' => 50,
      '#parents' => array('extra', 'wrapper_classes'),
    );
  }
  if (webform_component_feature($component['type'], 'css_classes')) {
    $form['display']['css_classes'] = array(
      '#type' => 'textfield',
      '#title' => t('CSS classes'),
      '#default_value' => isset($component['extra']['css_classes']) ? $component['extra']['css_classes'] : '',
      '#description' => t('Apply a class to the field. Separate multiple by spaces.'),
      '#weight' => 51,
      '#parents' => array('extra', 'css_classes'),
    );
  }

  // Validation settings.
  $form['validation'] = array(
    '#type' => 'fieldset',
    '#title' => t('Validation'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => 5,
  );
  if (webform_component_feature($component['type'], 'required')) {
    $form['validation']['required'] = array(
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#default_value' => ($component['required'] == '1' ? TRUE : FALSE),
      '#description' => t('Check this option if the user must enter a value.'),
      '#weight' => -1,
      '#parents' => array('required'),
    );
  }

  // Position settings, only shown if JavaScript is disabled.
  $form['position'] = array(
    '#type' => 'fieldset',
    '#title' => t('Position'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#tree' => FALSE,
    '#weight' => 20,
    '#attributes' => array('class' => array('webform-position')),
  );

  $options = array('0' => t('Root'));
  foreach ($node->webform['components'] as $existing_cid => $value) {
    if (webform_component_feature($value['type'], 'group') && (!isset($component['cid']) || $existing_cid != $component['cid'])) {
      $options[$existing_cid] = $value['name'];
    }
  }
  $form['position']['pid'] = array(
    '#type' => 'select',
    '#title' => t('Parent'),
    '#default_value' => $component['pid'],
    '#description' => t('Optional. You may organize your form by placing this component inside another fieldset.'),
    '#options' => $options,
    '#access' => count($options) > 1,
    '#weight' => 3,
  );
  $form['position']['weight'] = array(
    '#type' => 'textfield',
    '#size' => 4,
    '#title' => t('Weight'),
    '#default_value' => $component['weight'],
    '#description' => t('Optional. In the menu, the heavier items will sink and the lighter items will be positioned nearer the top.'),
    '#weight' => 4,
  );

  // Add the fields specific to this component type:
  $additional_form_elements = (array) webform_component_invoke($component['type'], 'edit', $component);
  if (empty($additional_form_elements)) {
    drupal_set_message(t('The webform component of type @type does not have an edit function defined.', array('@type' => $component['type'])));
  }

  // Merge the additional fields with the current fields:
  if (isset($additional_form_elements['extra'])) {
    $form['extra'] = array_merge($form['extra'], $additional_form_elements['extra']);
    unset($additional_form_elements['extra']);
  }
  if (isset($additional_form_elements['position'])) {
    $form['position'] = array_merge($form['position'], $additional_form_elements['position']);
    unset($additional_form_elements['position']);
  }
  if (isset($additional_form_elements['display'])) {
    $form['display'] = array_merge($form['display'], $additional_form_elements['display']);
    unset($additional_form_elements['display']);
  }
  if (isset($additional_form_elements['validation'])) {
    $form['validation'] = array_merge($form['validation'], $additional_form_elements['validation']);
    unset($additional_form_elements['validation']);
  }
  elseif (count(element_children($form['validation'])) == 0) {
    unset($form['validation']);
  }
  $form = array_merge($form, $additional_form_elements);
  // Ensure that the webform admin library is attached, possibly in addition to
  // component-specific attachments.
  $form['#attached']['library'][] = array('webform', 'admin');

  // Add the submit button.
  $form['actions'] = array(
    '#type' => 'actions',
    '#weight' => 50,
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save component'),
  );

  // Remove fieldsets without any child form controls.
  foreach (element_children($form) as $group_key) {
    $group = $form[$group_key];
    if (isset($group['#type']) && $group['#type'] === 'fieldset' && !element_children($group)) {
      unset($form[$group_key]);
    }
  }

  return $form;
}

/**
 * Field name validation for the webform unique key. Must be alphanumeric.
 */
function webform_component_edit_form_validate($form, &$form_state) {
  $node = $form['#node'];;

  if (!preg_match('/^[a-z0-9_]+$/i', $form_state['values']['form_key'])) {
    form_set_error('form_key', t('The field key %field_key is invalid. Please include only lowercase alphanumeric characters and underscores.', array('%field_key' => $form_state['values']['form_key'])));
  }

  foreach ($node->webform['components'] as $cid => $component) {
    if (($component['cid'] != $form_state['values']['cid'] || $form_state['values']['clone']) && ($component['pid'] == $form_state['values']['pid']) && (strcasecmp($component['form_key'], $form_state['values']['form_key']) == 0)) {
      form_set_error('form_key', t('The field key %field_key is already in use by the field labeled %existing_field. Please use a unique key.', array('%field_key' => $form_state['values']['form_key'], '%existing_field' => $component['name'])));
    }
  }
}

/**
 * Submit handler for webform_component_edit_form().
 */
function webform_component_edit_form_submit($form, &$form_state) {
  // Ensure a webform record exists.
  $node = $form['#node'];
  webform_ensure_record($node);

  // Remove extra values that match the default.
  if (isset($form_state['values']['extra'])) {
    $default = array('type' => $form_state['values']['type'], 'extra' => array());
    webform_component_defaults($default);
    foreach ($form_state['values']['extra'] as $key => $value) {
      if (isset($default['extra'][$key]) && $default['extra'][$key] === $value) {
        unset($form_state['values']['extra'][$key]);
      }
    }
  }

  // Remove empty attribute values.
  if (isset($form_state['values']['extra']['attributes'])) {
    foreach ($form_state['values']['extra']['attributes'] as $key => $value) {
      if ($value === '') {
        unset($form_state['values']['extra']['attributes'][$key]);
      }
    }
  }

  if ($form_state['values']['clone']) {
    webform_component_clone($node, $form_state['values']);
    drupal_set_message(t('Component %name cloned.', array('%name' => $form_state['values']['name'])));
  }
  elseif (!empty($form_state['values']['cid'])) {
    webform_component_update($form_state['values']);
    drupal_set_message(t('Component %name updated.', array('%name' => $form_state['values']['name'])));
  }
  else {
    $cid = webform_component_insert($form_state['values']);
    drupal_set_message(t('New component %name added.', array('%name' => $form_state['values']['name'])));
  }

  // Since Webform components have been updated but the node itself has not
  // been saved, it is necessary to explicitly clear the cache to make sure
  // the updated webform is visible to anonymous users. This resets the page
  // and block caches (only);
  cache_clear_all();

  // Refresh the entity cache, should it be cached in persistent storage.
  entity_get_controller('node')->resetCache(array($node->id()));

  $form_state['redirect'] = array('node/' . $node->id() . '/webform/components', isset($cid) ? array('query' => array('cid' => $cid)) : array());
}

/**
 * Form to confirm deletion of a component.
 */
function webform_component_delete_form($form, $form_state, $node, $component) {
  $cid = $component['cid'];

  $form = array();
  $form['node'] = array(
    '#type' => 'value',
    '#value' => $node,
  );
  $form['component'] = array(
    '#type' => 'value',
    '#value' => $component,
  );

  $component_type = $node->webform['components'][$cid]['type'];
  if (webform_component_feature($component_type, 'group')) {
    $question = t('Delete the %name fieldset?', array('%name' => $node->webform['components'][$cid]['name']));
    $description = t('This will immediately delete the %name @type component and all nested components within %name from the %webform webform. This cannot be undone.',
                     array('%name' => $node->webform['components'][$cid]['name'],
                           '@type' => webform_component_property($component_type, 'label'),
                           '%webform' => $node->title));
  }
  else {
    $question = t('Delete the %name component?', array('%name' => $node->webform['components'][$cid]['name']));
    $description = t('This will immediately delete the %name component from the %webform webform. This cannot be undone.', array('%name' => $node->webform['components'][$cid]['name'], '%webform' => $node->title));
  }

  return confirm_form($form, $question, 'node/' . $node->id() . '/webform/components', $description, t('Delete'));
}

/**
 * Submit handler for webform_component_delete_form().
 */
function webform_component_delete_form_submit($form, &$form_state) {
  // Delete the component.
  $node = $form_state['values']['node'];
  $component = $form_state['values']['component'];
  webform_component_delete($node, $component);
  drupal_set_message(t('Component %name deleted.', array('%name' => $component['name'])));

  // Check if this webform still contains any information.
  unset($node->webform['components'][$component['cid']]);
  webform_check_record($node);

  // Since Webform components have been updated but the node itself has not
  // been saved, it is necessary to explicitly clear the cache to make sure
  // the updated webform is visible to anonymous users. This resets the page
  // and block caches (only);
  cache_clear_all();

  // Refresh the entity cache, should it be cached in persistent storage.
  entity_get_controller('node')->resetCache(array($node->id()));

  $form_state['redirect'] = 'node/' . $node->id() . '/webform/components';
}

/**
 * Insert a new component into the database.
 *
 * @param $component
 *   A full component containing fields from the component form.
 */
function webform_component_insert(&$component) {
  // Allow modules to modify the component before saving.
  foreach (\Drupal::moduleHandler()->getImplementations('webform_component_presave') as $module) {
    $function = $module . '_webform_component_presave';
    $function($component);
  }

  $component['value'] = isset($component['value']) ? $component['value'] : NULL;
  $component['required'] = isset($component['required']) ? $component['required'] : 0;
  $component['extra']['private'] = isset($component['extra']['private']) ? $component['extra']['private'] : 0;

  if (!isset($component['cid'])) {
    if (lock_acquire('webform_component_insert_' . $component['nid'], 5)) {
      $next_id_query = db_select('webform_component')->condition('nid', $component['nid']);
      $next_id_query->addExpression('MAX(cid) + 1', 'cid');
      $component['cid'] = $next_id_query->execute()->fetchField();
      if ($component['cid'] == NULL) {
        $component['cid'] = 1;
      }
      lock_release('webform_component_insert_' . $component['nid']);
    }
    else {
      watchdog('webform', 'A Webform component could not be saved because a timeout occurred while trying to acquire a lock for the node. Details: <pre>@component</pre>', array('@component' => print_r($component, TRUE)));
      return FALSE;
    }
  }

  $query = db_insert('webform_component')
    ->fields(array(
      'nid' => $component['nid'],
      'cid' => $component['cid'],
      'pid' => $component['pid'],
      'form_key' => $component['form_key'],
      'name' => $component['name'],
      'type' => $component['type'],
      'value' => (string) $component['value'],
      'extra' => serialize($component['extra']),
      'required' => $component['required'],
      'weight' => $component['weight'],
    ))
    ->execute();

  // Post-insert actions.
  \Drupal::moduleHandler()->invokeAll('webform_component_insert', $component);

  return $component['cid'];
}

/**
 * Update an existing component with new values.
 *
 * @param $component
 *   A full component containing a nid, cid, and all other fields from the
 *   component form. Additional properties are stored in the extra array.
 */
function webform_component_update($component) {
  // Allow modules to modify the component before saving.
  foreach (\Drupal::moduleHandler()->getImplementations('webform_component_presave') as $module) {
    $function = $module . '_webform_component_presave';
    $function($component);
  }

  $component['value'] = isset($component['value']) ? $component['value'] : NULL;
  $component['required'] = isset($component['required']) ? $component['required'] : 0;
  $component['extra']['private'] = isset($component['extra']['private']) ? $component['extra']['private'] : 0;
  db_update('webform_component')
    ->fields(array(
      'pid' => $component['pid'],
      'form_key' => $component['form_key'],
      'name' => $component['name'],
      'type' => $component['type'],
      'value' => isset($component['value']) ? $component['value'] : '',
      'extra' => serialize($component['extra']),
      'required' => $component['required'],
      'weight' => $component['weight']
    ))
    ->condition('nid', $component['nid'])
    ->condition('cid', $component['cid'])
    ->execute();

  // Post-update actions.
  \Drupal::moduleHandler()->invokeAll('webform_component_update', $component);
}

function webform_component_delete($node, $component) {
  // Check if a delete function is available for this component. If so,
  // load all submissions and allow the component to delete each one.
  webform_component_include($component['type']);
  $delete_function = '_webform_delete_' . $component['type'];
  if (function_exists($delete_function)) {
    module_load_include('inc', 'webform', 'includes/webform.submissions');
    $submissions = webform_get_submissions($node->id());
    foreach ($submissions as $submission) {
      if (isset($submission->data[$component['cid']])) {
        webform_component_invoke($component['type'], 'delete', $component, $submission->data[$component['cid']]);
      }
    }
  }

  // Remove database entries.
  db_delete('webform_component')
    ->condition('nid', $node->id())
    ->condition('cid', $component['cid'])
    ->execute();
  db_delete('webform_submitted_data')
    ->condition('nid', $node->id())
    ->condition('cid', $component['cid'])
    ->execute();

  // Delete any conditionals dependent on this component.
  module_load_include('inc', 'webform', 'includes/webform.conditionals');
  foreach ($node->webform['conditionals'] as $rgid => $conditional) {
    $delete_conditional = FALSE;
    $specs = array(
      array(
        'field' => 'rules',
        'table' => 'webform_conditional_rules',
        'component' => 'source',
        'component_type' => 'source_type',
        'index' => 'rid',
      ),
      array(
        'field' => 'actions',
        'table' => 'webform_conditional_actions',
        'component' => 'target',
        'component_type' => 'target_type',
        'index' => 'aid',
      ),
    );
    foreach ($specs as $spec) {
      $count = count($conditional[$spec['field']]);
      foreach ($conditional[$spec['field']] as $key => $thing) {
        if ($thing[$spec['component_type']] === 'component' && $thing[$spec['component']] == $component['cid']) {
          if ($count == 1) {
            // Delete the conditional if this component is the only source or target.
            webform_conditional_delete($node, $conditional);
            break 2;
          }
          db_delete($spec['table'])
            ->condition('nid', $node->id())
            ->condition('rgid', $rgid)
            ->condition($spec['index'], $key)
            ->execute();
          $count--;
        }
      }
    }
  }

  // Delete all elements under this element.
  $result = db_select('webform_component', 'c')
    ->fields('c')
    ->condition('nid', $node->id())
    ->condition('pid', $component['cid'])
    ->execute();
  foreach ($result as $row) {
    $child_component = $node->webform['components'][$row->cid];
    webform_component_delete($node, $child_component);
  }

  // Post-delete actions.
  \Drupal::moduleHandler()->invokeAll('webform_component_delete', $component);
}

/**
 * Recursively insert components into the database.
 *
 * @param $node
 *   The node object containing the current webform.
 * @param $component
 *   A full component containing fields from the component form.
 */
function webform_component_clone(&$node, &$component) {
  $original_cid = $component['cid'];
  $component['cid'] = NULL;
  $new_cid = webform_component_insert($component);
  $component['cid'] = $new_cid;
  if (webform_component_feature($component['type'], 'group')) {
    foreach ($node->webform['components'] as $cid => $child_component) {
      if ($child_component['pid'] == $original_cid) {
        $child_component['pid'] = $new_cid;
        webform_component_clone($node, $child_component);
      }
    }
  }
  return $new_cid;
}

/**
 * Check if a component has a particular feature.
 *
 * @see hook_webform_component_info()
 */
function webform_component_feature($type, $feature) {
  $components = webform_components();
  $defaults = array(
    'analysis' => TRUE,
    'csv' => TRUE,
    'default_value' => TRUE,
    'description' => TRUE,
    'email' => TRUE,
    'email_address' => FALSE,
    'email_name' => FALSE,
    'required' => TRUE,
    'title' => TRUE,
    'title_display' => TRUE,
    'title_inline' => TRUE,
    'title_internal' => FALSE,
    'conditional' => TRUE,
    'conditional_action_set' => FALSE,
    'spam_analysis' => FALSE,
    'group' => FALSE,
    'attachment' => FALSE,
    'private' => TRUE,
    'placeholder' => FALSE,
    'wrapper_classes' => TRUE,
    'css_classes' => TRUE,
    'views_range' => FALSE,
  );
  return isset($components[$type]['features'][$feature]) ? $components[$type]['features'][$feature] : !empty($defaults[$feature]);
}

/**
 * Get a component property from the component definition.
 *
 * @see hook_webform_component_info()
 */
function webform_component_property($type, $property) {
  $components = webform_components();
  $defaults = array(
    'conditional_type' => 'string',
  );
  return isset($components[$type][$property]) ? $components[$type][$property] : $defaults[$property];
}

/**
 * Create a list of components suitable for a select list.
 *
 * @param $node
 *   The webform node.
 * @param $component_filter
 *   Either an array of components, or a string containing a feature name (csv,
 *   email, required, conditional) on which this list of components will be
 *   restricted.
 * @param $prefix_group
 *   TRUE to indent with a hyphen, or 'path" to Prepend enclosing group (e.g.
 *   fieldset) name(s)
 * @param $pagebreak_groups
 *   Determine if pagebreaks should be converted to option groups in the
 *   returned list of options.
 */
function webform_component_list($node, $component_filter = NULL, $prepend_group = TRUE, $pagebreak_groups = FALSE) {
  $options = array();
  $page_names = array();
  $parent_names = array();

  $components = is_array($component_filter) ? $component_filter : $node->webform['components'];
  $feature = is_string($component_filter) ? $component_filter : NULL;

  foreach ($components as $cid => $component) {
    // If this component is a group (e.g. fieldset), then remember its name, including any parents.
    if ($prepend_group && webform_component_feature($component['type'], 'group')) {
      $parent_names[$cid] = ($component['pid'] ? $parent_names[$component['pid']] : '') .
                            ($prepend_group === 'path' ? $component['name'] . ': ' : '-');
    }
    $page_num = $component['page_num'];
    // If this component is a pagebreak, then generate an option group, ensuring a unique name.
    if ($pagebreak_groups && $component['type'] == 'pagebreak') {
      $page_name = $component['name'];
      $copy = 1;
      while (in_array($page_name, $page_names)) {
        $page_name = $component['name'] . '_' . ++$copy;
      }
      $page_names[$page_num] = $page_name;
    }
    // If this component should be included in the options, add it with any prefix, in a page group, as needed.
    if (!isset($feature) || webform_component_feature($component['type'], $feature) || $prepend_group === TRUE) {
      $prefix = ($prepend_group && $component['pid']) ? $parent_names[$component['pid']] : '';
      if ($pagebreak_groups && $page_num > 1) {
        $options[$page_names[$page_num]][$cid] = $prefix . $component['name'];
      }
      else {
        $options[$cid] = $prefix . $component['name'];
      }
    }
  }

  return $options;
}

/**
 * A Form API process function to expand a component list into checkboxes.
 */
function webform_component_select($element) {
  // Split the select list into checkboxes.
  foreach ($element['#options'] as $key => $label) {
    $label_length = strlen($label);
    $label = preg_replace('/^(\-)+/', '', $label);
    $indents = $label_length - strlen($label);
    $element[$key] = array(
      '#title' => $label,
      '#type' => 'checkbox',
      '#default_value' => array_search($key, $element['#value']) !== FALSE,
      '#return_value' => $key,
      '#parents' => array_merge($element['#parents'], array($key)),
      '#indent' => $indents,
    );
  }

  $element['#theme_wrappers'] = array();
  $element['#type'] = 'webform_component_select';
  $element['#theme'] = 'webform_component_select';
  $element['#attached'] = array(
    'library' => array(
      array('webform', 'admin'),
      array('system', 'drupal.collapse'),
    ),
    'js' => array(
      'misc/tableselect.js' => array(),
    ),
  );

  return $element;
}

/**
 * Theme the contents of a Webform component select element.
 */
function theme_webform_component_select($variables) {
  $element = $variables['element'];

  $rows = array();
  $header = array();
  if (!isset($element['#all_checkbox']) || $element['#all_checkbox']) {
    $header = array(array('class' => array('select-all'), 'data' => ' ' . t('Include all components')));
  }
  foreach (element_children($element) as $key) {
    if ($key != 'suffix') {
      $rows[] = array(
        theme('indentation', array('size' => $element[$key]['#indent'])) . drupal_render($element[$key]),
      );
    }
  }

  $element['#type'] = 'fieldset';
  $element['#value'] = NULL;
  $element['#attributes']['class'] = array('webform-component-select-table');
  if (!isset($element['#collapsible']) || $element['#collapsible']) {
    $element['#attributes']['class'][] = 'collapsible';
  }
  if (!isset($element['#collapsed']) || $element['#collapsed']) {
    $element['#attributes']['class'][] = 'collapsed';
  }

  if (empty($rows)) {
    $element['#children'] = t('No available components.');
  }
  else {
    $element['#children'] = '<div class="webform-component-select-wrapper">' . theme('table', array('header' => $header, 'rows' => $rows, 'sticky' => FALSE)) . '</div>';
  }

  if (isset($element['suffix'])) {
    $element['#children'] .= '<div class="webform-component-select-suffix">' . drupal_render($element['suffix']) . '</div>';
  }


  return theme('fieldset', array('element' => $element));
}

/**
 * Find a components parents within a node.
 */
function webform_component_parent_keys($node, $component) {
  $parents = array($component['form_key']);
  $pid = $component['pid'];
  while ($pid) {
    $parents[] = $node->webform['components'][$pid]['form_key'];
    $pid = $node->webform['components'][$pid]['pid'];
  }
  return array_reverse($parents);
}

/**
 * Populate a component with the defaults for that type.
 */
function webform_component_defaults(&$component) {
  $defaults = webform_component_invoke($component['type'], 'defaults');
  \Drupal::moduleHandler()->alter('webform_component_defaults', $defaults, $component['type']);
  if (!empty($defaults)) {
    foreach ($defaults as $key => $default) {
      if (!isset($component[$key])) {
        $component[$key] = $default;
      }
    }
    foreach ($defaults['extra'] as $extra => $default) {
      if (!isset($component['extra'][$extra])) {
        $component['extra'][$extra] = $default;
      }
    }
  }
}

/**
 * Validate an element value is unique with no duplicates in the database.
 */
function webform_validate_unique($element, $form_state) {
  if ($element['#value'] !== '') {
    $nid = $form_state['values']['details']['nid'];
    $sid = $form_state['values']['details']['sid'];
    $query = db_select('webform_submitted_data')
      ->fields('webform_submitted_data', array('sid'))
      ->condition('nid', $nid)
      ->condition('cid', $element['#webform_component']['cid'])
      ->condition('data', $element['#value'])
      ->range(0, 1); // More efficient than using countQuery() for data checks.
    if ($sid) {
      $query->condition('sid', $sid, '<>');
    }
    $count = $query->execute()->fetchField();
    if ($count) {
      form_error($element, t('The value %value has already been submitted once for the %title field. You may have already submitted this form, or you need to use a different value.', array('%value' => $element['#value'], '%title' => $element['#title'])));
    }
  }
}
