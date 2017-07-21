<?php

namespace Drupal\openy_digital_signage_screen\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\node\NodeInterface;
use Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleItemInterface;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface;
use Drupal\panels\CachedValuesGetterTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route controllers for Screen schedule routes.
 */
class OpenYScreenSchedule extends ControllerBase {

  use AjaxFormTrait;
  use CachedValuesGetterTrait;

  const LEFT = 'left';
  const RIGHT = 'right';

  /**
   * Generates Screen Schedule manage page contents.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface $openy_digital_signage_screen
   *   The Digital Signage Screen entity.
   *
   * @return array
   *   Page build array.
   */
  public function schedulePage(Request $request, OpenYScreenInterface $openy_digital_signage_screen) {
    // Add a section containing the available blocks to be added to the variant.
    $build = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
          'openy_digital_signage_screen/openy_ds_screen_schedule',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'screen_schedule_ui',
    ];

    $schedule_entity = $openy_digital_signage_screen->screen_schedule->entity;
    $schedule_manager = \Drupal::service('openy_digital_signage_schedule.manager');
    $now = strtotime('today');
    $schedule = $schedule_manager->getUpcomingScreenContents($schedule_entity, 86400, $now, TRUE);

    $build['#schedule'] = [
      '#type' => 'container',
      '#theme' => 'screen_schedule_timeline',
      '#screen' => $openy_digital_signage_screen,
      '#schedule' => $schedule,
      '#year' => date('Y', $now),
      '#month' => date('n', $now),
      '#day' => date('j', $now),
    ];
    $build['#schedule']['#calendar'] = [
      '#type' => 'container',
      '#theme' => 'screen_schedule_calendar',
      '#year' => date('Y', $now),
      '#month' => date('n', $now),
      '#day' => date('j', $now),
      '#overrides' => [],
      '#screen' => $openy_digital_signage_screen,
    ];
    $build['#data'] = [
      '#type' => 'markup',
      '#markup' => '',
    ];

    return $build;
  }

  /**
   * Defines Screen Schedule manage page title.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface $openy_digital_signage_screen
   *   The Digital Signage Screen entity.
   *
   * @return string
   *   Formatted title.
   */
  public function scheduleTitle(Request $request, OpenYScreenInterface $openy_digital_signage_screen) {
    return $this->t('@label – manage schedule', ['@label' => $openy_digital_signage_screen->label()]);
  }

  /**
   * Adds AJAX commands to a response object in order to output content.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The response object.
   * @param string $side
   *   The side name ("left" or "right").
   * @param mixed $build
   *   The content to be output.
   */
  private function outputToAside(AjaxResponse $response, $side, $build) {
    switch ($side) {
      case 'left':
        $response->addCommand(new RemoveCommand('.screen-schedule-ui--left > *'));
        $response->addCommand(new AppendCommand('.screen-schedule-ui--left', $build));
        break;

      case 'right':
        $response->addCommand(new RemoveCommand('.screen-schedule-ui--right > *'));
        $response->addCommand(new AppendCommand('.screen-schedule-ui--right', $build));
        break;
    }
  }

  /**
   * Gets a schedule item add form.
   *
   * @param OpenYScreenInterface $screen
   *   The Screen to whose a new schedule item must be added.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  public function addScheduleItem(OpenYScreenInterface $screen) {
    // Build a new Schedule item form.
    $schedule_item = \Drupal::entityTypeManager()
      ->getStorage('openy_digital_signage_sch_item')
      ->create([
        'title' => 'New schedule item',
        'schedule' => $screen->screen_schedule->entity->id(),
      ]);

    $form = \Drupal::entityTypeManager()
      ->getFormObject('openy_digital_signage_sch_item', 'default')
      ->setEntity($schedule_item);
    $build = \Drupal::formBuilder()->getForm($form);

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $this->outputToAside($response, $this::RIGHT, $build);
    return $response;
  }

  /**
   * Gets a schedule item edit form.
   *
   * @param OpenYScheduleItemInterface $schedule_item
   *   The Schedule item.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  public function editScheduleItem(OpenYScheduleItemInterface $schedule_item) {
    // Build an edit Schedule item form.
    $form = \Drupal::entityTypeManager()
      ->getFormObject('openy_digital_signage_sch_item', 'edit')
      ->setEntity($schedule_item);
    $build = \Drupal::formBuilder()->getForm($form);

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $this->outputToAside($response, $this::RIGHT, $build);
    return $response;
  }

  /**
   * Gets a markup for showing the schedule item conten.
   *
   * @param OpenYScreenInterface $screen
   *   The Screen entity.
   * @param OpenYScheduleItemInterface $schedule_item
   *   The Schedule item entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  public function viewScheduleItem(Request $request, OpenYScreenInterface $screen, OpenYScheduleItemInterface $schedule_item) {
    $screen_content = $schedule_item->content->entity;

    $from = $request->query->has('from') ? $request->query->get('from') : time();
    $to = $request->query->has('to') ? $request->query->get('to') : time() + 8 * 3600;

    // Build an edit Schedule item form.
    $src = Url::fromRoute('entity.node.canonical', [
      'node' => $screen_content->id(),
      'from' => $from,
      'to' => $to,
      'screen' => $screen->id(),
    ])->toString();
    $build = [
      '#type' => 'container',
      '#tag' => 'div',
      '#attributes' => [
        'data-src' => $src,
        'class' => ['frame-container'],
      ],
    ];

    if ($screen->orientation->value == 'portrait') {
      $build['#attributes']['class'][] = 'frame-container--portrait';
    }

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $this->outputToAside($response, $this::RIGHT, $build);
    return $response;
  }

  /**
   * Gets a markup for showing the schedule item conten.
   *
   * @param OpenYScreenInterface $screen
   *   The Screen entity.
   * @param NodeInterface $screen_content
   *   The Screen content entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  public function viewScreenContent(OpenYScreenInterface $screen, NodeInterface $screen_content) {
    // Build an edit Schedule item form.
    $build = [
      '#type' => 'container',
      '#tag' => 'div',
      '#attributes' => [
        'data-src' => Url::fromRoute('entity.node.canonical', ['node' => $screen_content->id()])
          ->toString(),
        'class' => ['frame-container'],
      ],
    ];

    if ($screen->orientation->value == 'portrait') {
      $build['#attributes']['class'][] = 'frame-container--portrait';
    }

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $this->outputToAside($response, $this::RIGHT, $build);
    return $response;
  }

  /**
   * Gets a markup for Screen schedule timeline.
   *
   * @param OpenYScreenInterface $screen
   *   The Screen entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  public function redrawTimeline(OpenYScreenInterface $screen, $year = NULL, $month = NULL, $day = NULL) {
    if (!isset($year, $month, $day)) {
      $year = date('Y', $_SERVER[REQUEST_TIME]);
      $month = date('m', $_SERVER[REQUEST_TIME]);
      $day = date('d', $_SERVER[REQUEST_TIME]);
      $now = strtotime('today');
    }
    else {
      $now = strtotime(sprintf('%d-%02d-%02d', $year, $month, $day));
    }

    $schedule_entity = $screen->screen_schedule->entity;
    // Move to constructor or make method static.
    $schedule_manager = \Drupal::service('openy_digital_signage_schedule.manager');
    $schedule = $schedule_manager->getUpcomingScreenContents($schedule_entity, 86400, $now);

    $build = [
      '#type' => 'container',
      '#theme' => 'screen_schedule_timeline',
      '#screen' => $screen,
      '#schedule' => $schedule,
      '#year' => $year,
      '#month' => $month,
      '#day' => $day,
    ];
    $build['#calendar'] = [
      '#type' => 'container',
      '#theme' => 'screen_schedule_calendar',
      '#year' => $year,
      '#month' => $month,
      '#day' => $day,
      '#overrides' => [],
      '#screen' => $screen,
    ];

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $this->outputToAside($response, $this::LEFT, $build);
    return $response;
  }

}
