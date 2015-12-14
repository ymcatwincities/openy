<?php
/**
 * @file
 * Contains \Drupal\metatag\MetatagManagerInterface.
 */

namespace Drupal\metatag;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class MetatagManager.
 *
 * @package Drupal\metatag
 */
interface MetatagManagerInterface {

  /**
   * Extracts all tags of a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to extract metatags from.
   *
   * @return array
   *   Array of metatags.
   */
  public function tagsFromEntity(ContentEntityInterface $entity);

  /**
   * Returns an array of group plugin information sorted by weight.
   *
   * @return array
   *   Array of groups, sorted by weight.
   */
  public function sortedGroups();

  /**
   * Returns an array of tag plugin information sorted by group then weight.
   *
   * @return array
   *   Array of tags, sorted by weight.
   */
  public function sortedTags();

  /**
   * Returns a weighted array of groups containing their weighted tags.
   *
   * @return array
   *   Array of sorted tags, in groups.
   */
  public function sortedGroupsWithTags();

  /**
   * Builds the form element for a Metatag field.
   *
   * If a list of either groups or tags are passed in, those will be used to
   * limit the groups/tags on the form. If nothing is passed in, all groups
   * and tags will be used.
   *
   * @param array $values
   *   Existing values.
   * @param array $element
   *   Existing element
   * @param array $included_groups
   *   Available group plugins.
   * @param array $included_tags
   *   Available tag plugins.
   *
   * @return array
   *   Render array for metatag form.
   */
  public function form(array $values, array $element, array $included_groups = NULL, array $included_tags = NULL);

}
