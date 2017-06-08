<?php

namespace Drupal\openy_digital_signage_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides a Block with the current time.
 *
 * @Block(
 *   id = "openy_digital_signage_block_time",
 *   admin_label = @Translation("Current time block"),
 *   category = @Translation("Digital Signage")
 * )
 */
class OpenYDigitalSignageBlockTime extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // By default, the block will be placed in the left top corner.
    return [
      'position_y' => 'top',
      'position_x' => 'right',
      'color' => 'white',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['position_y'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the Y position on the screen'),
      '#default_value' => $this->configuration['position_y'],
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
    ];
    $form['position_x'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the X position on the screen'),
      '#default_value' => $this->configuration['position_x'],
      '#options' => [
        'right' => $this->t('Right'),
        'left' => $this->t('Left'),
      ],
    ];
    $form['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color Schema'),
      '#default_value' => $this->configuration['color'],
      '#options' => [
        'white' => $this->t('White'),
        'black' => $this->t('Black'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['position_y'] = $form_state->getValue('position_y');
    $this->configuration['position_x'] = $form_state->getValue('position_x');
    $this->configuration['color'] = $form_state->getValue('color');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $y = $this->configuration['position_y'];
    $x = $this->configuration['position_x'];
    $attributes = new Attribute();
    $attributes->addClass('block-time');
    $attributes->addClass('block-time-color-' . $this->configuration['color']);
    $attributes->addClass('block-time-position-' . $y . '-' . $x);
    $attributes->setAttribute('id', 'openy-digital-signage-block-time');

    $build = [
      '#theme' => 'openy_digital_signage_block_time',
      '#attached' => [
        'library' => [
          'openy_digital_signage_block_time/block_time',
        ],
      ],
      '#current_time' => date('h:i a'),
      '#wrapper_attributes' => $attributes,
    ];

    return $build;
  }

}
