<?php

namespace Drupal\file_entity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FileSettingsForm
 * @package Drupal\file_entity\Form
 */
class FileSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'file_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'file_entity.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['max_filesize'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum upload size'),
      '#default_value' => \Drupal::config('file_entity.settings')->get('max_filesize'),
      '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current max limit <strong>%limit</strong>).', array('%limit' => format_size(file_upload_max_size()))),
      '#element_validate' => ['\Drupal\file\Plugin\Field\FieldType\FileItem::validateMaxFilesize'],
      '#size' => 10,
    );

    $form['default_allowed_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Default allowed file extensions'),
      '#default_value' => \Drupal::config('file_entity.settings')->get('default_allowed_extensions'),
      '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => ['\Drupal\file\Plugin\Field\FieldType\FileItem::validateExtensions'],
      '#maxlength' => NULL,
    );

    $form['file_entity_alt'] = array(
      '#type' => 'textfield',
      '#title' => t('Alt attribute'),
      '#description' => t('The text to use as value for the <em>img</em> tag <em>alt</em> attribute.'),
      '#default_value' => \Drupal::config('file_entity.settings')->get('alt'),
    );

    $form['file_entity_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title attribute'),
      '#description' => t('The text to use as value for the <em>img</em> tag <em>title</em> attribute.'),
      '#default_value' => \Drupal::config('file_entity.settings')->get('title'),
    );

    // Provide default token values.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_help'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => array('file'),
      );
      $form['file_entity_alt']['#description'] .= t('This field supports tokens. Default tokens depend on the <a href=":token">Token module</a> to work correctly. The ":value" version of the token (just raw value, no markup) should be used for performance and to avoid theme issues.', [':token' => 'https://drupal.org/project/token']);
      $form['file_entity_title']['#description'] .= t('This field supports tokens. Default tokens depend on the <a href=":token">Token module</a> to work correctly. The ":value" version of the token (just raw value, no markup) should be used for performance and to avoid theme issues', [':token' => 'https://drupal.org/project/token']);
    }

    $form['file_upload_wizard'] = array(
      '#type' => 'fieldset',
      '#title' => t('File upload wizard'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('Configure the steps available when uploading a new file.'),
    );

    $form['file_upload_wizard']['wizard_skip_file_type'] = array(
      '#type' => 'checkbox',
      '#title' => t('Skip filetype selection.'),
      '#default_value' => \Drupal::config('file_entity.settings')->get('wizard_skip_file_type'),
      '#description' => t('The file type selection step is only available if the uploaded file falls into two or more file types. If this step is skipped, files with no available file type or two or more file types will not be assigned a file type.'),
    );

    $form['file_upload_wizard']['wizard_skip_scheme'] = array(
      '#type' => 'checkbox',
      '#title' => t('Skip scheme selection.'),
      '#default_value' => \Drupal::config('file_entity.settings')->get('wizard_skip_scheme'),
      '#description' => t('The scheme selection step is only available if two or more file destinations, such as public local files served by the webserver and private local files served by Drupal, are available. If this step is skipped, files will automatically be saved using the default download method.'),
    );

    $form['file_upload_wizard']['wizard_skip_fields'] = array(
      '#type' => 'checkbox',
      '#title' => t('Skip available fields.'),
      '#default_value' => \Drupal::config('file_entity.settings')->get('wizard_skip_fields'),
      '#description' => t('The field selection step is only available if the file type the file belongs to has any available fields. If this step is skipped, any fields on the file will be left blank.'),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @TODO: Validation?
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('file_entity.settings')
      ->set('max_filesize', $form_state->getValue('max_filesize'))
      ->set('default_allowed_extensions', $form_state->getValue('default_allowed_extensions'))
      ->set('alt', $form_state->getValue('file_entity_alt'))
      ->set('title', $form_state->getValue('file_entity_title'))
      ->set('wizard_skip_file_type', $form_state->getValue('wizard_skip_file_type'))
      ->set('wizard_skip_scheme', $form_state->getValue('wizard_skip_scheme'))
      ->set('wizard_skip_fields', $form_state->getValue('wizard_skip_fields'))
      ->save();

    drupal_set_message(t('File Settings have been succesfully saved.'));
  }
}
