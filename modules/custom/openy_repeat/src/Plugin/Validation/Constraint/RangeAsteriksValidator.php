<?php

namespace Drupal\openy_repeat\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class RangeAsteriksValidator extends ConstraintValidator {
  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    foreach ($items as $item) {
      // First check if the value is an *.
      if ($item->value != '*' && !is_int($item->value) && !($item->value >= $constraint->min && $item->value <= $constraint->max)) {
        // The value is not an integer or is not * or is not in range, so a violation, aka error, is applied.
        $this->context->addViolation($constraint->notRangeAsteriks, [
          '%value' => $item->value,
          '%min' => $constraint->min,
          '%max' => $constraint->max
        ]);
      }
    }
  }
}