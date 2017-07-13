<?php

namespace Drupal\openy_digital_signage_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreen;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface;

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

    $classes = [];
    $period = $this->getSchedulePeriod();
    if ($screen = $this::getScreenContext()) {
      if ($room = $this->getRoom($screen)) {
        $classes = $this->getClassesSchedule($screen, $period, $room);
      }
    }
    else {
      $classes = $this->getDummyClassesSchedule($period);
    }

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

  private function getRoom(OpenYScreenInterface $screen) {
    $screen_room = $screen->field_screen_room->value;
    $configuration_room = $this->configuration['room'];
    return $screen_room ?: $configuration_room;
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
      return [
        'from' => time(),
        'to' => time() + $this::DEFAULT_PERIOD_LENGTH,
      ];
    }

    return $period;
  }

  private static function getClassesSchedule(OpenYScreenInterface $screen, $period, $room) {
    $location = $screen->field_screen_location->entity;

    $period_to = date('c', $period['to']);
    $period_from = date('c', $period['from']);
    $eq = \Drupal::entityQuery('openy_ds_classes_session');
    $eq->condition('room_name', $room);
    $eq->condition('field_session_location', $location->id());
    $eq->condition('date_time.value', $period_to, '<=');
    $eq->condition('date_time.end_value', $period_from, '>=');
    $eq->sort('date_time.value');
    $results = $eq->execute();

    $storage = \Drupal::entityTypeManager()->getStorage('openy_ds_classes_session');

    $class_sessions = $storage->loadMultiple($results);

    $classes = [];
    foreach ($class_sessions as $class_session) {
      $from = strtotime($class_session->date_time->value . 'z');
      $to = strtotime($class_session->date_time->end_value . 'z');
      $classes[] = [
        'from' => $from,
        'to' => $to,
        'trainer' => $class_session->instructor->value,
        'substitute_trainer' => trim($class_session->sub_instructor->value),
        'name' => $class_session->label(),
        'from_formatted' => date('H:ia', $from),
        'to_formatted' => date('H:ia', $to),
      ];
    }

    return $classes;
  }

  private function getDummyClassesSchedule($period) {
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

  private static function getScreenContext() {
    $route_name = \Drupal::routeMatch()->getRouteName();
    $request = \Drupal::request();
    if ($route_name == 'entity.openy_digital_signage_screen.canonical') {
      $screen = $request->get('openy_digital_signage_screen');
      return $screen;
    }
    else {
      $request = \Drupal::request();
      if ($request->query->has('screen')) {
        $storage = \Drupal::entityTypeManager()->getStorage('openy_digital_signage_screen');
        $screen = $storage->load($request->query->get('screen'));
        return $screen;
      }
    }

    return NULL;
  }

}
