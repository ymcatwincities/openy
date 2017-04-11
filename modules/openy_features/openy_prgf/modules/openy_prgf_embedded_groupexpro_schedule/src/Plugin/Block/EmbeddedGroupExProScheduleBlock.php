<?php

namespace Drupal\openy_prgf_embedded_groupexpro_schedule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block with Embedded GroupEx Pro Schedule.
 *
 * @Block(
 *   id = "embedded_groupexpro_schedule_block",
 *   admin_label = @Translation("Embedded GroupEx Pro Schedule"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class EmbeddedGroupExProScheduleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['account'] = 611;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account'),
      '#default_value' => isset($config['account']) ? $config['account'] : '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['account'] = $form_state->getValue('account');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // TODO: investigate why configuration is not saved when editing block
    // settings as paragraph field; remove $default_config usage.
    $default_config = $this->defaultConfiguration();
    return [
      [
        '#type' => 'embedded_groupexpro_schedule',
        '#account' => $config['account'] ? $config['account'] : $default_config['account'],
      ],
    ];
  }

}
