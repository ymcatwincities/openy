<?php

namespace Drupal\address\Plugin\Validation\Constraint;

use CommerceGuys\Addressing\Validator\Constraints\AddressFormatConstraint as ExternalAddressFormatConstraint;

/**
 * Address format constraint.
 *
 * @Constraint(
 *   id = "AddressFormat",
 *   label = @Translation("Address Format", context = "Validation"),
 *   type = { "address" }
 * )
 */
class AddressFormatConstraint extends ExternalAddressFormatConstraint {

  public $blankMessage = '@name field must be blank.';
  public $notBlankMessage = '@name field is required.';
  public $invalidMessage = '@name field is not in the right format.';

}
