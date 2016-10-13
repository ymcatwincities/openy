<?php

namespace Drupal\bulk_actions\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete a menu item.
 *
 * @Action(
 *   id = "menu_link_content_delete_action",
 *   label = @Translation("Delete menu item"),
 *   type = "menu_link_content",
 *   confirm_form_route_name = "menu_link_content.multiple_delete_confirm",
 * )
 */
class MenuLinkContentDelete extends ActionBase implements ContainerFactoryPluginInterface {

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
    $this->tempStore = $temp_store_factory->get('menu_link_content_multiple_delete_confirm');
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
    // @todo Probably need to check some permissions here.
    return TRUE;
  }

}
