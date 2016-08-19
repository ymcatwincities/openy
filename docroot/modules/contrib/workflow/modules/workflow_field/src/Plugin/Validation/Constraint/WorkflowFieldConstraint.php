<?php

/**
 * @file
 * Contains \Drupal\workflowfield\Plugin\Validation\Constraint\WorkflowFieldConstraint.
 */

namespace Drupal\workflowfield\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Supports validating workflowfield names.
 *
 * @see https://drupalwatchdog.com/volume-5/issue-2/introducing-drupal-8s-entity-validation-api
 *
 * @Constraint(
 *   id = "WorkflowField",
 *   label = @Translation("Workflow field name", context = "Validation"),
 * )
 */
class WorkflowFieldConstraint extends CompositeConstraintBase {

  /**
   * Message shown when a comment fieldname doesn't match an entity field name.
   *
   * @var string
   */
  public $messageFieldname = 'A workflow field on a comment must have
    the same field_name as the commented Entity. Please maintain the entity
    first, or choose another field name.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['field_name', ];
  }

}
