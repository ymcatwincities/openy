<?php

namespace Drupal\panels\Plugin\PanelsStorage;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels\Storage\PanelsStorageBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Panels storage service that stores Panels displays in Page Manager.
 *
 * @PanelsStorage("page_manager")
 */
class PageManagerPanelsStorage extends PanelsStorageBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PageManagerPanelsStorage.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Load a page variant entity.
   *
   * @param string $id
   *   The page variant entity's id.
   *
   * @return \Drupal\page_manager\PageVariantInterface
   */
  protected function loadPageVariant($id) {
    return $this->entityTypeManager->getStorage('page_variant')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function save(PanelsDisplayVariant $panels_display) {
    $id = $panels_display->getStorageId();
    if ($id && ($page_variant = $this->loadPageVariant($id))) {
      $variant_plugin = $page_variant->getVariantPlugin();
      if (!($variant_plugin instanceof PanelsDisplayVariant)) {
        throw new \Exception("Page variant doesn't use a Panels display variant");
      }
      $variant_plugin->setConfiguration($panels_display->getConfiguration());
      $page_variant->save();
    }
    else {
      throw new \Exception("Couldn't find page variant to store Panels display");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    if ($page_variant = $this->loadPageVariant($id)) {
      $panels_display = $page_variant->getVariantPlugin();

      // If this page variant doesn't have a Panels display on it, then we treat
      // it the same as if there was no such page variant.
      if (!($panels_display instanceof PanelsDisplayVariant)) {
        return NULL;
      }

      // Pass down the contexts because the display has no other way to get them
      // from the variant.
      $panels_display->setContexts($page_variant->getContexts());

      return $panels_display;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($id, $op, AccountInterface $account) {
    if ($op == 'change layout') {
      $op = 'update';
    }
    if ($page_variant = $this->loadPageVariant($id)) {
      return $page_variant->access($op, $account, TRUE);
    }

    return AccessResult::forbidden();
  }

}
