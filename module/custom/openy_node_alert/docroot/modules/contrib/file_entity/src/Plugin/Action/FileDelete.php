<?php

namespace Drupal\file_entity\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete a file.
 *
 * @Action(
 *   id = "file_delete_action",
 *   label = @Translation("Delete file"),
 *   type = "file",
 *   confirm_form_route_name = "file_entity.multiple_delete_confirm",
 * )
 */
class FileDelete extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The temp store.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempStore = $temp_store_factory->get('file_multiple_delete_confirm');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('user.private_tempstore'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple(array($entity));
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    // @todo Make translation-aware, similar to node.
    $entities_by_id = [];
    foreach ($entities as $entity) {
      $entities_by_id[$entity->id()] = $entity;
    }
    // Just save in temp store for now, delete after confirmation.
    $this->tempStore->set('delete', $entities_by_id);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf($object instanceof FileInterface)->andIf(AccessResult::allowedIf($object->access('delete')));
    return $return_as_object ? $result : $result->isAllowed();
  }


}
