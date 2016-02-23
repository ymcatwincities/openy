<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

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
    $query = $this->select('amm_site_page', 'p')
      ->fields('p',
      [
        'site_page_id',
        'page_subdirectory',
        'redirect_target',
        'redirect_type',
        'redirect_url',
        'redirect_page_id',
      ])
      ->condition('is_redirect', 1);

    // Omit duplicate redirect.
    $query->condition('site_page_id', [27706], 'NOT IN');

    return $query;
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
      'redirect_type' => $this->t('Status code'),
      'redirect_target' => $this->t('Redirect target'),
      'redirect_url' => $this->t('External redirect URL'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('redirect_source', trim($row->getSourceProperty('page_subdirectory'), '/'));
    $row->setSourceProperty('redirect_redirect', $this->getRedirect($row));

    return parent::prepareRow($row);
  }

  /**
   * Get redirect path.
   *
   * @param Row $row
   *   Row object.
   *
   * @return string|bool
   *   Ready to use redirect path for redirect entity.
   */
  private function getRedirect(Row $row) {
    switch ($row->getSourceProperty('redirect_target')) {
      case 'page':
        $path = $this->select('amm_site_page', 'p')
          ->fields('p', ['page_subdirectory'])
          ->condition('site_page_id', $row->getSourceProperty('redirect_page_id'))
          ->execute()
          ->fetchField();
        return sprintf('internal:%s', rtrim($path, '/'));

      case 'url':
        return $row->getSourceProperty('redirect_url');
    }
    return FALSE;
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
