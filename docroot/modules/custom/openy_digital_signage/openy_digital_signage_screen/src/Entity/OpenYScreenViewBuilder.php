<?php

namespace Drupal\openy_digital_signage_screen\Entity;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a view builder for OpenY Digital Signage Screen entities.
 */
class OpenYScreenViewBuilder implements EntityViewBuilderInterface {

  /**
   * Default timespan is a day.
   */
  const TIMESPAN = 86400;

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $versions = \Drupal::moduleHandler()->invokeAll('ds_version');
    $version = md5(json_encode($versions));

    $build = [
      '#prefix' => '<div
        class="screen"
        data-screen-id="' . $entity->id() . '"
        data-app-version="' . $version . '">',
      '#suffix' => '</div>',
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'openy_digital_signage_screen/openy_ds_screen_handler',
          'openy_digital_signage_screen/openy_ds_screen_theme',
        ],
      ],
    ];

    \Drupal::service('page_cache_kill_switch')->trigger();

    if ($schedule = $entity->screen_schedule->entity) {
      /** @var OpenYScheduleManager $schedule_manager */
      $schedule_manager = \Drupal::service('openy_digital_signage_schedule.manager');
      $schedule = $schedule_manager->getScreenUpcomingScreenContents($entity, self::TIMESPAN);
      $render_controller = \Drupal::entityTypeManager()->getViewBuilder('node');
      foreach ($schedule as $item) {
        if (!$screen_content = $item['content']) {
          continue;
        }
        $period = &drupal_static('schedule_item_period');
        $period = [
          'from' => $item['from'],
          'to' => $item['to'],
        ];
        $schedule_item_build = $render_controller->view($screen_content);
        $hash = md5(json_encode($schedule_item_build));
        $schedule_item_build['#prefix'] = '<div class="screen-content"
          data-screen-content-id="' . $item['content-id'] . '"
          data-from="' . $item['item']['from'] . '" data-to="' . $item['item']['to'] . '"
          data-from-ts="' . $item['from'] . '" data-to-ts="' . $item['to'] . '"
          data-hash="' . $hash . '" >';

        $schedule_item_build['#suffix'] = '</div>';
        $build[] = $schedule_item_build;
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = [];
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = array()) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display_options = array()) {
    throw new \LogicException();
  }

}
