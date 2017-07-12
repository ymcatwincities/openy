<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'boolean' class.
 */
abstract class BooleanBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);

    switch ($format) {
      case 'value':
        return ($value) ? $this->t('Yes') : $this->t('No');

      default:
        return ($value) ? 1 : 0;
    }
  }

}
