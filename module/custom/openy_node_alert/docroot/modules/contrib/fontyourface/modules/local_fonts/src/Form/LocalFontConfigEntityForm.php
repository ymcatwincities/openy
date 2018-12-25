<?php

namespace Drupal\local_fonts\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Class LocalFontConfigEntityForm.
 *
 * @package Drupal\local_fonts\Form
 */
class LocalFontConfigEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $local_font_config_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $local_font_config_entity->label(),
      '#description' => $this->t("Name of the Custom Font. Note that while Font Family is not necessarily unique, this name is."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $local_font_config_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\local_fonts\Entity\LocalFontConfigEntity::load',
      ],
      '#disabled' => !$local_font_config_entity->isNew(),
    ];

    $form['font_family'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font family'),
      '#default_value' => (isset($local_font_config_entity->font_family)) ? $local_font_config_entity->font_family : '',
      '#description' => $this->t('The CSS Font Family. The @font-face name will be based on this.'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['font_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Style'),
      '#options' => [
        'normal' => $this->t('Normal'),
        'italic' => $this->t('Italics'),
      ],
      '#default_value' => (isset($local_font_config_entity->font_style)) ? $local_font_config_entity->font_style : 'normal',
    ];

    $form['font_weight'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Weight'),
      '#options' => [
        '100' => '100',
        '200' => '200',
        '300' => '300',
        '400' => $this->t('400 (normal)'),
        '500' => '500',
        '600' => '600',
        '700' => $this->t('700 (bold)'),
        '800' => '800',
        '900' => '900',
      ],
      '#default_value' => (isset($local_font_config_entity->font_weight)) ? $local_font_config_entity->font_weight : '400',
    ];

    $form['font_classification'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Font Classification'),
      '#description' => $this->t('This is mostly useful for filtering.'),
      '#options' => [
        'serif' => $this->t('Serif'),
        'sans-serif' => $this->t('Sans Serif'),
        'slab-serif' => $this->t('Slab Serif'),
        'handwriting' => $this->t('Handwriting'),
        'decorative' => $this->t('Decorative'),
        'monospace' => $this->t('Monospace'),
      ],
      '#default_value' => (isset($local_font_config_entity->font_classification)) ? $local_font_config_entity->font_classification : [],
    ];

    $form['font_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Font File'),
      '#description' => $this->t('The font file must be in WOFF format since that is accepted by all modern browsers.'),
      '#size' => 50,
      '#upload_validators' => [
        'file_validate_extensions' => ['woff'],
        'file_validate_size' => [file_upload_max_size()],
        'file_validate_name_length' => [],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $local_font_config_entity = $this->entity;

    $values = $form_state->getValues();
    if (empty($values['font_file']) && empty($local_font_config_entity->getFontWoffData())) {
      $form_state->setErrorByName('font_file', $this->t('WOFF file must be uploaded'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save Custom Font Config Entity.
    $local_font_config_entity = $this->entity;
    if (!empty($values['font_file'])) {
      // Get contents of Font File.
      $font_file = File::load($values['font_file'][0]);
      $font_file_data = base64_encode(file_get_contents($font_file->getFileUri()));
      $local_font_config_entity->setFontWoffData($font_file_data);
    }
    $status = $local_font_config_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Custom Font.', [
          '%label' => $local_font_config_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Custom Font.', [
          '%label' => $local_font_config_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($local_font_config_entity->urlInfo('collection'));
  }

}
