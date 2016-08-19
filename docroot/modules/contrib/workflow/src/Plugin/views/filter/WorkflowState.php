<?php

/**
 * @file
 * Contains \Drupal\workflow\Plugin\views\filter\WorkflowState.
 */

namespace Drupal\workflow\Plugin\views\filter;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filter handler which uses workflow_state as options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("workflow_state")
 */
class WorkflowState extends ManyToOne {

  use FieldAPIHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->definition['field_name'] = $this->definition['entity field'];
    $field_storage = $this->getFieldStorageDefinition();

    // We may have not a valid workflow field.
    if (FALSE && $field_storage) {
      // Set valueOptions here so getValueOptions() will just return it.
      $this->valueOptions = options_allowed_values($field_storage);
      // $this->valueOptions = workflow_state_allowed_values($field_storage);
    }
    else {
      // TODO D8. This is a hack. It doesn't work.
      // The default options_allowed_values only reads field_name. Repair that.
      $field_name = $this->definition['entity field'];
      $field_storage = new FieldStorageConfig([
        'field_name' => $field_name,
        'type' => 'testtype',
        'entity_type' => $options['entity_type']]);
      $field_storage->set('allowed_values_function', 'workflow_state_allowed_values');
      $wid = '';

      // Set valueOptions here so getValueOptions() will just return it.
      // $this->valueOptions = options_allowed_values($field_storage);
      // $this->valueOptions = workflow_state_allowed_values($field_storage);
      $this->valueOptions = workflow_get_workflow_state_names($wid, $options['group_info']['widget'] == 'select');
    }

  }

}
