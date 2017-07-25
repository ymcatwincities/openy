<?php

/**
 * @file
 * Integrates third party settings on the Maillog module's behalf.
 */

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;

/**
 * Implements hook_webform_submission_form_alter().
 */
function maillog_webform_submission_form_alter(array &$form, FormStateInterface $form_state, $form_id) {
  // Never display Maillog message via CLI.
  if (PHP_SAPI == 'cli') {
    return;
  }

  // Never display Maillog message via dialogs or Ajax requests.
  $wrapper_format = \Drupal::request()->get(MainContentViewSubscriber::WRAPPER_FORMAT);
  if ($wrapper_format) {
    return;
  }

  $config = \Drupal::config('maillog.settings');

  // If this webform does not send any email do not display any warning.
  $webform = Webform::load($form['#webform_id']);
  $sends_email = FALSE;
  $handlers = $webform->getHandlers();
  foreach ($handlers as $handler) {
    if ($handler instanceof \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler) {
      $sends_email = TRUE;
    }
  }
  if (!$sends_email) {
    return;
  }

  // Build warning message base on maillog settings and permissions.
  $build = [];

  // Display warning that all emails will be logged.
  if ($config->get('log')) {
    if (\Drupal::currentUser()->hasPermission('administer maillog')) {
      $build[] = ['#markup' => t('The <a href=":href">Maillog</a> module is logging all emails.', [':href' => Url::fromRoute('maillog.settings')->toString()])];
    }
    else {
      $build[] = ['#markup' => t('The Maillog module is logging all emails.')];
    }
  }
  else {
    if (\Drupal::currentUser()->hasPermission('administer maillog')) {
      $build[] = ['#markup' => t('The <a href=":href">Maillog</a> module is installed.', [':href' => Url::fromRoute('maillog.settings')->toString()])];
    }
    else {
      $build[] = ['#markup' => t('The Maillog module is installed.')];
    }
  }

  if (\Drupal::currentUser()->hasPermission('view maillog')) {
    // Display warning if the user can view maillog.
    if ($config->get('log') && $url = \Drupal::pathValidator()->getUrlIfValid('/admin/reports/maillog')) {
      $build[] = ['#prefix' => ' ', '#markup' => t('Emails will be <a href=":href">logged</a>.', [':href' => $url->toString()])];
    }
    // Display warning if the user can view email on page.
    if ($config->get('verbose')) {
      $build[] = ['#prefix' => ' ', '#markup' => t('Emails will displayed on this page.')];
    }
  }

  // Display warning if the Maillog module is not sending email.
  if (!$config->get('send')) {
    // Display warning that all emails will be logged.
    $build[] = ['#markup' => t('No emails will be sent.'), '#prefix' => ' <b>', '#suffix' => '</b>'];
  }

  drupal_set_message(\Drupal::service('renderer')->renderPlain($build), 'warning');
}
