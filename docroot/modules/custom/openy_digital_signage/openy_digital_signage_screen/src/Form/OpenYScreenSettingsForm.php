<?php

namespace Drupal\openy_digital_signage_screen\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class OpenYScreenSettingsForm.
 *
 * @package Drupal\openy_digital_signage_screen\Form
 *
 * @ingroup openy_digital_signage_screen
 */
class OpenYScreenSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'OpenYScreen_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openy_digital_signage_screen.default_fallback_content'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('openy_digital_signage_screen.default_fallback_content')
      ->set('target_id', $form_state->getValue('default_fallback_content'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form for OpenY Digital Signage Screen entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['OpenYScreen_settings']['#markup'] = $this->t('Settings form for OpenY Digital Signage Screen entities.');

    $default_value = NULL;
    if ($id = $this->config('openy_digital_signage_screen.default_fallback_content')->get('target_id')) {
      $default_value = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($id);
    }

    $form['default_fallback_content'] = array(
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default screen fallback content'),
      '#target_type' => 'node',
      '#selection_settings' => array(
        'target_bundles' => array('screen_content'),
      ),
      '#default_value' => $default_value,
      '#description' => $this->t('You can create a new Screen content node at @link', ['@link' => Url::fromUserInput('/node/add/screen_content')]),
    );

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

}
