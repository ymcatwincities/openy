<?php

namespace Drupal\openy_digital_signage_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides a Scheduling: Room current class.
 *
 * @Block(
 *   id = "openy_digital_signage_class_current",
 *   admin_label = @Translation("Room current class"),
 *   category = @Translation("Room Entry")
 * )
 */
class OpenYDigitalSignageBlockClassCurrent extends BlockBase {

  const DEFAULT_PERIOD_LENGTH = 28800;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // By default, the block will be placed in the left top corner.
    return [
      'room' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['room'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Room'),
      '#default_value' => $this->configuration['room'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['room'] = $form_state->getValue('room');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $attributes = new Attribute();
    $attributes->addClass('block');
    $attributes->addClass('block-class-current');

    $period = $this->getSchedulePeriod();
    $classes = $this->getClassesSchedule($period);

    $build = [
      '#theme' => 'openy_digital_signage_blocks_class_current',
      '#attached' => [
        'library' => [
          'openy_digital_signage_blocks/class_current',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      '#room' => $this->configuration['room'],
      '#classes' => $classes,
      '#wrapper_attributes' => $attributes,
    ];

    return $build;
  }

  private function getSchedulePeriod() {
    $period = &drupal_static('schedule_item_period', NULL);

    if (!isset($period)) {
      if (isset($_GET['from'], $_GET['to'])) {
        return [
          'from' => $_GET['from'],
          'to' => $_GET['to'],
        ];
      }
    }

    return [
      'from' => time(),
      'to' => time() + $this::DEFAULT_PERIOD_LENGTH,
    ];
  }

  private function getClassesSchedule($period) {
    $classes = [];
    $time = $period['from'];
    $cnt = 198;
    $duration = ceil(($period['to'] - $period['from']) / ($cnt));
    $break_duration = intval($duration * 10 / 13);
    $duration -= $break_duration;
    for ($i = 0; $i < $cnt; $i++) {
      $from = $time;
      $to = $from + $duration;
      $time = $to + $break_duration;
      $classes[] = [
        'from' => $from,
        'to' => $to,
        'trainer' => 'Nichole C.',
        'substitute_trainer' => rand(0, 10) < 5 ? 'Substitute T.' : '',
        'name' => 'OULA® Dance Fitness',
        'from_formatted' => date('H:ia', $from),
        'to_formatted' => date('H:ia', $to),
      ];
    }

    return $classes;
  }

}
