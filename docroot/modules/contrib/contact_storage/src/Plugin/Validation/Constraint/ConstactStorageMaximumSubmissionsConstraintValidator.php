<?php

namespace Drupal\contact_storage\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the maximum submission limit constraint.
 */
class ConstactStorageMaximumSubmissionsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    // Check if the current user has reached the form's maximum submission limit.
    $contact_form = $entity->getParent()->get('contact_form')->referencedEntities()[0];
    $maximum_submissions_user = $contact_form->getThirdPartySetting('contact_storage', 'maximum_submissions_user', 0);
    if (($maximum_submissions_user !== 0) && contact_storage_maximum_submissions_user($contact_form) >= $maximum_submissions_user) {
      // Limit reached; can't submit the form.
      $this->context->addViolation($constraint->limitReached, ['@limit' => $maximum_submissions_user]);
    }
  }

}
