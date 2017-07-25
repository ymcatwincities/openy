<?php

namespace Drupal\Tests\address\Unit\Plugin\Validation\Constraint;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\address\Plugin\Validation\Constraint\CountryConstraint;
use Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @coversDefaultClass \Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator
 * @group address
 */
class CountryConstraintValidatorTest extends UnitTestCase {

  /**
   * The constraint.
   *
   * @var \Drupal\address\Plugin\Validation\Constraint\CountryConstraint
   */
  protected $constraint;

  /**
   * The validator.
   *
   * @var \Drupal\address\Plugin\Validation\Constraint\CountryConstraintValidator
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $country_repository = $this->prophesize(CountryRepositoryInterface::class);
    $country_repository->getList()->willReturn(['RS' => 'Serbia', 'FR' => 'France']);

    $this->constraint = new CountryConstraint(['availableCountries' => ['FR']]);
    $this->validator = new CountryConstraintValidator($country_repository->reveal());
  }

  /**
   * @covers ::validate
   *
   * @dataProvider providerTestValidate
   */
  public function testValidate($country_code, $expected_violation) {
    // If a violation is expected, then the context's buildViolation method
    // will be called, otherwise it should not be called.
    $context = $this->prophesize(ExecutionContextInterface::class);
    if ($expected_violation) {
      $violation_builder = $this->prophesize(ConstraintViolationBuilderInterface::class);
      $violation_builder->setParameter('%value', Argument::any())->willReturn($violation_builder);
      $violation_builder->addViolation()->willReturn($violation_builder);
      $context->buildViolation($expected_violation)->willReturn($violation_builder->reveal())->shouldBeCalled();
    }
    else {
      $context->buildViolation(Argument::any())->shouldNotBeCalled();
    }

    $this->validator->initialize($context->reveal());
    $this->validator->validate($country_code, $this->constraint);
  }

  /**
   * Data provider for ::testValidate().
   */
  public function providerTestValidate() {
    // Data provides run before setUp, so $this->constraint is not available.
    $constraint = new CountryConstraint();

    $cases = [];
    // Case 1: Empty values.
    $cases[] = [NULL, FALSE];
    $cases[] = ['', FALSE];
    // Case 2: Valid country.
    $cases[] = ['FR', FALSE];
    // Case 3: Invalid country.
    $cases[] = ['InvalidValue', $constraint->invalidMessage];
    // Case 4: Valid, but unavailable country.
    $cases[] = ['RS', $constraint->notAvailableMessage];

    return $cases;
  }

}
