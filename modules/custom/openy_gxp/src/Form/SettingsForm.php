<?php

namespace Drupal\openy_gxp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Settings Form for gxp.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_gxp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_gxp.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_gxp.settings');

    $form_state->setCached(FALSE);

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Id'),
      '#default_value' => $config->get('client_id'),
      '#description' => t('Your GroupExPro client id. Like 3.'),
    ];

    $form['activity'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => 'activity'],
      '#title' => $this->t('Activity'),
      '#default_value' => Node::load($config->get('activity')),
      '#description' => t('What activity we should use as a parent. Should be Group Exercises under Fitness.'),
    ];

    $form['locations'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Locations Mapping'),
      '#default_value' => $config->get('locations'),
      '#description' => t('One per line. Format: GroupExPro ID, Name (as Branch in Drupal). Example: 202,West YMCA'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = \Drupal::service('config.factory')->getEditable('openy_gxp.settings');

    $config->set('client_id', $form_state->getValue('client_id'))->save();
    $config->set('activity', $form_state->getValue('activity'))->save();
    $config->set('locations', $form_state->getValue('locations'))->save();

    parent::submitForm($form, $form_state);
  }

}
