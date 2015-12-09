<?php

/**
 * @file
 * Contains redirects migration.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;
use Drupal\Core\Database\Query\Query;
use Drupal\Core\Database\Statement;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for entity:redirect content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_redirect"
 * )
 */
class YmcaMigrateRedirect extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('amm_site_page', 'p')
      ->fields('p',
      [
        'site_page_id',
        'page_subdirectory',
      ])
      ->condition('site_page_id', 4940);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'site_page_id' => $this->t('Page ID'),
      'page_subdirectory' => $this->t('Page path'),
      'redirect_source' => $this->t('Processed page path'),
      'redirect_page_id' => $this->t('Page ID for the redirect page'),
      'redirect_redirect' => $this->t('Processed redirect'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // @todo Remove hardcoded value when all migrations will be ready.
    $row->setSourceProperty('redirect_page_id', 4747);

    $row->setSourceProperty('redirect_source', trim($row->getSourceProperty('page_subdirectory'), '/'));
    $row->setSourceProperty('redirect_redirect', $this->getRedirect($row->getSourceProperty('redirect_page_id')));

    return TRUE;
  }

  /**
   * Get redirect path.
   *
   * @param int $id
   *   Page ID.
   *
   * @return string
   *   Ready to use redirect path for redirect entity.
   */
  private function getRedirect($id) {
    $query = $this->select('amm_site_page', 'p')
      ->fields('p', ['page_subdirectory'])
      ->condition('site_page_id', $id);
    $path = $query->execute()->fetchField();
    return sprintf('internal:%s', rtrim($path, '/'));
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'site_page_id' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

}
