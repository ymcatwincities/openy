<?php
/**
 * @file
 * Date Block parser service.
 */

namespace Drupal\ymca_blocks;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Class DateBlock.
 *
 * @package Drupal\ymca_blocks
 */
class DateBlockService {

  /**
   * @var \DateTime;
   */
  protected $startDate;

  /**
   * @var \DateTime;
   */
  protected $endDate;

  /**
   * @var string;
   */
  protected $activeContent;

  /**
   * @var BlockContent
   */
  protected $slideShowBlockEntity;

  protected $slideShowItems;

  const DBS_BEFORE = 'before starting date';
  const DBS_MIDDLE = 'in the middle';
  const DBS_AFTER = 'after ending date';

  /**
   * DateBlock constructor.
   */
  public function __construct() {
  }

  /**
   * Find Slides for specific Date Block by it's ID.
   *
   * @param int $date_block_id
   *   Date block ID to work on.
   *
   * @return array
   * Return array for adding to render afterwards.
   *
   * @throws \Exception
   */
  public function getSlides($date_block_id = 0) {
    if ($date_block_id == 0) {
      throw new \Exception('Block ID shoudn\'t be zero');
    }
    /** @var BlockContent $date_block */
    $date_block = \Drupal::entityTypeManager()->getStorage('block_content')->load($date_block_id);

    if ($date_block->get('type')->get(0)->getValue()['target_id'] !== 'date_block') {
      throw new \Exception('Block type is not date_block.');
    }

    if ($date_block->_referringItem->getFieldDefinition()->get('field_name') != 'field_promo_slideshow') {
      throw new \Exception('Block is not referenced from field_promo_slideshow.');
    }

    $this->initBlockData($date_block);
    return $this->slideShowItems;
  }

  /**
   * Initial setter for a block.
   *
   * @param BlockContent $date_block
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
        $this->activeContent = $entity->get('field_content_date_before')->get(0)->getValue()['value'];
        break;
      case self::DBS_MIDDLE:
        $this->activeContent = $entity->get('field_content_date_between')->get(0)->getValue()['value'];
        break;
      case self::DBS_AFTER:
        $this->activeContent = $entity->get('field_content_date_end')->get(0)->getValue()['value'];
        break;
    }
    // Obtain SlideShow.
    $this->getSlideShowBlockEntity($this->activeContent);

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

        // Cache to be invalidated when start time comes.
        $build['#cache']['max-age'] = $this->startDate->getTimestamp() - REQUEST_TIME;
        break;
      case self::DBS_MIDDLE:
        hide($build['field_content_date_before']);
        hide($build['field_content_date_end']);

        // Cache to be invalidated when end time comes.
        $build['#cache']['max-age'] = $this->endDate->getTimestamp() - REQUEST_TIME;
        break;
      case self::DBS_AFTER:
        hide($build['field_content_date_before']);
        hide($build['field_content_date_between']);

        // Ok, all the dates are behind. Cache permanently, by default.
        break;
    }

    // Do not show date fields at all.
    hide($build['field_start_date']);
    hide($build['field_end_date']);

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

  private function getSlideShowBlockEntity($embed_data = '') {
    if ($embed_data == '') {
      throw new \Exception('Embed data cannot be empty');
    }

    preg_match_all("/<drupal-entity.*data-entity-uuid=\"(.*)\">.*<\/drupal-entity>/miU", $embed_data, $match);
    if (!isset($match[1][0])) {
      throw new \Exception('Embed data contains no entity_embed code');
    }
    if (count($match[1]) !== 1) {
      throw new \Exception('Embed data contains inappropriate entity_embed code. Should be single item only');
    }
    $b_type = 'block_content';
    $query = \Drupal::entityQuery($b_type)
      ->condition('type', 'slide_show')
      ->condition('uuid', $match[1][0])
      ->execute();
    if (empty($query)) {
      throw new \Exception('Embed data contains uuid to non existent SlideShow block');
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
    /** @var BlockContent $slide_entity */
    foreach ($slides as $id => $slide_entity) {
      $title = is_null($slide_entity->get('field_title')->get(0)) ? '' : $slide_entity->get('field_title')->get(0)->getValue()['value'];
      $img_url = is_null($slide_entity->get('field_image')->get(0)) ? '' : File::load($slide_entity->get('field_image')->get(0)->getValue()['target_id'])->getFileUri();
      $menu_link = is_null($slide_entity->get('field_block_content')->get(0)) ? NULL : $this->getMenuLinkEntity($slide_entity->get('field_block_content')->get(0)->getValue()['value']);
      $btn_url = is_null($menu_link) ? '' : $menu_link->get('link')->get(0)->getValue()['uri'];
      $btn_title = is_null($menu_link) ? '' : $menu_link->label();
      $this->slideShowItems[$i]['id'] = $i;
      $this->slideShowItems[$i]['title'] = $title;
      $this->slideShowItems[$i]['img_url'] = file_create_url($img_url);
      $this->slideShowItems[$i]['btn_title'] = $btn_title;
      $this->slideShowItems[$i]['btn_url'] = $btn_url == '' ? '' : Url::fromUri($btn_url);
      $i++;
    }
  }

  /**
   * Load MenuLink Entity from entity_embed code.
   *
   * @param $embed_data
   *   String to be parsed for MenuLink entity_embed.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Menu Link entity.
   *
   * @throws \Exception
   */
  private function getMenuLinkEntity($embed_data) {
    if ($embed_data == '') {
      throw new \Exception('Embed data cannot be empty');
    }

    preg_match_all("/<drupal-entity.*data-entity-type=\"menu_link_content\".*data-entity-uuid=\"(.*)\">.*<\/drupal-entity>/miU", $embed_data, $match);
    if (!isset($match[1][0])) {
      throw new \Exception('Embed data contains no entity_embed code');
    }
    if (count($match[1]) !== 1) {
      throw new \Exception('Embed data contains inappropriate entity_embed code. Should be single item Menu Link only');
    }

    $b_type = 'menu_link_content';
    $query = \Drupal::entityQuery($b_type)
      ->condition('uuid', $match[1][0])
      ->execute();
    if (empty($query)) {
      throw new \Exception('Embed data contains uuid to non existent Menu Link item');
    }
    $menu_item_id = array_shift($query);
    return \Drupal::entityManager()->getStorage($b_type)->load($menu_item_id);
  }

}