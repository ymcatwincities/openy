<?php

/**
 * @file
 * Administration forms for the Scheduler module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeTypeInterface;

/**
 * Helper function for the real hook_form_node_type_form_alter().
 *
 * @see scheduler_form_node_type_form_alter()
 */
function _scheduler_form_node_type_form_alter(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\node\NodeTypeInterface $type */
  $type = $form_state->getFormObject()->getEntity();

  $form['#attached']['library'][] = 'scheduler/admin';
  $form['#attached']['library'][] = 'scheduler/vertical-tabs';

  $form['scheduler'] = array(
    '#type' => 'details',
    '#title' => t('Scheduler'),
    '#weight' => 35,
    '#group' => 'additional_settings',
  );

  // Publishing options.
  $form['scheduler']['publish'] = array(
    '#type' => 'details',
    '#title' => t('Publishing'),
    '#weight' => 1,
    '#group' => 'scheduler',
    '#open' => TRUE,
  );
  $form['scheduler']['publish']['scheduler_publish_enable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable scheduled publishing for this content type'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_enable', FALSE),
  );
  $form['scheduler']['publish']['scheduler_publish_touch'] = array(
    '#type' => 'checkbox',
    '#title' => t('Change content creation time to match the scheduled publish time'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_touch', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['publish']['scheduler_publish_required'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require scheduled publishing'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_required', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['publish']['scheduler_publish_revision'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a new revision on publishing'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_revision', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['publish']['advanced'] = array(
    '#type' => 'details',
    '#title' => t('Advanced options'),
    '#open' => FALSE,
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['publish']['advanced']['scheduler_publish_past_date'] = array(
    '#type' => 'radios',
    '#title' => t('Action to be taken for publication dates in the past'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'publish_past_date', 'error'),
    '#options' => array(
      'error' => t('Display an error message - do not allow dates in the past'),
      'publish' => t('Publish the content immediately after saving'),
      'schedule' => t('Schedule the content for publication on the next cron run'),
    ),
  );

  // Unpublishing options.
  $form['scheduler']['unpublish'] = array(
    '#type' => 'details',
    '#title' => t('Unpublishing'),
    '#weight' => 2,
    '#group' => 'scheduler',
    '#open' => TRUE,
  );
  $form['scheduler']['unpublish']['scheduler_unpublish_enable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable scheduled unpublishing for this content type'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'unpublish_enable', FALSE),
  );
  $form['scheduler']['unpublish']['scheduler_unpublish_required'] = array(
    '#type' => 'checkbox',
    '#title' => t('Require scheduled unpublishing'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'unpublish_required', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_unpublish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['scheduler']['unpublish']['scheduler_unpublish_revision'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a new revision on unpublishing'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'unpublish_revision', FALSE),
    '#states' => array(
      'visible' => array(
        ':input[name="scheduler_unpublish_enable"]' => array('checked' => TRUE),
      ),
    ),
  );

  // The 'node_edit_layout' fieldset contains options to alter the layout of
  // node edit pages.
  $form['scheduler']['node_edit_layout'] = array(
    '#type' => 'details',
    '#title' => t('Node edit page layout'),
    '#weight' => 3,
    '#group' => 'scheduler',
    // The #states processing only caters for AND and does not do OR. So to set
    // the state to visible if either of the boxes are ticked we use the fact
    // that logical 'X = A or B' is equivalent to 'not X = not A and not B'.
    '#states' => array(
      '!visible' => array(
        ':input[name="scheduler_publish_enable"]' => array('!checked' => TRUE),
        ':input[name="scheduler_unpublish_enable"]' => array('!checked' => TRUE),
      ),
    ),
  );
  // @todo Worthwhile to port this to D8 now form displays are configurable?
  $form['scheduler']['node_edit_layout']['scheduler_use_vertical_tabs'] = array(
    '#type' => 'radios',
    '#title' => t('Display scheduling options as'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'use_vertical_tabs', TRUE),
    '#options' => array(
      '1' => t('Vertical tab'),
      '0' => t('Separate fieldset'),
    ),
    '#description' => t('Use this option to specify how the scheduling options will be displayed when editing a node.'),
  );
  $form['scheduler']['node_edit_layout']['scheduler_expand_fieldset'] = array(
    '#type' => 'radios',
    '#title' => t('Expand fieldset'),
    '#default_value' => $type->getThirdPartySetting('scheduler', 'expand_fieldset', FALSE),
    '#options' => array(
      '0' => t('Expand only when a scheduled date exists or when a date is required'),
      '1' => t('Always open the fieldset, even if no dates exist'),
    ),
  );

  $form['#entity_builders'][] = 'scheduler_form_node_type_form_builder';
}

/**
 * Entity builder for the node type form with scheduler options.
 */
function scheduler_form_node_type_form_builder($entity_type, NodeTypeInterface $type, &$form, FormStateInterface $form_state) {
  $type->setThirdPartySetting('scheduler', 'expand_fieldset', $form_state->getValue('scheduler_expand_fieldset'));
  $type->setThirdPartySetting('scheduler', 'publish_enable', $form_state->getValue('scheduler_publish_enable'));
  $type->setThirdPartySetting('scheduler', 'publish_past_date', $form_state->getValue('scheduler_publish_past_date'));
  $type->setThirdPartySetting('scheduler', 'publish_required', $form_state->getValue('scheduler_publish_required'));
  $type->setThirdPartySetting('scheduler', 'publish_revision', $form_state->getValue('scheduler_publish_revision'));
  $type->setThirdPartySetting('scheduler', 'publish_touch', $form_state->getValue('scheduler_publish_touch'));
  $type->setThirdPartySetting('scheduler', 'unpublish_enable', $form_state->getValue('scheduler_unpublish_enable'));
  $type->setThirdPartySetting('scheduler', 'unpublish_required', $form_state->getValue('scheduler_unpublish_required'));
  $type->setThirdPartySetting('scheduler', 'unpublish_revision', $form_state->getValue('scheduler_unpublish_revision'));
  $type->setThirdPartySetting('scheduler', 'use_vertical_tabs', $form_state->getValue('scheduler_use_vertical_tabs'));
}
