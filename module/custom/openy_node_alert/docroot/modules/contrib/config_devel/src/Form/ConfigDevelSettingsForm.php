<?php

/**
 * @file
 * Contains \Drupal\config_devel\Form\ConfigDevelSettingsForm.
 */

namespace Drupal\config_devel\Form;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for config devel.
 */
class ConfigDevelSettingsForm extends ConfigFormBase {

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'config_devel.settings';

  /**
   * @var array
   */
  protected $keys;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_value = '';
    foreach ($this->config(static::CONFIGNAME)->get('auto_import') as $file) {
      $default_value .= $file['filename'] . "\n";
    }
    $form['auto_import'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Auto import'),
      '#default_value' => $default_value,
      '#description' => $this->t('When these files change, they will be automatically imported at the beginning of the next request. List one file per line.'),
    );
    $form['auto_export'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Auto export'),
      '#default_value' => implode("\n", $this->config(static::CONFIGNAME)->get('auto_export')),
      '#description' => $this->t('Automatically export to the files specified. List one file per line.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach (array('auto_import', 'auto_export') as $key) {
      $form_state->setValue($key, array_filter(preg_split("/\r\n/", $form_state->getValues()[$key])));
    }
    foreach ($form_state->getValues()['auto_import'] as $file) {
      $name = basename($file, '.' . FileStorage::getFileExtension());
      if (in_array($name, array('system.site', 'core.extension', 'simpletest.settings'))) {
        $form_state->setErrorByName('auto_import', $this->t('@name is not compatible with this module', array('@name' => $name)));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $auto_import = array();
    foreach ($form_state->getValues()['auto_import'] as $file) {
      $auto_import[] = array(
        'filename' => $file,
        'hash' => '',
      );
    }
    $this->config(static::CONFIGNAME)
      ->set('auto_import', $auto_import)
      ->set('auto_export', $form_state->getValues()['auto_export'])
      ->save();
    parent::submitForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_devel_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config_devel.settings'];
  }

}
