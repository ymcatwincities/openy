<?php

namespace Drupal\ymca_migrate_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Path\PathValidator;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateTrait;

/**
 * YmcaMigrateNonMigrated controller.
 */
class YmcaMigrateNonMigrated extends ControllerBase {

  use YmcaMigrateTrait;

  /**
   * Legacy DB connection.
   *
   * @var Connection
   */
  protected $dbLegacy = NULL;

  /**
   * DB connection.
   *
   * @var Connection
   */
  protected $db = NULL;

  /**
   * DB connection.
   *
   * @var PathValidator
   */
  private $validator = NULL;

  /**
   * YmcaMigrateNonMigrated constructor.
   */
  public function __construct() {
    $this->dbLegacy = Database::getConnection('default', 'amm_source');
    $this->db = Database::getConnection();
    $this->validator = \Drupal::pathValidator();
  }

  /**
   * Show the page.
   */
  public function pageView() {
    $stmt = $this->dbLegacy->select('amm_site_page', 'p')
      ->fields(
        'p', ['site_page_id', 'page_subdirectory']
      )
      ->condition('is_redirect', 0)
      ->condition('do_not_publish', 0)
      ->condition('site_page_id', $this->getSkippedPages(), 'NOT IN')
      ->isNull('backup_of_site_page_id')
      ->execute();

    $non = [];
    while ($data = $stmt->fetchObject()) {
      if (!$this->validator->isValid($data->page_subdirectory)) {
        $non[] = [
          'id' => $data->site_page_id,
          'path' => $data->page_subdirectory,
        ];
      }
    }

    return array(
      'info' => [
        '#markup' => t('Count of non-migrated pages: %num', ['%num' => count($non)]),
      ],
      'table' => [
        '#theme' => 'table',
        '#rows' => $non,
        '#header' => [
          'Page ID',
          'Path',
        ]
      ],
      '#cache' => [
        'max-age' => 60 * 5
      ],
    );
  }

}
