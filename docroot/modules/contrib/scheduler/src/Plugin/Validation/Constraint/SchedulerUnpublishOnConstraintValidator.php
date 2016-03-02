<?php

/**
 * @file
 * Contains \Drupal\scheduler\Plugin\Validation\Constraint\SchedulerUnpublishOnConstraintValidator.
 */

namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SchedulerUnpublishOn constraint.
 */
class SchedulerUnpublishOnConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $scheduler_unpublish_required = $entity->getEntity()->type->entity->getThirdPartySetting('scheduler', 'unpublish_required');
    $publish_on = $entity->getEntity()->publish_on->value;
    $unpublish_on = $entity->value;

    // The unpublish-on 'required' form attribute is not set in every case, but
    // a value must be entered if also setting a publish-on date.
    if ($scheduler_unpublish_required && !empty($publish_on) && empty($unpublish_on)) {
      $this->context->buildViolation($constraint->messageUnpublishOnRequiredIfPublishOnEntered)
        ->atPath('unpublish_on')
        ->addViolation();
    }
  }
}
