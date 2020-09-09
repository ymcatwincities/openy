<?php

namespace Drupal\openy_popups\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\openy_popups\Form\BranchesForm;

/**
 * Settings Form for openy_popups.
 */
class SettingsForm extends ConfigFormBase {

  const UPLOAD_LOCATION = 'public://openy_popup/';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_popups_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_popups.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_popups.settings');
    $form = parent::buildForm($form, $form_state);

    $default = $config->get('location');
    
    $form['img'] = [
      '#type' => 'managed_file',
      '#title' => t('Popup image'),
      '#description' => t('File size max 12.8MB'),
      '#upload_validators'  => [
        'file_validate_is_image' => [],
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [12800000],
      ],
      '#upload_location' => self::UPLOAD_LOCATION,
      '#default_value' => ($config->get('img')) ? [$config->get('img')] : NULL,
    ];

    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => 'full_html',
      '#default_value' => ($config->get('description')) ? $config->get('description') : '',
    ];
    $branches_list = BranchesForm::getLocations();
    $form['branch'] = BranchesForm::buildBranch($default, $branches_list);
    $form['branch']['#prefix'] = t('Please select default location');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('openy_popups.settings');
    if ($config->get('img')) {
      // Delete old image.
      \Drupal::service('file_system')->delete($config->get('img'));
    }

    if ($form_image = $form_state->getValue('img')) {
      // Save image.
      $image = array_values($form_image);
      $file = File::load(array_shift($image));
      $file->status = FILE_STATUS_PERMANENT;
      $file->save();

      // Set configuration.
      $config->set('img', $file->id())->save();
    }

    $config->set('description', $form_state->getValue('description')['value'])->save();
    $config->set('location', $form_state->getValue('branch'))->save();

    parent::submitForm($form, $form_state);
  }

}
