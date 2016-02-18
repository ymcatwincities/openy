<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateNodeBase;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateTrait;
use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;

/**
 * Source plugin for node:article content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_page"
 * )
 */
class YmcaMigrateNodePage extends YmcaMigrateNodeBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    if ($this->isDev()) {
      $query_builder->getList([
        4693,
        8595,
        4765,
        4747,
        8659,
        4793,
        14641,
        22528,
        4695,
        4792,
        4794,
        8652,
        20235,
        8601,
        4795,
        4711,
        4796,
        8642,
        20256,
        4744,
        4797,
        4717,
        4798,
        4738,
        4720,
        20247,
        20249,
        4721,
        4745,
        4748,
        4754,
        4757,
        4799,
        4729,
        4746,
        4749,
        4755,
        4758,
        20243,
        4750,
        4756,
        15824,
        4751,
        4759,
        4763,
        4752,
        4760,
        4753,
        5100,
        4563,
        7985,
        9638,
      ]);
    }
    else {
      $query_builder->getByBundle('page');
    }
    return $query_builder->build();

  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $theme_id = $row->getSourceProperty('theme_id');

    $show_sidebar = 0;
    if (in_array($theme_id, [22, 17, 23])) {
      $show_sidebar = 1;
    }
    $row->setSourceProperty('field_sidebar_navigation', ['value' => $show_sidebar]);

    // We should relate pages only to locations and camps.
    $path = $row->getSourceProperty('page_subdirectory');
    preg_match('/\/(?:camps|locations)\//', $path, $test);
    if (!empty($test)) {
      // Get path of parent node.
      preg_match('/\/(?:camps|locations)\/.*?\//', $path, $test);
      if (!empty($test)) {
        // For development just use static one.
        if ($this->isDev()) {
          $path = '/locations/elk_river_ymca/';
        }

        // Get id of the node.
        $source = $this->select('amm_site_page', 'p')
          ->fields('p', ['site_page_id'])
          ->condition('page_subdirectory', $test[0])
          ->execute()
          ->fetchAssoc();

        if ($source) {
          // Ok. We've got an ID let's check for it's mapping.
          $migration_ids = [
            'ymca_migrate_node_camp',
            'ymca_migrate_node_location',
          ];
          $migrations = \Drupal::entityManager()
            ->getStorage('migration')
            ->loadMultiple($migration_ids);

          $id = YmcaMigrateTrait::getDestinationId($source, $migrations);
          if (!$id) {
            $this->idMap->saveMessage(
              $this->getCurrentIds(),
              $this->t(
                '[QA] Failed to get mapping [@page]', ['@page' => $source['site_page_id']]
              ),
              MigrationInterface::MESSAGE_ERROR
            );
          }
          else {
            $row->setSourceProperty('field_related', ['target_id' => $id]);
          }
        }
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'site_page_id' => $this->t('Page ID'),
      'page_title' => $this->t('Page title'),
      'field_content' => $this->t('Content'),
      'field_lead_description' => $this->t('Content'),
      'field_header_button' => $this->t('Header button'),
      'field_header_variant' => $this->t('Header variant'),
      'field_sidebar' => $this->t('Sidebar'),
      'field_secondary_sidebar' => $this->t('Secondary sidebar'),
      'field_sidebar_navigation' => $this->t('Show sidebar navigation'),
    ];
    return $fields;
  }

}
