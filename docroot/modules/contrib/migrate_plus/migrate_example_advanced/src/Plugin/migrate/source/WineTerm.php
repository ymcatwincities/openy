<?php

/**
 * @file
 * Contains \Drupal\migrate_example_advanced\Plugin\migrate\source\WineTerm.
 */

namespace Drupal\migrate_example_advanced\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * A straight-forward SQL-based source plugin, to retrieve category data from
 * the source database.
 *
 * @MigrateSource(
 *   id = "wine_term"
 * )
 */
class WineTerm extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('migrate_example_advanced_categories', 'wc')
      ->fields('wc', ['categoryid', 'type', 'name', 'details', 'category_parent', 'ordering'])
      // This sort assures that parents are saved before children.
      ->orderBy('category_parent', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'categoryid' => $this->t('Unique ID of the category'),
      'type' => $this->t('Category type corresponding to Drupal vocabularies'),
      'name' => $this->t('Category name'),
      'details' => $this->t('Description of the category'),
      'category_parent' => $this->t('ID of the parent category'),
      'ordering' => $this->t('Order in which to display this category'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['categoryid' => ['type' => 'integer']];
  }

}
