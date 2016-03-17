<?php

namespace Drupal\webforms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\contact\ContactFormEditForm as CoreContactFormEditForm;

/**
 * Extended base form for contact form edit forms.
 */
class ContactFormEditForm extends CoreContactFormEditForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\webforms\ContactForm $contact_form */
    $contact_form = $this->entity;
    $form['prefix'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Prefix'),
      '#default_value' => $contact_form->getPrefix(),
      '#description' => $this->t('Optional prefix.'),
    );
    $form['suffix'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Suffix'),
      '#default_value' => $contact_form->getSuffix(),
      '#description' => $this->t('Optional suffix.'),
    );
    $form['submission_page'] = array(
      '#type' => 'details',
      '#title' => $this->t('Submission page'),
      '#collapsible' => TRUE,
      '#open' => $contact_form->getProvideSubmissionPage(),
    );
    $form['submission_page']['provideSubmissionPage'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Provide submission page'),
      '#default_value' => $contact_form->getProvideSubmissionPage(),
      '#description' => $this->t('Instead of redirecting to any page show configured page that could use tokens to output submission data.'),
    );
    $form['submission_page']['submissionPageTitle'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Submission title'),
      '#default_value' => $contact_form->getSubmissionPageTitle(),
    );
    $form['submission_page']['submissionPageContent'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Submission page content'),
      '#description' => $this->t('Enter markup that should be shown on the submission page of this form.'),
    );
    if ($submission_page_content = $contact_form->getSubmissionPageContent()) {
      $form['submission_page']['submissionPageContent']['#format'] = $submission_page_content['format'];
      $form['submission_page']['submissionPageContent']['#default_value'] = $submission_page_content['value'];
    }
    $form['submission_page']['tokens'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => ['node', 'user', 'query', 'contact_message', 'contact'],
      '#click_insert' => FALSE,
      '#global_types' => TRUE,
    );
    $path = $contact_form->getSubmissionPagePath();
    $form['submission_page']['path'] = array(
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('Url path settings'),
      '#open' => TRUE,
      '#element_validate' => array(array(get_class($this), 'validateSubmissionPathSettings')),
    );
    $form['submission_page']['path']['alias'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Submission page URL alias'),
      '#default_value' => $path['alias'],
      '#required' => FALSE,
      '#maxlength' => 255,
      '#description' => $this->t('The alternative URL for submission page. Use a relative path. For example, "/membership/thank-you".'),
    );
    $form['submission_page']['path']['pid'] = array(
      '#type' => 'value',
      '#value' => $path['pid'],
    );
    $form['submission_page']['path']['source'] = array(
      '#type' => 'value',
      '#value' => $path['source'],
    );
    $form['submission_page']['path']['langcode'] = array(
      '#type' => 'value',
      '#value' => $path['langcode'],
    );

    $email_settings = $contact_form->getEmailSettings();
    $form['email'] = array(
      '#type' => 'details',
      '#title' => $this->t('Email templates'),
      '#collapsible' => TRUE,
      '#open' => $email_settings['custom'],
      '#tree' => TRUE,
    );
    $form['email']['custom'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use custom email template'),
      '#default_value' => $email_settings['custom'],
    );
    $form['email']['subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $email_settings['subject'],
      '#description' => $this->t('Tokens are supported.'),
    );
    $form['email']['content'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Email content'),
      '#description' => $this->t('Enter markup to use for email content.'),
    );
    $form['email']['content']['#format'] = $email_settings['content']['format'];
    $form['email']['content']['#default_value'] = $email_settings['content']['value'];
    $form['email']['tokens'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'contact_message'],
      '#click_insert' => FALSE,
    );

    return $form;
  }

  /**
   * Form element validation handler for URL alias form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateSubmissionPathSettings(array &$element, FormStateInterface $form_state) {
    // Trim the submitted value of whitespace and slashes.
    $alias = rtrim(trim($element['alias']['#value']), " \\/");
    if (!empty($alias)) {
      $form_state->setValueForElement($element['alias'], $alias);

      // Validate that the submitted alias does not exist yet.
      $is_exists = \Drupal::service('path.alias_storage')->aliasExists($alias, $element['langcode']['#value'], $element['source']['#value']);
      if ($is_exists) {
        $form_state->setError($element, t('The alias is already in use.'));
      }
    }

    if ($alias && $alias[0] !== '/') {
      $form_state->setError($element, t('The alias needs to start with a slash.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    /* @var \Drupal\webforms\ContactForm $contact_form */
    $contact_form = $this->entity;

    $path = $contact_form->path;
    // New form.
    if ($path['alias'] && !$path['source']) {
      $path_info = $contact_form->getSubmissionPagePath();
      if ($_path = \Drupal::service('path.alias_storage')->save($path_info['source'], $path['alias'], $path['langcode'])) {
        $path['pid'] = $_path['pid'];
      }
    }
    else {
      // Delete old alias if user erased it.
      if ($path['pid'] && !$path['alias']) {
        \Drupal::service('path.alias_storage')->delete(['pid' => $path['pid']]);
      }
      // Only save a non-empty alias.
      elseif ($path['alias']) {
        \Drupal::service('path.alias_storage')->save($path['source'], $path['alias'], $path['langcode'], $path['pid']);
      }
    }
  }

}
