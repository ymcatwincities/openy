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
        'Y_letter' => $this->t('Y letter'),
        'announcment' => $this->t('Announcment'),
        'aquatics' => $this->t('Aquatics'),
        'baseball' => $this->t('Baseball'),
        'basketball' => $this->t('Basketball'),
        'camp' => $this->t('Camp'),
        'cardio' => $this->t('Cardio'),
        'check' => $this->t('Check'),
        'crunches' => $this->t('Crunches'),
        'date' => $this->t('Date'),
        'dance' => $this->t('Dance'),
        'direction' => $this->t('Direction'),
        'dodgeball' => $this->t('Dodgeball'),
        'donate' => $this->t('Donate'),
        'discount' => $this->t('Discount'),
        'event' => $this->t('Event'),
        'family' => $this->t('Family'),
        'floor_hockey' => $this->t('Hockey'),
        'flag_football' => $this->t('Football'),
        'lacrosse' => $this->t('Lacrosse'),
        'location' => $this->t('Location'),
        'mail' => $this->t('Mail'),
        'martial' => $this->t('Martial'),
        'message' => $this->t('Message'),
        'olympic' => $this->t('Olympic'),
        'prize' => $this->t('Prize'),
        'phone' => $this->t('Phone'),
        'question' => $this->t('Question'),
        'run' => $this->t('Run'),
        'running_club' => $this->t('Running Club'),
        'soccer' => $this->t('Soccer'),
        'strength' => $this->t('Strength'),
        'time' => $this->t('Time'),
        'tip' => $this->t('Tip'),
        'track_and_field' => $this->t('Track and Field'),
        'tumbling' => $this->t('Tumpling'),
        'video' => $this->t('Video'),
        'volleyball' => $this->t('Volleyball'),
        'yoga' => $this->t('Yoga'),
        'zoom' => $this->t('Zoom'),
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
