<?php

namespace Drupal\ymca_blocks;

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

  /**
   * SlideShow block entity.
   *
   * @var BlockContent
   */
  protected $slideShowBlockEntity;

  /**
   * Array of items for template.
   *
   * @var array
   */
  protected $slideShowItems;

  const DBS_BEFORE = 'before starting date';
  const DBS_MIDDLE = 'in the middle';
  const DBS_AFTER = 'after ending date';

  /**
   * Find Slides for specific Date Block by it's ID.
   *
   * @param int $date_block_id
   *   Date block ID to work on.
   *
   * @return array
   *   Return array for adding to render afterwards.
   *
   * @throws \Exception
   */
  public function getSlides($date_block_id = 0) {
    if ($date_block_id == 0) {
      \Drupal::logger('Date Blocks')->critical(t('Block ID shoudn\'t be zero'));
      return FALSE;
    }
    /** @var BlockContent $date_block */
    $date_block = \Drupal::entityTypeManager()->getStorage('block_content')->load($date_block_id);

    if (empty($date_block)) {
      \Drupal::logger('Date Blocks')->critical(t('Could not load block ID @id.', array('@id' => $date_block_id)));
      return FALSE;
    }

    if ($date_block->get('type')->get(0)->getValue()['target_id'] !== 'date_block') {
      \Drupal::logger('Date Blocks')->critical(t('Block type is not date_block.'));
      return FALSE;
    }

    if (isset($date_block->_referringItem) && $date_block->_referringItem->getFieldDefinition()->get('field_name') != 'field_promo_slideshow') {
      \Drupal::logger('Date Blocks')->critical(t('Block is not referenced from field_promo_slideshow.'));
      return FALSE;
    }

    $this->initBlockData($date_block);
    return $this->slideShowItems;
  }

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
        $this->activeContent = is_null($entity->get('field_content_date_before')->get(0)) ? '' : $entity->get('field_content_date_before')->get(0)->getValue()['value'];
        break;

      case self::DBS_MIDDLE:
        $this->activeContent = is_null($entity->get('field_content_date_between')->get(0)) ? '' : $entity->get('field_content_date_between')->get(0)->getValue()['value'];
        break;

      case self::DBS_AFTER:
        $this->activeContent = is_null($entity->get('field_content_date_end')->get(0)) ? '' : $entity->get('field_content_date_end')->get(0)->getValue()['value'];
        break;

    }
    // Obtain SlideShow.
    try {
      $this->setSlideShowBlockEntity($this->activeContent);
    }
    catch (\Exception $e) {
      watchdog_exception(__CLASS__, $e);
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
      'tags' => ['ymca_cron']
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

  /**
   * SlideShow architecture parser.
   *
   * @param string $embed_data
   *   String been parsed.
   *
   * @throws \Exception
   *
   * @return bool
   *   FALSE if there is an error.
   */
  private function setSlideShowBlockEntity($embed_data = '') {
    if ($embed_data == '') {
      return FALSE;
    }

    preg_match_all("/<drupal-entity.*data-entity-uuid=\"(.*)\">.*<\/drupal-entity>/miU", $embed_data, $match);
    if (!isset($match[1][0])) {
      return FALSE;
    }
    if (count($match[1]) !== 1) {
      return FALSE;
    }
    $b_type = 'block_content';
    $query = \Drupal::entityQuery($b_type)
      ->condition('type', 'slide_show')
      ->condition('uuid', $match[1][0])
      ->execute();
    if (empty($query)) {
      return FALSE;
    }
    $slideshow_block_id = array_shift($query);
    $this->slideShowBlockEntity = \Drupal::entityManager()->getStorage($b_type)->load($slideshow_block_id);
    $items = $this->slideShowBlockEntity->get('field_slide_show_item');
    $ids = array();
    for ($i = 0; $i < $items->count(); $i++) {
      $ids[] = $items->get($i)->getValue()['target_id'];
    }
    $slides = \Drupal::entityManager()->getStorage($b_type)->loadMultiple($ids);

    $i = 0;
    $style = ImageStyle::load('2013_masthead');
    /** @var BlockContent $slide_entity */
    foreach ($slides as $id => $slide_entity) {
      $title = is_null($slide_entity->get('field_title')->get(0)) ? '' : $slide_entity->get('field_title')->get(0)->getValue()['value'];
      $img_url = is_null($slide_entity->get('field_image')->get(0)) ? '' : File::load($slide_entity->get('field_image')->get(0)->getValue()['target_id'])->getFileUri();
      $description = '';
      if ($content = $slide_entity->get('field_block_content')->get(0)) {
        $value = $content->getValue();
        $description = check_markup($value['value'], $value['format']);
      }

      $this->slideShowItems[$i]['id'] = $i;
      $this->slideShowItems[$i]['title'] = $title;
      $this->slideShowItems[$i]['img_url'] = $style->buildUrl($img_url);
      $this->slideShowItems[$i]['description'] = $description;
      $i++;
    }
  }

  /**
   * Load MenuLink Entity from entity_embed code.
   *
   * @param string $embed_data
   *   String to be parsed for MenuLink entity_embed.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Menu Link entity.
   *
   * @throws \Exception
   */
  private function getMenuLinkEntity($embed_data) {
    if ($embed_data == '') {
      \Drupal::logger('Date Blocks')->critical(t('Embed data cannot be empty'));
      return FALSE;
    }

    preg_match_all("/<drupal-entity.*data-entity-type=\"menu_link_content\".*data-entity-uuid=\"(.*)\">.*<\/drupal-entity>/miU", $embed_data, $match);
    if (!isset($match[1][0])) {
      \Drupal::logger('Date Blocks')->critical(t('Embed data contains no entity_embed code'));
      return FALSE;
    }
    if (count($match[1]) !== 1) {
      \Drupal::logger('Date Blocks')->critical(t('Embed data contains inappropriate entity_embed code. Should be single item Menu Link only'));
      return FALSE;
    }

    $b_type = 'menu_link_content';
    $query = \Drupal::entityQuery($b_type)
      ->condition('uuid', $match[1][0])
      ->execute();
    if (empty($query)) {
      \Drupal::logger('Date Blocks')->critical(t('Embed data contains uuid to non existent Menu Link item'));
      return FALSE;
    }
    $menu_item_id = array_shift($query);
    return \Drupal::entityManager()->getStorage($b_type)->load($menu_item_id);
  }

}
