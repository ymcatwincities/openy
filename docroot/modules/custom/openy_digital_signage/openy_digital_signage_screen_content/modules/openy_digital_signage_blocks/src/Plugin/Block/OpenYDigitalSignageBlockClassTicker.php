<?php

namespace Drupal\openy_digital_signage_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\openy_digital_signage_classes_schedule\OpenYClassesScheduleManagerInterface;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface;
use Drupal\openy_digital_signage_screen\OpenYScreenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Scheduling: class ticker.
 *
 * @Block(
 *   id = "openy_digital_signage_class_ticker",
 *   admin_label = @Translation("Room class ticker"),
 *   category = @Translation("Room Entry")
 * )
 */
class OpenYDigitalSignageBlockClassTicker extends BlockBase implements ContainerFactoryPluginInterface {

  const DEFAULT_PERIOD_LENGTH = 28800;

  /**
   * The Classes Schedule Manager.
   *
   * @var $scheduleManager OpenYClassesScheduleManagerInterface
   */
  protected $scheduleManager;

  /**
   * The Screen Manager.
   *
   * @var \Drupal\openy_digital_signage_screen\OpenYScreenManagerInterface
   */
  protected $screenManager;

  /**
   * OpenYDigitalSignageBlockClassCurrent constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\openy_digital_signage_classes_schedule\OpenYClassesScheduleManagerInterface $schedule_manager
   *   The Open Y DS Classes Schedule Manager.
   * @param \Drupal\openy_digital_signage_screen\OpenYScreenManagerInterface $screen_manager
   *   The Open Y DS Screen Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OpenYClassesScheduleManagerInterface $schedule_manager, OpenYScreenManagerInterface $screen_manager) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->scheduleManager = $schedule_manager;
    $this->screenManager = $screen_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openy_digital_signage_classes_schedule.manager'),
      $container->get('openy_digital_signage_screen.manager')
    );
  }


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
    $attributes->addClass('block-class-ticker');

    $classes = [];
    $period = $this->getSchedulePeriod();
    if ($screen = $this->screenManager->getScreenContext()) {
      if ($room = $this->getRoom($screen)) {
        $location = $screen->field_screen_location->entity;
        $classes = $this->scheduleManager->getClassesSchedule($period, $location, $room);
      }
    }
    else {
      $classes = $this->getDummyClassesSchedule($period);
    }

    $build = [
      '#theme' => 'openy_digital_signage_blocks_class_ticker',
      '#attached' => [
        'library' => [
          'openy_digital_signage_blocks/class_ticker',
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

  /**
   * Retrieves room.
   *
   * @param \Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface $screen
   *   The screen context.
   *
   * @return mixed
   *   The room context.
   */
  private function getRoom(OpenYScreenInterface $screen) {
    $screen_room = $screen->field_screen_room->value;
    $configuration_room = $this->configuration['room'];
    return $screen_room ?: $configuration_room;
  }

  /**
   * Retrieve schedule period.
   *
   * @return array
   *   The schedule period.
   */
  private function getSchedulePeriod() {
    $period = &drupal_static('schedule_item_period', NULL);

    if (isset($period)) {
      return $period;
    }

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

  /**
   * Generates dummy class schedule.
   *
   * @param array $period
   *   Period of time the schedule to be generated.
   *
   * @return array
   *   The generated schedule.
   */
  private function getDummyClassesSchedule($period) {
    $classes = [];
    $time = $period['from'];
    $cnt = 19;
    $duration = ceil(($period['to'] - $period['from']) / ($cnt));
    $break_duration = intval($duration * 4 / 13);
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
