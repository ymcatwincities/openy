<?php

use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\NodeType;

/**
 * Theme the components table of the Webform settings form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see \Drupal\webform\Form\WebformSettingsForm::buildForm().
 * @ingroup themeable
 */
function theme_webform_admin_settings_components_table($variables) {
  $form = $variables['form'];

  // Format the components into a table.
  $rows = array();
  foreach (Element::children($form) as $key) {
    $row = array();
    $row[] = $form[$key]['#title'];
    $row[] = $form[$key]['#description'];
    $form[$key]['#title'] = NULL;
    $form[$key]['#description'] = NULL;
    $row[] = array('data' => drupal_render($form[$key]), 'align' => 'center');
    $rows[] = $row;
  }
  $header = array(t('Name'), t('Description'), array('data' => t('Enabled'), 'class' => array('checkbox')));

  // Create the table inside the form.
  $table = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  return drupal_render($table);
}

/**
 * Create a comma-separate list of content types that are webform enabled.
 */
function webform_admin_type_list() {
  $webform_types = webform_node_types();
  $webform_type_list = '';
  $webform_type_count = count($webform_types);
  foreach ($webform_types as $n => $type) {
    $node_type = NodeType::load($type);
    $webform_type_list .= \Drupal::l(t('@type', array('@type' => $node_type->label())), Url::fromRoute('node.add', ['node_type' => $type]));
    if ($n + 1 < $webform_type_count) {
      $webform_type_list .= $webform_type_count == 2 ? ' ' : ', ';
    }
    if ($n + 2 == $webform_type_count) {
      $webform_type_list .= t('or') . ' ';
    }
  }
  return $webform_type_list;
}
