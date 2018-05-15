<?php

namespace Drupal\advanced_help_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdvancedHelpBlockSettingsForm.
 *
 * @package Drupal\advanced_help_block\Form
 * @ingroup advanced_help_block
 */
class AdvancedHelpBlockSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced_help_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'advanced_help_block.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['advanced_help_block'] = [
      '#type' => 'details',
      '#title' => t('Advanced help block settigns.'),
      '#open' => TRUE,
    ];
    $form['advanced_help_block']['view_type'] = [
      '#type' => 'select',
      '#title' => t('Output advanced help block as:'),
      '#options' => [
        'block' => t('Block'),
        'message' => t('Message')
      ],
      '#default_value' => $this->config('advanced_help_block.settings')
        ->get('advanced_help_block.view_type'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('advanced_help_block.settings')
      ->set('advanced_help_block.view_type', $form_state->getValue('view_type'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.advanced_help_blocks.ahb_list');
  }

}
