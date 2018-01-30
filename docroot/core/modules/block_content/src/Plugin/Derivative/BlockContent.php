<?php

namespace Drupal\block_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves block plugin definitions for all custom blocks.
 */
class BlockContent extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $query = $this->connection->select('block_content', 'bc')
      ->fields('bc');
    $query->addJoin('left', 'block_content_field_revision', 'f', 'f.revision_id = bc.revision_id');
    $query->fields('f');
    $results = $query->execute();
    // Reset the discovered definitions.
    $this->derivatives = [];
    /** @var $block_content \Drupal\block_content\Entity\BlockContent */
    foreach ($results as $block_content) {
      $this->derivatives[$block_content->uuid] = $base_plugin_definition;
      $this->derivatives[$block_content->uuid]['admin_label'] = $block_content->info;
      $this->derivatives[$block_content->uuid]['config_dependencies']['content'] = [
        "block_content:{$block_content->uuid}:{$block_content->type}"
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
