<?php

namespace Drupal\openy_font\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FontSettingsForm.
 *
 * @package Drupal\openy_font\Form
 */
class FontSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_font.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_font_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_font.settings');

    $form['#title'] = $this->t('OpenY Font Settings');

    if (\Drupal::service('module_handler')->moduleExists('fontyourface')) {
      $message = $this->t('
        OpenY Font module was replaced in favor of <a href=":href">FontYourFace</a> module. 
        Please, <a href=":custom">use it</a> to add purchased fonts to the site and uninstall OpenY Font module.', [
          ':href' => 'https://www.drupal.org/project/fontyourface',
          ':custom' => '/admin/appearance/font/local_font_config_entity']);
      $form['warning'] = [
        '#markup' => '<div class="messages messages--warning">' . $message . '</div>',
      ];
    }

    $form['intro'] = [
      '#markup' => '<p>' . $this->t('To use Cachet font (Bold, Book, Medium), you must purchase a license from fonts.com. <ol><li>Purchase appropriate font on <a href=":link" target="_blank">fonts.com</a>.</li><li>Download archive with fonts.</li><li>Unarchive fonts.</li><li>Upload fonts into appropriate fields (field name should match to font name).</li></ol>Upon installation, uploaded fonts will be used on the OpenY website.', [
          ':link' => 'https://www.fonts.com/font/monotype/cachet',
        ]) . '</p>',
    ];

    $form['cachet_bold'] = [
      '#type' => 'managed_file',
      '#field_name' => 'cachet_bold',
      '#title' => $this->t('Cachet Bold'),
      '#description' => $this->t('Upload Cachet Bold here. File name should be <strong>Cachet-Bold.ttf</strong>.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['ttf'],
        'openy_font_file_validation' => ['bold'],
      ],
      '#upload_location' => 'public://fonts/',
      '#default_value' => $config->get('cachet_bold'),
    ];

    $form['cachet_book'] = [
      '#type' => 'managed_file',
      '#field_name' => 'cachet_book',
      '#title' => $this->t('Cachet Book'),
      '#description' => $this->t('Upload Cachet Book here. File name should be <strong>Cachet-Book.ttf</strong>.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['ttf'],
        'openy_font_file_validation' => ['book'],
      ],
      '#upload_location' => 'public://fonts/',
      '#default_value' => $config->get('cachet_book'),
    ];

    $form['cachet_medium'] = [
      '#type' => 'managed_file',
      '#field_name' => 'cachet_medium',
      '#title' => $this->t('Cachet Medium'),
      '#description' => $this->t('Upload Cachet Medium here. File name should be <strong>Cachet-Medium.ttf</strong>.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['ttf'],
        'openy_font_file_validation' => ['medium'],
      ],
      '#upload_location' => 'public://fonts/',
      '#default_value' => $config->get('cachet_medium'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('openy_font.settings')
      ->set('cachet_bold', $form_state->getValue('cachet_bold'))
      ->set('cachet_book', $form_state->getValue('cachet_book'))
      ->set('cachet_medium', $form_state->getValue('cachet_medium'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
