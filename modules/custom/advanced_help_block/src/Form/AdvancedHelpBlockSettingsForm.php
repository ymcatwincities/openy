<?php

namespace Drupal\advanced_help_block\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class AdvancedHelpBlockSettingsForm.
 *
 * @package Drupal\advanced_help_block\Form
 * @ingroup advanced_help_block
 */
class AdvancedHelpBlockSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'advanced_help_block_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_factory = \Drupal::configFactory();
    $config_factory->getEditable('advanced_help_block.settings')
      ->set('advanced_help_block.view_type', $form_state->getValue('view_type'))
      ->save();
    drupal_set_message(t('Advanced help block type was changed to <b>@value</b>', ['@value' => $form_state->getValue('view_type')]));
  }


  /**
   * Define the form used for ContentEntityExample settings.
   *
   * @return array
   *   Form definition array.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config_factory = \Drupal::configFactory();
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
      '#default_value' => $config_factory->getEditable('advanced_help_block.settings')
        ->get('advanced_help_block.view_type'),
    ];

    $form['actions'] = [
      '#type' => 'actions'
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save settings')
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('view.advanced_help_blocks.ahb_list'),
    ];

    return $form;
  }
}
