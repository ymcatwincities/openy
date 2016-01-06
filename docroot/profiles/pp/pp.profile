<?php
/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function pp_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  // Add a placeholder as example that one can choose an arbitrary site name.
  $form['site_information']['site_name']['#attributes']['placeholder'] = t('YMCA Twin Cities');
  $form['#submit'][] = 'pp_form_install_configure_submit';
}

/**
 * Submission handler to sync the contact.form.feedback recipient.
 */
function pp_form_install_configure_submit($form, FormStateInterface $form_state) {
  $site_mail = $form_state->getValue('site_mail');
  ContactForm::load('personal')->setRecipients([$site_mail])->trustData()->save();
}

/**
 * Implements hook_install_tasks().
 */
function pp_install_tasks($install_state){
  $tasks = array(
    'pp_disable_views' => array(
      'display_name' => t('Disable views'),
      'display' => FALSE,
      'type' => 'normal'
    ),
  );

  return $tasks;
}

/**
 * Disable views install task.
 */
function pp_disable_views() {
  View::load('taxonomy_term')->disable()->save();
}
