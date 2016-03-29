<?php

namespace Drupal\webforms;

use Drupal\contact\Entity\ContactForm as CoreContactForm;
use Drupal\Core\Language\LanguageInterface;

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
   * Form's provide submission page setting.
   *
   * @var bool
   */
  protected $provideSubmissionPage = FALSE;

  /**
   * Form's submission page content setting.
   *
   * @var array
   */
  protected $submissionPageContent = [];

  /**
   * Form's submission page title.
   *
   * @var string
   */
  protected $submissionPageTitle = 'Thank you';

  /**
   * Form's email settings.
   *
   * @var array
   */
  protected $email = [
    'custom' => FALSE,
    'subject' => '',
    'content' => [
      'value' => '',
      'format' => 'full_html',
    ],
  ];

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

  /**
   * {@inheritdoc}
   */
  public function getProvideSubmissionPage() {
    return $this->provideSubmissionPage;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvideSubmissionPage($value) {
    $this->provideSubmissionPage = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionPageTitle() {
    return $this->submissionPageTitle;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionPageContent() {
    return $this->submissionPageContent;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmissionPageContent($value) {
    $this->submissionPageContent = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionPagePath() {
    $source = NULL;
    $path = array();
    if (!$this->enforceIsNew) {
      $source = '/submission/' . $this->id() . '/thank_you';
      $conditions = ['source' => $source];
      if ($this->langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        $conditions['langcode'] = $this->langcode;
      }
      $path = \Drupal::service('path.alias_storage')->load($conditions);
      if ($path === FALSE) {
        $path = array();
      }
    }

    $path += array(
      'pid' => NULL,
      'source' => $source,
      'alias' => '',
      'langcode' => $this->langcode,
    );

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailSettings() {
    return $this->email;
  }

}
