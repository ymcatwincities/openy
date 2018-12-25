<?php

namespace Drupal\panelizer\Plugin\PanelsStorage;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctools\Context\AutomaticContext;
use Drupal\panelizer\Exception\PanelizerException;
use Drupal\panelizer\PanelizerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels\Storage\PanelsStorageBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Panels storage service that stores Panels displays in the Panelizer field.
 *
 * @PanelsStorage("panelizer_field")
 */
class PanelizerFieldPanelsStorage extends PanelsStorageBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\panelizer\PanelizerInterface
   */
  protected $panelizer;

  /**
   * Constructs a PanelizerDefaultPanelsStorage.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PanelizerInterface $panelizer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->panelizer = $panelizer;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('panelizer')
    );
  }

  /**
   * Gets the underlying entity from storage.
   *
   * @param $id
   *   The storage service id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   */
  protected function loadEntity($id) {
    list ($entity_type, $id, , $revision_id) = array_pad(explode(':', $id), 4, NULL);

    $storage = $this->entityTypeManager->getStorage($entity_type);
    if ($revision_id) {
      $entity = $storage->loadRevision($revision_id);
    }
    else {
      $entity = $storage->load($id);
    }

    return $entity;
  }

  /**
   * Returns the entity context.
   *
   * Wraps creating new Context objects to avoid typed data in tests.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Plugin\Context\Context
   *   The context.
   */
  protected function getEntityContext($entity_type_id, EntityInterface $entity) {
    return new AutomaticContext(new ContextDefinition('entity:' . $entity_type_id, NULL, TRUE), $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    if ($entity = $this->loadEntity($id)) {
      list ($entity_type_id, , $view_mode) = explode(':', $id);
      if ($panels_display = $this->panelizer->getPanelsDisplay($entity, $view_mode)) {
        // Set the entity as a context on the Panels display.
        $contexts = [
          '@panelizer.entity_context:entity' => $this->getEntityContext($entity_type_id, $entity),
        ];
        $panels_display->setContexts($contexts);
        return $panels_display;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(PanelsDisplayVariant $panels_display) {
    $id = $panels_display->getStorageId();
    if ($entity = $this->loadEntity($id)) {
      list (,, $view_mode) = explode(':', $id);
      // If we're dealing with an entity that has a documented default, we
      // don't want to lose that information when we save our customizations.
      // This enables us to revert to the correct default at a later date.
      if ($entity instanceof FieldableEntityInterface) {
        $default = NULL;
        if ($entity->hasField('panelizer') && $entity->panelizer->first()) {
          foreach ($entity->panelizer as $item) {
            if ($item->view_mode == $view_mode) {
              $default = $item->default;
              break;
            }
          }
        }
        try {
          $this->panelizer->setPanelsDisplay($entity, $view_mode, $default, $panels_display);
        }
        catch (PanelizerException $e) {
          // Translate to expected exception type.
          throw new \Exception($e->getMessage());
        }
      }
    }
    else {
      throw new \Exception("Couldn't find entity to store Panels display on");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($id, $op, AccountInterface $account) {
    if ($entity = $this->loadEntity($id)) {
      $access = AccessResult::neutral()
        ->addCacheableDependency($account);

      // We do not support "create", as this method's interface dictates,
      // because we work with existing entities here.
      $entity_operations = [
        'read' => 'view',
        'update' => 'update',
        'delete'=> 'delete',
        'change layout' => 'update',
      ];
      // Do not add entity cacheability metadata to the forbidden result,
      // because it depends on the Panels operation, and not on the entity.
      $access->orIf(isset($entity_operations[$op]) ? $entity->access($entity_operations[$op], $account, TRUE) : AccessResult::forbidden());

      if (!$access->isForbidden() && $entity instanceof FieldableEntityInterface) {
        list (,, $view_mode) = explode(':', $id);
        if ($op == 'change layout') {
          if ($this->panelizer->hasEntityPermission('change layout', $entity, $view_mode, $account)) {
            return $access->orIf(AccessResult::allowed());
          }
        }
        else if ($op == 'read' || $this->panelizer->hasEntityPermission('change content', $entity, $view_mode, $account)) {
          return $access->orIf(AccessResult::allowed());
        }
      }
    }

    return AccessResult::forbidden();
  }

}
