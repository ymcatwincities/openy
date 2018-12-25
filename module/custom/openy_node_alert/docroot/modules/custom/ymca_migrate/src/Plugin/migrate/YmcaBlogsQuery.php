<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Class YmcaBlogsQuery.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaBlogsQuery extends AmmBlogsQuery {

  use YmcaMigrateTrait;

  /**
   * Database to be used as active.
   *
   * @var \Drupal\migrate\Plugin\migrate\source\SqlBase
   */
  protected $database;

  /**
   * Tree of pages.
   *
   * @var array
   */
  protected $tree;

  /**
   * Singleton instance.
   *
   * @var \Drupal\ymca_migrate\Plugin\migrate\YmcaBlogsQuery
   */
  static private $instance;

  /**
   * If the ID has children.
   *
   * @var bool
   */
  private $hasChildren;

  /**
   * Select Interface for active DB SqlBase.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * Current migration for ability to logs.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * YmcaBlogsQuery constructor.
   *
   * @param \Drupal\migrate\Plugin\migrate\source\SqlBase $database
   *   SqlBase wrapper for dealing with DB.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   Migration for logging.
   */
  protected function __construct(SqlBase &$database, MigrationInterface &$migration) {
    // @todo Rethink if we can get rid of skip and needed IDs within constructor.
    $this->database = &$database;
    $this->migration = &$migration;
    $this->tree = [];
    // Let's by default have no children.
    $this->hasChildren = FALSE;

    if ($this->isDev()) {
      $this->setNeededIds(
        [
          859,
          857,
          856,
          855,
          854,
          825,
          850,
          841,
          832,
          831,
        ]
      );
    }

    $this->initQuery();
    // @todo Should we use here CT machine name?
    parent::__construct('blog');
  }

  /**
   * Method for init query with select parameters after fetch*() methods.
   */
  private function initQuery() {
    // Initialize query single time.
    $options['fetch'] = \PDO::FETCH_ASSOC;
    $this->query = $this->database->getDatabase()->select('abe_blog_post', 'b', $options);

    $this->query->fields(
      'b',
      [
        'blog_post_id',
        'title',
        'created_on',
        'modified_on',
        'blog_id',
        'author',
        'publication_date',
      ]
    );
    $this->query->addJoin('LEFT', 'catalog_entry', 'ce', 'b.blog_post_id = ce.entity_id');
    $this->query->fields(
      'ce',
      [
        'entry_id',
      ]
    );
    $this->query->addJoin('LEFT', 'catalog_label_association', 'cla', 'ce.entry_id = cla.entry_id');
    $this->query->fields(
      'cla',
      [
        'entry_id',
      ]
    );
    $this->query->addJoin('LEFT', 'catalog_label', 'cl', 'cla.label_id = cl.label_id');
    $this->query->fields(
      'cl',
      [
        'label_id',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    if ($this->isDev()) {
      return $this->getAllChildren($this->getNeededIds());
    }
    else {
      return $this->query;
    }
  }

  /**
   * {@inheritdoc}
   */
  static public function init(SqlBase &$migrate_database, MigrationInterface &$migration) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    self::$instance = new self($migrate_database, $migration);
    return self::$instance;
  }

  /**
   * Grab a list of all children for specific blog ID.
   *
   * @param array $ids
   *   LIst of IDs to process.
   *
   * @return array|bool
   *   Array of IDs. FALSE if no.
   */
  private function getAllChildren($ids = array()) {

    // @todo optimize this for /admin/structure/migrate/manage/ymca/migrations

    if (!empty($ids)) {
      $this->setNeededIds($ids);
    }
    $skipped_ids = $this->getSkippedIds();
    if (empty($skipped_ids)) {
      $skipped_ids = array();
    }
    // Logging count of not migrated blog posts.
    $this->query->condition(
      'blog_post_id',
      array_merge($this->getNeededIds(), $skipped_ids),
      'NOT IN'
    );
    $abandoned_ids = $this->query->execute()->fetchAll();

    $message = t('Blog posts not been migrated yet: @count', array('@count' => count($abandoned_ids)));
    \Drupal::logger('ymca_migrate')->error($message);

    $this->initQuery();
    $this->query->condition('blog_post_id', $this->getNeededIds(), 'IN');

    if (!empty($skipped_ids)) {
      $this->query->condition(
        'blog_post_id',
        $skipped_ids,
        'NOT IN'
      );
    }
    return $this->query;
  }

}
