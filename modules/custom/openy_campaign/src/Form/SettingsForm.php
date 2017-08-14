<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for managing module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openy_campaign.general_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_campaign.general_settings');

    $form['date_winners_announcement'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Winners announcement date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_winners_announcement'),
      '#description' => $this->t('Date and time when winners will be announced.'),
    ];

    $form['calculate_visit_goal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Calculate visit goal'),
      '#default_value' => $config->get('calculate_visit_goal'),
    ];

    $form['new_member_goal_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Goal of visits for new members'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('new_member_goal_number'),
      '#description' => $this->t('Goal of visits in this campaign for new members.'),
    ];

    $form['limit_goal_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit goal of visits'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('limit_goal_number'),
      '#description' => $this->t('Limit goal of visits in this campaign.'),
    ];

    $form['recent_winners_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recent winners limit'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('recent_winners_limit'),
      '#description' => $this->t('How many winners to show in the recent winners block.'),
    ];

    $excludes = $config->get('exclude_reg_product_codes');
    if (!empty($excludes) && is_array($excludes)) {
      $excludes = static::simplifyAllowedValues($config->get('exclude_reg_product_codes'));
    }
    $form['exclude_reg_product_codes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude Registration Product Codes'),
      '#default_value' => $excludes,
      '#description' => $this->t('Enter product codes, one per line. Example Code: <em>14_GETSUMMER</em>'),
    ];

    $form['error_msg_excluded_members'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Excluded Members Error'),
      '#default_value' => $config->get('error_msg_excluded_members')['value'],
      '#format' => $config->get('error_msg_excluded_members')['format'],
      '#description' => $this->t('Message to display if ProductCode is on the excluded list.'),
    ];

    $form['error_msg_incorrect_id'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Error Message: Incorrect Member ID'),
      '#default_value' => $config->get('error_msg_incorrect_id')['value'],
      '#format' => $config->get('error_msg_incorrect_id')['format'],
      '#description' => $this->t('Message to display if user is trying to register with an incorrect member ID.'),
    ];

    $form['error_msg_registered_before_start'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Error Message: Already Registered Before Start'),
      '#default_value' => $config->get('error_msg_registered_before_start')['value'],
      '#format' => $config->get('error_msg_registered_before_start')['format'],
      '#description' => $this->t('Message to display if user is trying to register again before the start of the campaign.'),
    ];

    $form['error_msg_registered_after_start'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Error Message: Already Registered After Start'),
      '#default_value' => $config->get('error_msg_registered_after_start')['value'],
      '#format' => $config->get('error_msg_registered_after_start')['format'],
      '#description' => $this->t('Message to display if user is trying to register again after the start of the campaign.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Checks whether a candidate allowed value is valid.
   *
   * @param string $option
   *   The option value entered by the user.
   */
  protected static function validateAllowedValue($option) {}

  /**
   * Simplifies allowed values to a key-value array from the structured array.
   *
   * @param array $structured_values
   *   Array of items with a 'value' and 'label' key each for the allowed
   *   values.
   *
   * @return string
   *   Formatted back for the textarea.
   */
  protected static function simplifyAllowedValues(array $structured_values) {
    return implode("\n", $structured_values);
  }

  /**
   * Extracts the allowed values array from the allowed_values element.
   *
   * @param string $string
   *   The raw string to extract values from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::allowedValuesString()
   */
  protected static function extractAllowedValues($string) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    $generated_keys = $explicit_keys = FALSE;
    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $explicit_keys = TRUE;
      }
      // Otherwise see if we can use the value as the key.
      elseif (!static::validateAllowedValue($text)) {
        $key = $value = $text;
        $explicit_keys = TRUE;
      }
      else {
        return;
      }

      $values[$key] = $value;
    }

    // We generate keys only if the list contains no explicit key at all.
    if ($explicit_keys && $generated_keys) {
      return;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $excluded_product_codes  = static::extractAllowedValues($form_state->getValue('exclude_reg_product_codes'));
    $this->config('openy_campaign.general_settings')
      ->set('date_winners_announcement', $form_state->getValue('date_winners_announcement'))
      ->set('calculate_visit_goal', $form_state->getValue('calculate_visit_goal'))
      ->set('new_member_goal_number', $form_state->getValue('new_member_goal_number'))
      ->set('limit_goal_number', $form_state->getValue('limit_goal_number'))
      ->set('recent_winners_limit', $form_state->getValue('recent_winners_limit'))
      ->set('exclude_reg_product_codes', $excluded_product_codes)
      ->set('error_msg_excluded_members', $form_state->getValue('error_msg_excluded_members'))
      ->set('error_msg_incorrect_id', $form_state->getValue('error_msg_incorrect_id'))
      ->set('error_msg_registered_before_start', $form_state->getValue('error_msg_registered_before_start'))
      ->set('error_msg_registered_after_start', $form_state->getValue('error_msg_registered_after_start'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
