<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\Core\State\StateInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for menu_link_content items for redirects.
 *
 * We've got migrated menu link items in required migrations. Please, see
 * $this->requirements. That migrations did not migrate menu items for
 * redirects. We are going to create menu items for redirects in this migration.
 * In order to get parent menu item and menu itself we need to load that
 * requirements and also current migration.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_menu_link_content_redirect"
 * )
 */
class YmcaMigrateMenuLinkContentRedirect extends SqlBase {

  /**
   * List of required migrations.
   *
   * @var array
   */
  private $requirements = [];

  /**
   * List of required migrations entities.
   *
   * @var array
   */
  private $migrations = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->prepopulateMigrations();
  }

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
          'exclude_from_nav',
          'sequence_index',
          'parent_id',
          'page_name',
        ])
      ->condition('is_redirect', 1);
    $query->orderBy('nav_level', 'ASC');
    $query->orderBy('sequence_index', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'parent_id' => $this->t('Parent ID'),
      'title' => $this->t('Link Title'),
      'sequence_index' => $this->t('Weight'),
      'menu_name' => $this->t('Menu'),
      'enabled' => $this->t('Enabled'),
      'parent_mlid' => $this->t('Parent menu item ID'),
      'link' => $this->t('Link destination'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // The source for title and link are different fore page and url targets.
    // Get appropriate one with $this->getRedirectData().
    $redirect_data = $this->getRedirectData($row);
    $row->setSourceProperty('title', $redirect_data['title']);
    $row->setSourceProperty('link', ['uri' => $redirect_data['link']]);

    // Disable menu item if needed.
    $row->setSourceProperty('enabled', TRUE);
    if ($row->getSourceProperty('exclude_from_nav')) {
      $row->setSourceProperty('enabled', FALSE);
    }

    // Get parent menu link ID.
    $parent_mlid = $this->getParentMlid([
      'site_page_id' => $row->getSourceProperty('parent_id')
    ]);

    // Check if we've got a parent.
    if ($parent_mlid) {
      $row->setSourceProperty('parent_mlid', $parent_mlid);
    }
    else {
      $this->idMap->saveMessage(
        $this->getCurrentIds(),
        $this->t(
          '[DEV] Cannot obtain parent menu link ID for @page',
          array('@page' => $row->getSourceProperty('parent_id'))
        ),
        MigrationInterface::MESSAGE_ERROR
      );
      return FALSE;
    }

    // Get menu name.
    $row->setSourceProperty(
      'menu_name',
      $this->getMenu($row->getSourceProperty('parent_mlid'))
    );

    return parent::prepareRow($row);
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

  /**
   * Get redirect data.
   *
   * @param Row $row
   *   Row object.
   *
   * @return array
   *   Ready to use redirect data:
   *    - title
   *    - link
   */
  private function getRedirectData(Row $row) {
    $data = [];
    switch ($row->getSourceProperty('redirect_target')) {
      case 'page':
        $result = $this->select('amm_site_page', 'p')
          ->fields('p', ['page_subdirectory', 'page_name'])
          ->condition('site_page_id', $row->getSourceProperty('redirect_page_id'))
          ->execute()
          ->fetchAssoc();
        $data = [
          'title' => $result['page_name'],
          'link' => sprintf('internal:%s', rtrim($result['page_subdirectory'], '/')),
        ];
        break;

      case 'url':
        $data = [
          'title' => $row->getSourceProperty('page_name'),
          'link' => $row->getSourceProperty('redirect_url'),
        ];

        // Specific redirect for SSO Sign Out page.
        if ($row->getSourceProperty('site_page_id') == 13300) {
          $data['link'] = 'internal:/personify/sign_out';
        }
        break;
    }
    return $data;
  }

  /**
   * Get menu name.
   *
   * @param int $menu_link_id
   *   Menu link ID.
   *
   * @return string
   *   Menu name.
   */
  private function getMenu($menu_link_id) {
    /* @var MenuLinkContent $menu_link */
    $menu_link = \Drupal::getContainer()
      ->get('entity.manager')
      ->getStorage('menu_link_content')
      ->load($menu_link_id);
    return $menu_link->getMenuName();
  }

  /**
   * Get parent menu link ID.
   *
   * @param array $source
   *   Example: ['site_page_id' => 10].
   *
   * @return bool|string
   *   Parent menu link ID.
   */
  private function getParentMlid(array $source) {
    foreach ($this->migrations as $id => $migration) {
      $map = $migration->getIdMap();
      $dest = $map->getRowBySource($source);
      if (!empty($dest)) {
        return $dest['destid1'];
      }
    }

    return FALSE;
  }

  /**
   * Prepopulate required migrations.
   */
  private function prepopulateMigrations() {
    $this->requirements = [
      'ymca_migrate_menu_link_content_camps',
      'ymca_migrate_menu_link_content_child_care_preschool',
      'ymca_migrate_menu_link_content_community_programs',
      'ymca_migrate_menu_link_content_health_fitness',
      'ymca_migrate_menu_link_content_jobs_suppliers_news',
      'ymca_migrate_menu_link_content_kid_teen_activities',
      'ymca_migrate_menu_link_content_locations',
      'ymca_migrate_menu_link_content_main',
      'ymca_migrate_menu_link_content_swimming',
      'ymca_migrate_menu_link_content_redirect',
    ];
    $this->migrations = \Drupal::getContainer()
      ->get('entity.manager')
      ->getStorage('migration')
      ->loadMultiple($this->requirements);
  }

}
