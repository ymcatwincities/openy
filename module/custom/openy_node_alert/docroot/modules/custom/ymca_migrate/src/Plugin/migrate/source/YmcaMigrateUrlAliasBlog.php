<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateUrlAliasBase;
use Drupal\migrate\Row;
use Drupal\ymca_migrate\Plugin\migrate\YmcaBlogsQuery;

/**
 * Source plugin for url aliases.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_url_alias_blog"
 * )
 */
class YmcaMigrateUrlAliasBlog extends YmcaMigrateUrlAliasBase {

  /**
   * {@inheritdoc}
   */
  protected function getRequirements() {
    return [
      'ymca_migrate_node_blog',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query_builder = YmcaBlogsQuery::init($this, $this->migration);
    return $query_builder->getQuery();
  }

  /**
   * Clean title.
   *
   * @param string $str
   *   Title to clean.
   *
   * @return string
   *   Cleaned title.
   */
  protected function cleanTitle($str) {
    $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $str);
    $clean = Unicode::strtolower(trim($clean, '_'));
    $clean = preg_replace("/[\/_|+ -]+/", '_', $clean);
    return $clean;
  }

  /**
   * Make alias.
   *
   * @param \Drupal\migrate\Row $row
   *   Migrate row.
   *
   * @return string
   *   Generated alias.
   */
  protected function makeAlias(Row $row) {
    $date = \DateTime::createFromFormat(
      'Y-m-d H:i:s',
      $row->getSourceProperty('publication_date'),
      new \DateTimeZone(
        \Drupal::config('ymca_migrate.settings')->get('timezone')
      )
    );
    $timestamp = $date->getTimestamp();
    return sprintf(
      '/blog/%d/%d/%d/%d/%s',
      date('Y', $timestamp),
      date('m', $timestamp),
      date('d', $timestamp),
      $row->getSourceProperty('blog_post_id'),
      $this->cleanTitle($row->getSourceProperty('title'))
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source = $this->getSourcePath(['blog_post_id' => $row->getSourceProperty('blog_post_id')]);
    if ($source) {
      $row->setSourceProperty('source', $source);
    }
    else {
      $this->idMap->saveMessage(
        $this->getCurrentIds(),
        $this->t(
          "[DEV] Alias source is undefined for blog post ID: [@id]",
          array('@id' => $row->getSourceProperty('blog_post_id'))
        ),
        MigrationInterface::MESSAGE_WARNING
      );
    }

    $row->setSourceProperty('alias', $this->makeAlias($row));
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'blog_post_id' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
