<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

/**
 * Class FetcherDebugger.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer
 */
class FetcherDebugger implements FetcherInterface {

  /**
   * Wrapper.
   *
   * @var \Drupal\openy_pef_gxp_sync\syncer\WrapperInterface
   */
  protected $wrapper;

  /**
   * FetcherDebugger constructor.
   *
   * @param \Drupal\openy_pef_gxp_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   */
  public function __construct(WrapperInterface $wrapper) {
    $this->wrapper = $wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $data = [
      4 => [
        [
          'class_id' => '100',
          'category' => 'Strength',
          'location' => 'Andover',
          'title' => 'BodyPumpÂ®',
          'description' => 'BodyPump&trade; is the revolutionary barbell workout. Challenge all major muscle groups with squats, presses, lifts and curls as you strengthen, tone and define your entire body. Determine how hard you want to work by choosing the appropriate weights. Level: All,&nbsp;15+.&nbsp;&nbsp;Free drop-in class for Members.&nbsp;&nbsp;',
          'start_date' => 'September 10, 2012',
          'end_date' => 'September 10, 2037',
          'recurring' => 'weekly',
          'studio' => 'Studio 3',
          'instructor' => 'Test Instructor3',
          'patterns' => [
            'day' => 'Tuesday',
            'start_time' => '08:20',
            'end_time' => '09:15',
          ],
        ],
        [
          'class_id' => '100',
          'category' => 'Strength',
          'location' => 'Andover',
          'title' => 'BodyPumpÂ®',
          'description' => 'BodyPump&trade; is the revolutionary barbell workout. Challenge all major muscle groups with squats, presses, lifts and curls as you strengthen, tone and define your entire body. Determine how hard you want to work by choosing the appropriate weights. Level: All,&nbsp;15+.&nbsp;&nbsp;Free drop-in class for Members.&nbsp;&nbsp;',
          'start_date' => 'September 10, 2012',
          'end_date' => 'September 10, 2037',
          'recurring' => 'weekly',
          'studio' => 'Studio 3',
          'instructor' => 'Test Instructor2',
          'patterns' => [
            'day' => 'Monday',
            'start_time' => '08:20',
            'end_time' => '09:15',
          ],
        ],
      ],
      5 => [
        [
          'class_id' => '100',
          'category' => 'Strength',
          'location' => 'Blaisdell',
          'title' => 'BodyPumpÂ®',
          'description' => 'BodyPump&trade; is the revolutionary barbell workout. Challenge all major muscle groups with squats, presses, lifts and curls as you strengthen, tone and define your entire body. Determine how hard you want to work by choosing the appropriate weights. Level: All,&nbsp;15+.&nbsp;&nbsp;Free drop-in class for Members.&nbsp;&nbsp;',
          'start_date' => 'September 10, 2012',
          'end_date' => 'September 10, 2037',
          'recurring' => 'weekly',
          'studio' => 'Studio 3',
          'instructor' => 'Test Instructor3',
          'patterns' => [
            'day' => 'Tuesday',
            'start_time' => '08:20',
            'end_time' => '09:15',
          ],
        ],
      ],
    ];

    foreach ($data as $locationId => $locationData) {
      $this->wrapper->setSourceData($locationId, $locationData);
    }
  }

}
