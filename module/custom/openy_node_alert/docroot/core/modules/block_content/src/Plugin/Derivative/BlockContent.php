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
   * The custom block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
   protected $blockContentStorage;

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection, EntityStorageInterface $block_content_storage) {
    $this->connection = $connection;
    $this->blockContentStorage = $block_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $container->get('database'),
      $entity_manager->getStorage('block_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $request = \Drupal::request();
    $is_ajax = $request->isXmlHttpRequest();
    if ($is_ajax) {
      $block_contents = $this->blockContentStorage->loadMultiple();
      // Reset the discovered definitions.
      $this->derivatives = [];
      /** @var $block_content \Drupal\block_content\Entity\BlockContent */
      foreach ($block_contents as $block_content) {
        $this->derivatives[$block_content->uuid()] = $base_plugin_definition;
        $this->derivatives[$block_content->uuid()]['admin_label'] = $block_content->label();
        $this->derivatives[$block_content->uuid()]['config_dependencies']['content'] = [
          $block_content->getConfigDependencyName()
        ];
      }
    }
    else {
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
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
