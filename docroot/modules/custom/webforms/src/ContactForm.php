<?php

namespace Drupal\webforms;

use Drupal\contact\Entity\ContactForm as CoreContactForm;

/**
 * Extends core ContactForm with prefix and suffix.
 */
class ContactForm extends CoreContactForm {

  /**
   * Form's prefix.
   *
   * @var string
   */
  protected $prefix = '';

  /**
   * Form's suffix.
   *
   * @var string
   */
  protected $suffix = '';

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    return $this->prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrefix($value) {
    $this->prefix = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuffix() {
    return $this->suffix;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuffix($value) {
    $this->suffix = $value;
    return $this;
  }

}
