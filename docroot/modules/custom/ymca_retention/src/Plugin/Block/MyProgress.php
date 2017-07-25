<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block with personal progress.
 *
 * @Block(
 *   id = "retention_my_progress_block",
 *   admin_label = @Translation("[YMCA Retention] My Progress"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class MyProgress extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['enable_bonuses'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Bonuses'),
      '#default_value' => isset($config['enable_bonuses']) ? $config['enable_bonuses'] : FALSE,
      '#description' => $this->t('Enable this if the "Bonuses" feature is being used and bonuses will be disabled in the "My Progress" block.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $enable_bonuses = $form_state->getValue('enable_bonuses') ? TRUE : FALSE;
    $this->setConfigurationValue('enable_bonuses', $enable_bonuses);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $block = [
      '#theme' => 'ymca_retention_my_progress',
      '#enable_bonuses' => $config['enable_bonuses'],
      '#attached' => [
        'library' => [
          'ymca_retention/my-progress',
        ],
      ],
    ];

    return $block;
  }

}
