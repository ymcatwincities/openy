<?php

namespace Drupal\openy_blocks;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Class DateBlock.
 *
 * @package Drupal\ymca_blocks
 */
class DateBlockService {

  /**
   * Start date.
   *
   * @var \DateTime
   */
  protected $startDate;

  /**
   * End date.
   *
   * @var \DateTime
   */
  protected $endDate;

  /**
   * Content been parsed.
   *
   * @var string
   */
  protected $activeContent;

  const DBS_BEFORE = 'before starting date';
  const DBS_MIDDLE = 'in the middle';
  const DBS_AFTER = 'after ending date';

  /**
   * Initial setter for a block.
   *
   * @param BlockContent $entity
   *   DateBlock to work with.
   *
   * @return $this
   *   Chaining.
   */
  private function initBlockData(BlockContent $entity) {
    $fsd = $entity->get('field_start_date')->get(0)->getValue()['value'];
    $fed = $entity->get('field_end_date')->get(0)->getValue()['value'];
    $fsd_fix_time = str_replace('\\', '', $fsd);
    $fed_fix_time = str_replace('\\', '', $fed);
    $this->startDate = \DateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $fsd_fix_time, new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    $this->endDate = \DateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $fed_fix_time, new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));

    switch ($this->getBlockState()) {
      case self::DBS_BEFORE:
        $this->activeContent = is_null($entity->get('field_content_date_before')->get(0)) ? '' : $entity->get('field_content_date_before')->get(0)->getValue()['target_id'];
        break;

      case self::DBS_MIDDLE:
        $this->activeContent = is_null($entity->get('field_content_date_between')->get(0)) ? '' : $entity->get('field_content_date_between')->get(0)->getValue()['target_id'];
        break;

      case self::DBS_AFTER:
        $this->activeContent = is_null($entity->get('field_content_date_end')->get(0)) ? '' : $entity->get('field_content_date_end')->get(0)->getValue()['target_id'];
        break;

    }

    return $this;
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter().
   *
   * @param array $build
   *   Render build array to process on.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to deal with.
   *
   * @return $this
   *   Chaining.
   */
  public function viewAlter(array &$build, EntityInterface $entity) {
    $this->initBlockData($entity);

    switch ($this->getBlockState()) {
      case self::DBS_BEFORE:
        hide($build['field_content_date_between']);
        hide($build['field_content_date_end']);
        break;

      case self::DBS_MIDDLE:
        hide($build['field_content_date_before']);
        hide($build['field_content_date_end']);
        break;

      case self::DBS_AFTER:
        hide($build['field_content_date_before']);
        hide($build['field_content_date_between']);
        break;
    }

    // Do not show date fields at all.
    hide($build['field_start_date']);
    hide($build['field_end_date']);

    // Invalidate cache by cron.
    $build['#cache'] = [
      'tags' => ['openy_cron']
    ];

    return $this;
  }

  /**
   * Get block state, depending on a time.
   *
   * @return string
   *   State string representation.
   */
  private function getBlockState() {
    if (REQUEST_TIME <= $this->startDate->getTimestamp()) {
      // Here will go content for before start date.
      return self::DBS_BEFORE;
    }
    elseif (REQUEST_TIME >= $this->endDate->getTimestamp()) {
      // Here will go content for after end date.
      return self::DBS_AFTER;
    }
    else {
      // Here will go content for between dates.
      return self::DBS_MIDDLE;
    }
  }

}
