<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for block_content:basic content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_block_content_basic"
 * )
 */
class YmcaMigrateBlockContentBasic extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('legacy__block_content_basic', 'b')
      ->fields('b', ['id', 'info', 'body']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Block id'),
      'info' => $this->t('Block label'),
      'body' => $this->t('Body field value'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
