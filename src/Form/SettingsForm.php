<?php
/**
 * @file
 * Contains \Drupal\ygs_popups\Form\SettingsForm.
 */

namespace Drupal\ygs_popups\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Settings Form for ygs_popups.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_popups_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ygs_popups.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ygs_popups.settings');

    $form['img'] = [
      '#type' => 'managed_file',
      '#title' => t('Popup image'),
      '#upload_validators'  => [
        'file_validate_is_image' => [],
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [12800000],
      ],
      '#upload_location' => 'public://ygs_popup/',
      '#default_value' => ($config->get('img')) ? [$config->get('img')] : NULL,
    ];
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => 'full_html',
      '#default_value' => ($config->get('description')) ? $config->get('description') : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('ygs_popups.settings');
    if ($config->get('img')) {
      // Delete old image.
      file_delete($config->get('img'));
    }
    // Save image.
    $file = File::load(array_shift(array_values($form_state->getValue('img'))));
    $file->status = FILE_STATUS_PERMANENT;
    $file->save();

    // Set configuration.
    $config->set('img', $file->id())->save();
    $config->set('description', $form_state->getValue('description')['value'])->save();

    parent::submitForm($form, $form_state);
  }

}
