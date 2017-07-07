<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'contact' element.
 *
 * @WebformElement(
 *   id = "webform_contact",
 *   label = @Translation("Contact"),
 *   description = @Translation("Provides a form element to collect contact information (name, address, phone, email)."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformContact extends WebformAddress {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $lines = [];
    if (!empty($value['name'])) {
      $lines['name'] = $value['name'];
    }
    if (!empty($value['company'])) {
      $lines['company'] = $value['company'];
    }
    $lines += parent::formatHtmlItemValue($element, $webform_submission, $options);
    if (!empty($value['email'])) {
      $lines['email'] = [
        '#type' => 'link',
        '#title' => $value['email'],
        '#url' => \Drupal::pathValidator()->getUrlIfValid('mailto:' . $value['email']),
      ];
    }
    if (!empty($value['phone'])) {
      $lines['phone'] = $value['phone'];
    }
    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $lines = [];
    if (!empty($value['name'])) {
      $lines['name'] = $value['name'];
    }
    if (!empty($value['company'])) {
      $lines['company'] = $value['company'];
    }
    $lines += parent::formatTextItemValue($element, $webform_submission, $options);
    if (!empty($value['email'])) {
      $lines['email'] = $value['email'];
    }
    if (!empty($value['phone'])) {
      $lines['phone'] = $value['phone'];
    }
    return $lines;
  }

}
