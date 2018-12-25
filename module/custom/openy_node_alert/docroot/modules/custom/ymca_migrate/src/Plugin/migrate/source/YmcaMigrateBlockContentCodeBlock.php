<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for block_content:code_block content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_block_content_code_block"
 * )
 */
class YmcaMigrateBlockContentCodeBlock extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('shared_code_block', 'b')
      ->fields('b', ['code_block_id', 'name', 'content']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'code_block_id' => $this->t('Block id'),
      'name' => $this->t('Block label'),
      'content' => $this->t('Block content'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'code_block_id' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
