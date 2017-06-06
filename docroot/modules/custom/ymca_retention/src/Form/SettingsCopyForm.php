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

      $form['retention_intro'][$name]["{$name}_link"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Link'),
        '#default_value' => $config->get("{$name}_link"),
        '#description' => $this->t('The link of info block @n to apply to the image.', ['@n' => $i]),
        '#weight' => -1,
        '#element_validate' => [[get_called_class(), 'validateUrl']],
      ];

      // Use the #managed_file FAPI element to upload an image file.
      $form['retention_intro'][$name]["{$name}_img"] = [
        '#title' => t('Image'),
        '#field_name' => "{$name}_img",
        '#type' => 'managed_file',
        '#description' => t('The uploaded image will be displayed on this page using the image style choosen below.'),
        '#default_value' => $config->get("{$name}_img"),
        // '#default_value' => ['36595'],
        '#upload_validators'  => [
          'file_validate_extensions' => ['gif png jpg jpeg'],
          'file_validate_size' => [25600000],
        ],
        '#upload_location' => 'public://ymca_retention/',
        '#weight' => 0,
      ];
    }

    // $form['date_registration_close'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Registration close date and time'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('date_registration_close'),
    //   '#description' => $this->t('Date and time when registration will be closed.'),
    // ];

    // $form['date_reporting_open'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Reporting open date and time'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('date_reporting_open'),
    //   '#description' => $this->t('Date and time when reporting will be open.'),
    // ];

    // $form['date_reporting_close'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Reporting close date and time'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('date_reporting_close'),
    //   '#description' => $this->t('Date and time when reporting will be closed.'),
    // ];

    // $form['date_winners_announcement'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Winners announcement date and time'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('date_winners_announcement'),
    //   '#description' => $this->t('Date and time when winners will be announced.'),
    // ];

    // $form['calculate_visit_goal'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Calculate visit goal'),
    //   '#default_value' => $config->get('calculate_visit_goal'),
    // ];

    // $form['new_member_goal_number'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Goal of visits for new members'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('new_member_goal_number'),
    //   '#description' => $this->t('Goal of visits in this campaign for new members.'),
    // ];

    // $form['limit_goal_number'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Limit goal of visits'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('limit_goal_number'),
    //   '#description' => $this->t('Limit goal of visits in this campaign.'),
    // ];

    // $form['date_checkins_start'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Check-ins start date and time'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('date_checkins_start'),
    //   '#description' => $this->t('Start date and time, for getting data about check-ins in past months, before the campaign starts.'),
    // ];

    // $form['date_checkins_end'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Check-ins end date and time'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('date_checkins_end'),
    //   '#description' => $this->t('End date and time, for getting data about check-ins in past months, before the campaign starts.'),
    // ];

    // $form['recent_winners_limit'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Recent winners limit'),
    //   '#size' => 50,
    //   '#maxlength' => 500,
    //   '#default_value' => $config->get('recent_winners_limit'),
    //   '#description' => $this->t('How many winners to show in the recent winners block.'),
    // ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form element validation handler for #type 'url'.
   *
   * Note that #maxlength and #required is validated by _form_validate() already.
   */
  public static function validateUrl(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#value'] !== '') {
      if (strpos($element['#value'], '/') !== 0) {
        $form_state->setError($element, t('The "Link" field needs to be an internal URL that begins with a leading "/".'));
        return;
      }
      $value = 'internal:' . trim($element['#value']);
      $url = Url::fromUri($value);
      if (!$url->isRouted()) {
        $form_state->setError($element, t('The URL %url is not a valid internal path.', array('%url' => $element['#value'])));
      }
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
      $config
        ->set("{$name}_header", $form_state->getValue("{$name}_header"))
        ->set("{$name}_copy", $form_state->getValue("{$name}_copy"))
        ->set("{$name}_link", $form_state->getValue("{$name}_link"))
        ->set("{$name}_img", $fid);

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
