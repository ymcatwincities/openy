<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_retention\AnonymousCookieStorage;

/**
 * Provides a block with registration form.
 *
 * @Block(
 *   id = "retention_registration_block",
 *   admin_label = @Translation("[YMCA Retention] Registration"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class RegistrationForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'yteam' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['yteam'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Y Team user registration'),
      '#description' => $this->t('Select this checkbox if the registrations on this page will be done by Y Team.'),
      '#default_value' => isset($config['yteam']) ? $config['yteam'] : 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('yteam', $form_state->getValue('yteam'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Remove cookie in case when registration form is displayed on the page.
    AnonymousCookieStorage::delete('ymca_retention_member');

    $config = $this->getConfiguration();

    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\ymca_retention\Form\MemberRegisterForm', $config);
    return [
      '#theme' => 'ymca_retention_registration_form',
      '#form' => $form,
      '#yteam' => $config['yteam'],
    ];
  }

}
