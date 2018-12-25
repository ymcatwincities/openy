<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides form for managing module settings.
 */
class SettingsCopyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_copy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_retention.copy_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_retention.copy_settings');

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];

    // Intro Tab.
    $form['retention_intro'] = [
      '#type' => 'details',
      '#title' => $this->t('Introduction'),
      '#description' => $this->t('All copy elements seen under the "Introduction" tab.'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['entity-form-intro'],
      ],
      '#weight' => 10,
      '#optional' => TRUE,
    ];

    $form['intro_header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header'),
      '#default_value' => $config->get('intro_header'),
      '#description' => $this->t('The heading of the introduction tab.'),
      '#weight' => -99,
      '#group' => 'retention_intro',
    ];

    $form['retention_intro']['intro_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('intro_body')['value'],
      '#format' => $config->get('intro_body')['format'],
      '#description' => $this->t('Copy displayed right after the header.'),
      '#format' => 'full_html',
      '#weight' => -90,
    ];

    $form['intro_reg_btn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration Text'),
      '#default_value' => $config->get('intro_reg_btn'),
      '#description' => $this->t('Text of the registration button.'),
      '#group' => 'retention_intro',
    ];

    // Create 3 info blocks.
    for ($i = 1; $i < 4; $i++) {
      $name = "info_block_{$i}";

      // Intro Tab Info Block.
      $form['retention_intro'][$name] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Info Block @n', ['@n' => $i]),
        '#attributes' => [
          'class' => ['entity-form-intro-block-' . $i],
        ],
        '#weight' => 10,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form["{$name}_header"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Header'),
        '#default_value' => $config->get("{$name}_header"),
        '#description' => $this->t('The heading of info block @n.', ['@n' => $i]),
        '#weight' => -3,
        '#group' => $name,
      ];

      $form['retention_intro'][$name]["{$name}_copy"] = [
        '#type' => 'text_format',
        '#title' => $this->t('Copy'),
        '#default_value' => $config->get("{$name}_copy")['value'],
        '#format' => $config->get("{$name}_copy")['format'],
        '#description' => $this->t('The copy of info block @n.', ['@n' => $i]),
        '#weight' => -2,
      ];

      $link_type = [
        0 => $this->t('< Select One >'),
        1 => $this->t('Link'),
        2 => $this->t('Tab'),
      ];
      $form['retention_intro'][$name]["{$name}_link_type"] = [
        '#type' => 'select',
        '#title' => t('Link Type'),
        '#default_value' => $config->get("{$name}_link_type"),
        '#options' => $link_type,
        '#description' => t('Select whether the image should be linked to a tab or an internal URL.'),
        '#weight' => -1,
      ];

      $form['retention_intro'][$name]["{$name}_link"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Link'),
        '#default_value' => $config->get("{$name}_link"),
        '#description' => $this->t('The link of info block @n to apply to the image.', ['@n' => $i]),
        '#states' => [
          'visible' => [
            "select[name={$name}_link_type]" => ['value' => 1],
          ],
          'required' => [
            "select[name={$name}_link_type]" => ['value' => 1],
          ],
        ],
        '#weight' => 0,
        '#element_validate' => [[get_called_class(), 'validateUrl']],
      ];

      $tabs = [];
      for ($k = 1; $k < 6; $k++) {
        $tabs[$k] = $k;
      }
      $form['retention_intro'][$name]["{$name}_tab"] = [
        '#type' => 'select',
        '#title' => $this->t('Tab'),
        '#default_value' => $config->get("{$name}_tab"),
        '#options' => $tabs,
        '#description' => $this->t('The tab of info block @n to apply to the image.', ['@n' => $i]),
        '#states' => [
          'visible' => [
            "select[name={$name}_link_type]" => ['value' => 2],
          ],
          'required' => [
            "select[name={$name}_link_type]" => ['value' => 2],
          ],
        ],
        '#weight' => 1,
      ];

      // Use the #managed_file FAPI element to upload an image file.
      $form['retention_intro'][$name]["{$name}_img"] = [
        '#title' => t('Image'),
        '#field_name' => "{$name}_img",
        '#type' => 'managed_file',
        '#description' => t('The uploaded image will be displayed on this page using the image style choosen below.'),
        '#default_value' => $config->get("{$name}_img"),
        '#upload_validators'  => [
          'file_validate_extensions' => ['gif png jpg jpeg'],
          'file_validate_size' => [25600000],
        ],
        '#upload_location' => 'public://ymca_retention/',
        '#weight' => 2,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form element validation handler for #type 'url'.
   *
   * Note that #maxlength and #required is validated by
   * _form_validate() already.
   */
  public static function validateUrl(&$element, FormStateInterface $form_state, &$complete_form) {
    if (empty($element['#value'])) {
      return;
    }
    if (strpos($element['#value'], '/') !== 0) {
      $form_state->setError($element, t('The "Link" field needs to be an internal URL that begins with a leading "/".'));
      return;
    }
    $value = 'internal:' . trim($element['#value']);
    $url = Url::fromUri($value);
    if (!$url->isRouted()) {
      $form_state->setError($element, t('The URL %url is not a valid internal path.', ['%url' => $element['#value']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = \Drupal::currentUser()->id();
    $config = $this->config('ymca_retention.copy_settings')
      ->set('intro_header', $form_state->getValue('intro_header'))
      ->set('intro_body', $form_state->getValue('intro_body'))
      ->set('intro_reg_btn', $form_state->getValue('intro_reg_btn'));

    // Create 3 info blocks.
    for ($i = 1; $i < 4; $i++) {
      $name = "info_block_{$i}";
      $fid = $form_state->getValue("{$name}_img");
      $link_type = $form_state->getValue("{$name}_link_type");

      $config
        ->set("{$name}_header", $form_state->getValue("{$name}_header"))
        ->set("{$name}_copy", $form_state->getValue("{$name}_copy"))
        ->set("{$name}_link_type", $link_type)
        ->set("{$name}_img", $fid);

      switch ($link_type) {
        case 1:
          $config
            ->set("{$name}_link", $form_state->getValue("{$name}_link"))
            ->set("{$name}_tab", '');
          break;

        case 2:
          $config
            ->set("{$name}_link", '')
            ->set("{$name}_tab", $form_state->getValue("{$name}_tab"));
          break;

      }

      if (!empty($fid) && is_array($fid)) {
        // Load the file via file.fid.
        $file = file_load($fid[0]);
        // Set as permanent so file is not removed.
        $file->setPermanent();
        $file->save();
        $file_usage = \Drupal::service('file.usage');
        $file_usage->add($file, 'ymca_retention', "{$name}_img", $uid);
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
