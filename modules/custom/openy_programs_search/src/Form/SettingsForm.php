<?php

namespace Drupal\openy_programs_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for openy_programs_search.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_programs_search_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_programs_search.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_programs_search.settings');

    $form['client_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => t('Add your Daxko account id here. It is most likely a short number, like 1234.'),
    );

    $form['base_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Base URL'),
      '#default_value' => $config->get('base_url'),
      '#element_validate' => [['UrlHelper', 'isValid']],
      '#description' => t('Add your Daxko base url here. It is most likely https://operations.daxko.com.'),
    );

    $form['registration_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Registration Path'),
      '#default_value' => $config->get('registration_link'),
      '#description' => t('Add your Daxko registration path. Something like /Online/{{ client_id }}/Programs/Search.mvc/details. Where the path will be prefixed by your Daxko Base URL and the {{ client_id }} token will be replaced by your Daxko Client ID.'),
    ];

    $form['get_schools_by_program_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Get Schools By Program Path'),
      '#default_value' => $config->get('get_schools_by_program_path'),
      '#description' => t('Add your Daxko scrape schools by program path. Something like /Online/{{ client_id }}/Programs/ChildCareSearch.mvc/locations_by_program?program_id={{ program_id }}. Where the path will be prefixed by your Daxko Base URL, the {{ client_id }} token will be replaced by your Daxko Client ID, and {{ program_id }} will be replaced by program IDs.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = \Drupal::service('config.factory')->getEditable('openy_programs_search.settings');

    $config->set('client_id', $form_state->getValue('client_id'))->save();

    if ($base_url = $form_state->getValue('base_url')) {
      if (preg_match("#https?://#", $base_url) === 0) {
        $base_url = 'https://' . $base_url;
      }
      $config->set('base_url', $base_url)->save();
    }

    $config->set('registration_link', $form_state->getValue('registration_link'))
      ->save();
    $config->set('get_schools_by_program_path', $form_state->getValue('get_schools_by_program_path'))
      ->save();


    parent::submitForm($form, $form_state);
  }

}
