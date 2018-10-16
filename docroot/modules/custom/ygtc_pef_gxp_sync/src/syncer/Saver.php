<?php

namespace Drupal\ygtc_pef_gxp_sync\syncer;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class Saver.
 *
 * @package Drupal\ygtc_pef_gxp_sync\syncer
 */
class Saver implements SaverInterface {

  /**
   * Wrapper.
   *
   * @var \Drupal\ygtc_pef_gxp_sync\syncer\WrapperInterface
   */
  protected $wrapper;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Program subcategory.
   *
   * @var integer
   */
  protected $programSubcategory;

  /**
   * Saver constructor.
   *
   * @param \Drupal\ygtc_pef_gxp_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannel $loggerChannel
   *   Logger.
   */
  public function __construct(WrapperInterface $wrapper, LoggerChannel $loggerChannel) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;

    // @todo Get from module's config.
    $config = \Drupal::configFactory()->get('openy_gxp.settings');
    $this->programSubcategory = $config->get('activity');
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $data = $this->wrapper->getProcessedData();

    foreach ($data as $item) {
      // Get/Create class.
      try {
        $class = $this->getClass($item);
      }
      catch (\Exception $exception) {
        $this->logger
          ->error(
            'Failed to get class for Groupex class %class with message %message',
            [
              '%class' => $item['class_id'],
              '%message' => $exception->getMessage()
            ]
          );
      }

      // @todo Loop over data and create the appropriate entities.
      // @todo Create field_session_time paragraph.
      // @todo Create field_session_exclusions paragraph.
      // @todo Create session instance itself.

      $a = 10;
    }
  }

  /**
   * Create class or use existing.
   *
   * @param array $class
   *   Class properties.
   *
   * @return array
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getClass(array $class) {
    // Try to get existing activity.
    $existingActivities = \Drupal::entityQuery('node')
      ->condition('title', $class['title'])
      ->condition('type', 'activity')
      ->condition('field_activity_category', $this->programSubcategory)
      ->execute();

    if (!$existingActivities) {
      // No activities found. Create one.
      $activity = Node::create([
        'uid' => 1,
        'lang' => 'und',
        'type' => 'activity',
        'title' => $class['title'],
        'field_activity_description' => [[
          'value' => $class['description'],
          'format' => 'full_html'
        ]],
        'field_activity_category' => [['target_id' => $this->programSubcategory]],
      ]);
      $activity->save();
    }
    else {
      // Use the first found existing one.
      $activityId = reset($existingActivities);
      $activity = Node::load($activityId);
    }

    // Try to find class.
    $existingClasses = \Drupal::entityQuery('node')
      ->condition('title', $class['title'])
      ->condition('field_class_activity', $activity->id())
      ->condition('field_class_description', $class['description'])
      ->execute();

    if (!empty($existingClasses)) {
      $classId = reset($existingClasses);
      $class = Node::load($classId);
    }
    else {
      $paragraphs = [];
      foreach (['class_sessions', 'branches_popup_class'] as $type) {
        $paragraph = Paragraph::create(['type' => $type ]);
        $paragraph->isNew();
        $paragraph->save();
        $paragraphs[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
      $class = Node::create([
        'uid' => 1,
        'lang' => 'und',
        'type' => 'class',
        'title' => $class['title'],
        'field_class_description' => [[
          'value' => $class['description'],
          'format' => 'full_html'
        ]],
        'field_class_activity' => [['target_id' => $activity->id()]],
        'field_content' => $paragraphs,
      ]);
      $class->save();
    }

    return [
      'target_id' => $class->id(),
      'target_revision_id' => $class->getRevisionId(),
    ];
  }

}
