<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for block_content:basic content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_block_content_promo_block"
 * )
 */
class YmcaMigrateBlockContentPromoBlock extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('legacy__block_content_promo_block', 'b')
      ->fields('b', ['id', 'header', 'image', 'link', 'body']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Block id'),
      'info' => $this->t('Block label'),
      'header' => $this->t('Block header'),
      'image' => $this->t('Block image id'),
      'image_alt' => $this->t('Block image alt'),
      'link' => $this->t('Block link'),
      'body' => $this->t('Body field value'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // All the data blow we could fetch by making additional SQL requests.
    $row->setSourceProperty('info', $row->getSourceProperty('header'));
    $row->setSourceProperty('link_title', $row->getSourceProperty('header'));
    $row->setSourceProperty('image_alt', $row->getSourceProperty('header'));

    return parent::prepareRow($row);
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
