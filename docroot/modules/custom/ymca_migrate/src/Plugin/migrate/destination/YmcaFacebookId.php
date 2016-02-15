<?php

namespace Drupal\ymca_migrate\Plugin\migrate\destination;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateTrait;

/**
 * Defines destination plugin.
 *
 * @MigrateDestination(
 *   id = "ymca_facebook_id"
 * )
 */
class YmcaFacebookId extends DestinationBase {

  use YmcaMigrateTrait;

  /**
   * List of Migrations.
   *
   * @var array
   */
  private $migrations = [];

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Load migrations to map IDs.
    $dependencies = $this->migration->getMigrationDependencies();
    $this->migrations = \Drupal::getContainer()
      ->get('entity.manager')
      ->getStorage('migration')
      ->loadMultiple($dependencies['required']);

    $nid = YmcaMigrateTrait::getDestinationId(['site_page_id' => $row->getSourceProperty('page_id')], $this->migrations);

    if (!$nid) {
      // There is no such ID migrated. Just skip.
      return FALSE;
    }

    /* @var Node $node */
    $node = Node::load($nid);
    $node->set('field_facebook_page_id', [
      'value' => $row->getSourceProperty('fb_id'),
    ]);
    $node->save();

    return ['nid' => $nid];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'nid' => $this->t('The node ID'),
      'facebook_id' => $this->t('The Facebook ID.'),
    ];
  }

}
