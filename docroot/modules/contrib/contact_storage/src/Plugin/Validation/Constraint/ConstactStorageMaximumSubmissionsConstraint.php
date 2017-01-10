<?php

namespace Drupal\contact_storage\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Verify that the form has not been submitted more times that the limit.
 *
 * @Constraint(
 *   id = "ConstactStorageMaximumSubmissions",
 *   label = @Translation("Maximum submission limit", context = "Validation"),
 * )
 */
class ConstactStorageMaximumSubmissionsConstraint extends Constraint {

  /**
   * Message shown when the maximum submission limit has been reached.
   *
   * @var string
   */
  public $limitReached = 'You have reached the maximum submission limit of @limit for this form.';

}
