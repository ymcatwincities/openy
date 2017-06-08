<?php

namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SchedulerPublishOn constraint.
 */
class SchedulerPublishOnConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $publish_on = $entity->value;
    $default_publish_past_date = \Drupal::config('scheduler.settings')->get('default_publish_past_date');
    $scheduler_publish_past_date = $entity->getEntity()->type->entity->getThirdPartySetting('scheduler', 'publish_past_date', $default_publish_past_date);

    if ($publish_on && $scheduler_publish_past_date == 'error' && $publish_on < REQUEST_TIME) {
      $this->context->buildViolation($constraint->messagePublishOnDateNotInFuture)
        ->atPath('publish_on')
        ->addViolation();
    }
  }

}
