<?php

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
    $default_unpublish_required = \Drupal::config('scheduler.settings')->get('default_unpublish_required');
    $scheduler_unpublish_required = $entity->getEntity()->type->entity->getThirdPartySetting('scheduler', 'unpublish_required', $default_unpublish_required);
    $publish_on = $entity->getEntity()->publish_on->value;
    $unpublish_on = $entity->value;
    $status = $entity->getEntity()->status->value;

    // When the 'required unpublishing' option is enabled the #required form
    // attribute cannot set in every case. However a value must be entered if
    // also setting a publish-on date.
    if ($scheduler_unpublish_required && !empty($publish_on) && empty($unpublish_on)) {
      $this->context->buildViolation($constraint->messageUnpublishOnRequiredIfPublishOnEntered)
        ->atPath('unpublish_on')
        ->addViolation();
    }

    // Similar to the above scenario, the unpublish-on date must be entered if
    // the content is being published directly.
    if ($scheduler_unpublish_required && $status && empty($unpublish_on)) {
      $this->context->buildViolation($constraint->messageUnpublishOnRequiredIfPublishing)
        ->atPath('unpublish_on')
        ->addViolation();
    }

    // Check that the unpublish-on date is in the future. Unlike the publish-on
    // field, there is no option to use a past date, as this is not relevant for
    // unpublshing. The date must ALWAYS be in the future if it is entered.
    if ($unpublish_on && $unpublish_on < REQUEST_TIME) {
      $this->context->buildViolation($constraint->messageUnpublishOnDateNotInFuture)
        ->atPath('unpublish_on')
        ->addViolation();
    }

    // If both dates are entered then the unpublish-on date must be later than
    // the publish-on date.
    if (!empty($publish_on) && !empty($unpublish_on) && $unpublish_on < $publish_on) {
      $this->context->buildViolation($constraint->messageUnpublishOnTooEarly)
        ->atPath('unpublish_on')
        ->addViolation();
    }
  }

}
