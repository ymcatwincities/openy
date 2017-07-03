<?php

namespace Drupal\openy_digital_signage_screen\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\node\NodeInterface;
use Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleItemInterface;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface;
use Drupal\panels\CachedValuesGetterTrait;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route controllers for Screen schedule routes.
 */
class OpenYScreenSchedule extends ControllerBase {

  use AjaxFormTrait;
  use CachedValuesGetterTrait;

  /**
   * Tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Constructs a new VariantPluginEditForm.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $variant_manager
   *   The variant manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   */
  public function __construct(SharedTempStoreFactory $tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * TODO: Specify
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $openy_digital_signage_screen
   *
   * @return string
   */
  public function schedulePage(Request $request, $openy_digital_signage_screen) {
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
    $schedule = $schedule_manager->getUpcomingScreenContents($schedule_entity, 86400, $now);

    $build['#schedule'] = [
      '#type' => 'container',
      '#theme' => 'screen_schedule_timeline',
      '#screen' => $openy_digital_signage_screen,
      '#schedule' => $schedule,
    ];
    $build['#data'] = [
      '#type' => 'markup',
      '#markup' => '',
    ];

    return $build;
  }

  /**
   * TODO: Specify
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $openy_digital_signage_screen
   *
   * @return string
   */
  public function scheduleTitle(Request $request, $openy_digital_signage_screen) {
    // TODO: implement
    return $openy_digital_signage_screen->label() . ' – manage schedule';// . $openy_digital_signage_screen->screen_schedule->entity->label();
  }

  /**
   * Gets a schedule item add form.
   *
   * @param OpenYScreenInterface $screen
   *   The Screen to whose schedule a new item must be added
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
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
    $response->addCommand(new RemoveCommand('.screen-schedule-ui--right > *'));
    $response->addCommand(new AppendCommand('.screen-schedule-ui--right', $build));
    return $response;
  }

  /**
   * Gets a schedule item edit form.
   *
   * @param OpenYScheduleItemInterface $schedule_item
   *   The Schedule item.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function editScheduleItem(OpenYScheduleItemInterface $schedule_item) {
    // Build an edit Schedule item form.
    $form = \Drupal::entityTypeManager()
      ->getFormObject('openy_digital_signage_sch_item', 'edit')
      ->setEntity($schedule_item);
    $build = \Drupal::formBuilder()->getForm($form);

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand('.screen-schedule-ui--right > *'));
    $response->addCommand(new AppendCommand('.screen-schedule-ui--right', $build));
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
   */
  public function viewScheduleItem(OpenYScreenInterface $screen, OpenYScheduleItemInterface $schedule_item) {
    $screen_content = $schedule_item->content->entity;
    // Build an edit Schedule item form.
    $build = [
      '#type' => 'container',
      '#tag' => 'div',
      '#attributes' => [
        'data-src' => Url::fromRoute('entity.node.canonical', ['node' => $screen_content->id()])->toString(),
        'class' => ['frame-container'],
      ],
    ];

    if ($screen->orientation->value == 'portrait') {
      $build['#attributes']['class'][] = 'frame-container--portrait';
    }


    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();

    $response->addCommand(new RemoveCommand('.screen-schedule-ui--right > *'));
    $response->addCommand(new AppendCommand('.screen-schedule-ui--right', $build));
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
   */
  public function viewScreenContent(OpenYScreenInterface $screen, NodeInterface $screen_content) {
    // Build an edit Schedule item form.
    $build = [
      '#type' => 'container',
      '#tag' => 'div',
      '#attributes' => [
        'data-src' => Url::fromRoute('entity.node.canonical', ['node' => $screen_content->id()])->toString(),
        'class' => ['frame-container'],
      ],
    ];

    if ($screen->orientation->value == 'portrait') {
      $build['#attributes']['class'][] = 'frame-container--portrait';
    }

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();

    $response->addCommand(new RemoveCommand('.screen-schedule-ui--right > *'));
    $response->addCommand(new AppendCommand('.screen-schedule-ui--right', $build));
    return $response;
  }

  /**
   * Gets a markup for Screen schedule timeline.
   *
   * @param OpenYScreenInterface $screen
   *   The Screen entity.

   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function redrawTimeline(OpenYScreenInterface $screen) {
    $schedule_entity = $screen->screen_schedule->entity;
    $schedule_manager = \Drupal::service('openy_digital_signage_schedule.manager');
    $now = strtotime('today -1 day');
    $schedule = $schedule_manager->getUpcomingScreenContents($schedule_entity, 86400, $now);

    $build = [
      '#type' => 'container',
      '#theme' => 'screen_schedule_timeline',
      '#screen' => $screen,
      '#schedule' => $schedule,
    ];

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();

    $response->addCommand(new RemoveCommand('.screen-schedule-ui--left > *'));
    $response->addCommand(new AppendCommand('.screen-schedule-ui--left', $build));
    return $response;
  }
}
