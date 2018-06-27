<?php

namespace Drupal\openy_repeat\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a * or range integer.
 *
 * @Constraint(
 *   id = "range_asteriks",
 *   label = @Translation("Range Asteriks", context = "Validation"),
 *   type = "string"
 * )
 */
class RangeAsteriks extends Constraint {

  // The message that will be shown if the value is not an * or range integer.
  public $notRangeAsteriks = '%value is not a * and in range of %min - %max';

  // min value of the range.
  public $min;

  // max value of the range.
  public $max;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['min', 'max'];
  }

}