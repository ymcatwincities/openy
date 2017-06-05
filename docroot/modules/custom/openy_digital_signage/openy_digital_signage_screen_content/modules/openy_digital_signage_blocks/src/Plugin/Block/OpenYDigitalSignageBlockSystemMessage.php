<?php

namespace Drupal\openy_digital_signage_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides a System Message Block.
 *
 * @Block(
 *   id = "openy_digital_signage_system_message",
 *   admin_label = @Translation("System Message"),
 *   category = @Translation("Digital Signage")
 * )
 */
class OpenYDigitalSignageBlockSystemMessage extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // By default, the block will be placed in the left top corner.
    return [
      'icon' => 'hi',
      'message' => 'Default message',
      'color_scheme' => 'orange',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['icon'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon'),
      '#default_value' => $this->configuration['icon'],
      '#options' => [
        'bell' => $this->t('bell'),
        'bullhorn' => $this->t('bullhorn'),
        'thumbs-up' => $this->t('thumbs up'),
        'flash' => $this->t('flash'),
        'cutlery' => $this->t('cutlery'),
        'earphone' => $this->t('earphone'),
        'phone-alt' => $this->t('phone'),
        'tree-deciduous' => $this->t('tree'),
      ],
    ];
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $this->configuration['message'],
    ];
    $form['color_scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Color Scheme'),
      '#default_value' => $this->configuration['color_scheme'],
      '#options' => [
        'orange' => $this->t('Orange'),
        'blue' => $this->t('Blue'),
        'purple' => $this->t('Purple'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['icon'] = $form_state->getValue('icon');
    $this->configuration['message'] = $form_state->getValue('message');
    $this->configuration['color_scheme'] = $form_state->getValue('color_scheme');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $attributes = new Attribute();
    $attributes->addClass('block-system-message');
    $attributes->addClass('block-system-message-icon-' . $this->configuration['icon']);
    $attributes->addClass('block-system-message-color-' . $this->configuration['color_scheme']);

    $build = [
      '#theme' => 'openy_digital_signage_blocks_system_message',
      '#attached' => [
        'library' => [
          'openy_digital_signage_blocks/system_message',
        ],
      ],
      '#message' => check_markup($this->configuration['message'], 'inline_html'),
      '#icon' => $this->configuration['icon'],
      '#wrapper_attributes' => $attributes,
    ];

    return $build;
  }

}
