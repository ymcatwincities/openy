<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Prizes information block.
 *
 * @Block(
 *   id = "retention_prizes_info_block",
 *   admin_label = @Translation("[YMCA Retention] Prizes Info"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class PrizesInfo extends BlockBase {

  /**
   * The number of prizes.
   *
   * @var PRIZES_COUNT
   */
  const PRIZES_COUNT = 3;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'header' => [
        'value' => 'Get 4 Guest Passes',
        'format' => 'full_html',
      ],
      'sub_header' => [
        'value' => 'Track You Activities',
        'format' => 'full_html',
      ],
    ];

    // Create settings for the number of prizes.
    for ($i = 1; $i <= self::PRIZES_COUNT; $i++) {
      $value = "prize_{$i}";
      $config[$value] = [
        'winners' => '4',
        'copy' => [
          'value' => 'Prize Copy',
          'format' => 'full_html',
        ],
      ];
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['header'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Header'),
      '#default_value' => isset($config['header']) ? $config['header']['value'] : '',
      '#format' => isset($config['header']) ? $config['header']['format'] : 'full_html',
      '#description' => $this->t('Header at the top to possibly promote free passes.'),
    ];

    $form['sub_header'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Sub-Header'),
      '#default_value' => isset($config['sub_header']) ? $config['sub_header']['value'] : '',
      '#format' => isset($config['sub_header']) ? $config['sub_header']['format'] : 'full_html',
      '#description' => $this->t('Header below the promo header, above the prizes.'),
    ];

    // Create settings for the number of prizes.
    for ($i = 1; $i <= self::PRIZES_COUNT; $i++) {
      $value = "prize_{$i}";
      $form[$value] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Prize @n', ['@n' => $i]),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#tree' => TRUE,
      ];

      $form[$value]['winners'] = [
        '#type' => 'number',
        '#title' => $this->t('Prize @n Number of Winners', ['@n' => $i]),
        '#default_value' => isset($config[$value]['winners']) ? $config[$value]['winners'] : '',
        '#description' => $this->t('The amount of winners per branch.'),
      ];

      $form[$value]['copy'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Prize @n Copy', ['@n' => $i]),
        '#default_value' => isset($config[$value]['copy']) ? $config[$value]['copy']['value'] : '',
        '#format' => isset($config[$value]['copy']) ? $config[$value]['copy']['format'] : 'full_html',
        '#description' => $this->t('The copy / graphics for this prize.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('header', $form_state->getValue('header'));
    $this->setConfigurationValue('sub_header', $form_state->getValue('sub_header'));
    // Create settings for prize count.
    for ($i = 1; $i <= self::PRIZES_COUNT; $i++) {
      $value = "prize_{$i}";
      $prize = $form_state->getValue($value);
      if (!empty($prize)) {
        $this->setConfigurationValue($value, $prize);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    // Get retention copy settings.
    $settings = \Drupal::config('ymca_retention.copy_settings');

    $reg_btn = $settings->get('intro_reg_btn');

    // Ensure the 'reg_btn' contains a value.
    if (empty($reg_btn)) {
      $reg_btn = $this->t("Sign Up Now");
    }

    $prizes = [];
    // Create settings for prize count.
    for ($i = 1; $i <= self::PRIZES_COUNT; $i++) {
      $value = "prize_{$i}";
      $prize = $config[$value];
      if (!empty($prize)) {
        $prizes[$value] = $this->getThemedPrize($value, $prize);
      }
    }

    $block = [
      '#theme' => 'ymca_retention_prizes_info',
      '#header' => $this->getThemedTextFormat($config['header']),
      '#sub_header' => $this->getThemedTextFormat($config['sub_header']),
      '#content' => [
        'prizes' => $prizes,
        'reg_btn' => $reg_btn,
      ],
    ];

    return $block;
  }

  /**
   * Helper to format prizes for the theme.
   *
   * This checks an array and returns a theme array.
   *
   * @param string $key
   *   The prize key to indicate the prize title.
   * @param array $prize
   *   A single prize setting to prepare for the theme.
   *
   * @return array|mix
   *   A theme array or empty string if not valid.
   */
  protected function getThemedPrize($key, array $prize) {
    $titles = [
      'prize_1' => $this->t('<span>1</span>st Prize'),
      'prize_2' => $this->t('<span>2</span>nd Prize'),
      'prize_3' => $this->t('<span>3</span>rd Prize'),
      'prize_4' => $this->t('<span>4</span>th Prize'),
      'prize_5' => $this->t('<span>5</span>th Prize'),
    ];
    // Add the prize title.
    if (isset($titles[$key])) {
      $prize['title'] = $titles[$key];
    }

    // Format the text_format field.
    $prize['copy'] = $this->getThemedTextFormat($prize['copy']);

    return $prize;
  }

  /**
   * Helper to format filtered for the theme.
   *
   * This checks an array and returns a theme array.
   *
   * @return array|mix
   *   A theme array or empty string if not valid.
   */
  protected function getThemedTextFormat(array $text_field) {
    if (empty($text_field['value']) || !isset($text_field['format'])) {
      return '';
    }

    return [
      '#type' => 'processed_text',
      '#text' => $text_field['value'],
      '#format' => $text_field['format'],
    ];
  }

}
