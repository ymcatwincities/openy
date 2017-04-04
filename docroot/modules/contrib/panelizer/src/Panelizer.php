<?php

namespace Drupal\panelizer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ctools\ContextMapperInterface;
use Drupal\panelizer\Exception\PanelizerException;
use Drupal\panelizer\Plugin\PanelizerEntityManager;
use Drupal\panels\PanelsDisplayManagerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * The Panelizer service.
 */
class Panelizer implements PanelizerInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Panelizer entity manager.
   *
   * @var \Drupal\panelizer\Plugin\PanelizerEntityManager
   */
  protected $panelizerEntityManager;

  /**
   * The Panels display manager.
   *
   * @var \Drupal\Panels\PanelsDisplayManagerInterface
   */
  protected $panelsManager;

  /**
   * The context mapper.
   *
   * @var \Drupal\ctools\ContextMapperInterface
   */
  protected $contextMapper;

  /**
   * Constructs a Panelizer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\panelizer\Plugin\PanelizerEntityManager $panelizer_entity_manager
   *   The Panelizer entity manager.
   * @param \Drupal\panels\PanelsDisplayManagerInterface $panels_manager
   *   The Panels display manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\ctools\ContextMapperInterface $context_mapper
   *   The context mapper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user, PanelizerEntityManager $panelizer_entity_manager, PanelsDisplayManagerInterface $panels_manager, TranslationInterface $string_translation, ContextMapperInterface $context_mapper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypeManager = $field_type_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->panelizerEntityManager = $panelizer_entity_manager;
    $this->panelsManager = $panels_manager;
    $this->stringTranslation = $string_translation;
    $this->contextMapper = $context_mapper;
  }

  /**
   * Gets the Panelizer entity plugin.
   *
   * @param $entity_type_id
   *   The entity type id.
   *
   * @return \Drupal\panelizer\Plugin\PanelizerEntityInterface
   */
  protected function getEntityPlugin($entity_type_id) {
    return $this->panelizerEntityManager->createInstance($entity_type_id, []);
  }

  /**
   * Load a Panels Display via an ID (Machine Name).
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant|NULL
   *   The default Panels display with the given name if it exists; otherwise
   *   NULL.
   */
  public function getDefaultPanelsDisplayByMachineName($full_machine_name) {
    list($entity_type, $bundle, $view_mode, $machine_name) = explode('__', $full_machine_name);
    /** @var \Drupal\panelizer\Panelizer $panelizer */
    // @todo this $display_id looks all wrong to me since it's the name and view_mode.
    return $this->getDefaultPanelsDisplay($machine_name, $entity_type, $bundle, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityViewDisplay($entity_type_id, $bundle, $view_mode) {
    // Check the existence and status of:
    // - the display for the view mode,
    // - the 'default' display.
    $candidate_ids = [];
    if ($view_mode != 'default') {
      $candidate_ids[] = $entity_type_id . '.' . $bundle . '.' . $view_mode;
    }
    $candidate_ids[] = $entity_type_id . '.' . $bundle . '.default';
    $results = \Drupal::entityQuery('entity_view_display')
      ->condition('id', $candidate_ids)
      ->condition('status', TRUE)
      ->execute();

    // Select the first valid candidate display, if any.
    $load_id = FALSE;
    foreach ($candidate_ids as $candidate_id) {
      if (isset($results[$candidate_id])) {
        $load_id = $candidate_id;
        break;
      }
    }

    // Use the selected display if any, or create a fresh runtime object.
    $storage = $this->entityTypeManager->getStorage('entity_view_display');
    if ($load_id) {
      $display = $storage->load($load_id);
    }
    else {
      $display = $storage->create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'mode' => $view_mode,
        'status' => TRUE,
      ]);
    }

    // Let modules alter the display.
    $display_context = [
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'view_mode' => $view_mode,
    ];
    $this->moduleHandler->alter('entity_view_display', $display, $display_context);

    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function getPanelsDisplay(FieldableEntityInterface $entity, $view_mode, EntityViewDisplayInterface $display = NULL) {
    $settings = $this->getPanelizerSettings($entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display);
    if (($settings['custom'] || $settings['allow']) && isset($entity->panelizer) && $entity->panelizer->first()) {
      /** @var \Drupal\Core\Field\FieldItemInterface[] $values */
      $values = [];
      foreach ($entity->panelizer as $item) {
        $values[$item->view_mode] = $item;
      }
      if (isset($values[$view_mode])) {
        $panelizer_item = $values[$view_mode];
        // Check for a customized display first and use that if present.
        if (!empty($panelizer_item->panels_display)) {
          // @todo: validate schema after https://www.drupal.org/node/2392057 is fixed.
          return $this->panelsManager->importDisplay($panelizer_item->panels_display, FALSE);
        }
        // If not customized, use the specified default.
        if (!empty($panelizer_item->default)) {
          // If we're using this magic key use the settings default.
          if ($panelizer_item->default == '__bundle_default__') {
            $default = $settings['default'];
          }
          else {
            $default = $panelizer_item->default;
            // Ensure the default still exists and if not fallback sanely.
            $displays = $this->getDefaultPanelsDisplays($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
            if (!isset($displays[$default])) {
              $default = $settings['default'];
            }
          }
          $panels_display = $this->getDefaultPanelsDisplay($default, $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display);
          $this->setCacheTags($panels_display, $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display, $default, $settings);
          return $panels_display;
        }
      }
    }
    // If the field has no input to give us, use the settings default.
    $panels_display = $this->getDefaultPanelsDisplay($settings['default'], $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display);
    $this->setCacheTags($panels_display, $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $display, $settings['default'], $settings);
    return $panels_display;
  }

  /**
   * Properly determine the cache tags for a display and set them.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The panels display variant.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface|NULL $display
   *   If the caller already has the correct display, it can optionally be
   *   passed in here so the Panelizer service doesn't have to look it up;
   *   otherwise, this argument can be omitted.
   * @param $default
   *   The name of the panels display we are about to render.
   * @param array $settings
   *   The default panelizer settings for this EntityViewDisplay.
   */
  protected function setCacheTags(PanelsDisplayVariant $panels_display, $entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL, $default, array $settings) {
    if (!$display) {
      $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
    }
    $display_mode = $display ? $display->getMode() : '';

    if ($default == $settings['default']) {
      $tags = ["{$panels_display->getStorageType()}:{$entity_type_id}:{$bundle}:{$display_mode}"];
    }
    $tags[] = "{$panels_display->getStorageType()}:{$entity_type_id}:{$bundle}:{$display_mode}:$default";
    $panels_display->addCacheTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function setPanelsDisplay(FieldableEntityInterface $entity, $view_mode, $default, PanelsDisplayVariant $panels_display = NULL) {
    $settings = $this->getPanelizerSettings($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    if (($settings['custom'] || $settings['allow']) && isset($entity->panelizer)) {
      $panelizer_item = NULL;
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      foreach ($entity->panelizer as $item) {
        if ($item->view_mode == $view_mode) {
          $panelizer_item = $item;
          break;
        }
      }
      if (!$panelizer_item) {
        $panelizer_item = $this->fieldTypeManager->createFieldItem($entity->panelizer, count($entity->panelizer));
        $panelizer_item->view_mode = $view_mode;
      }

      // Note: We don't call $panels_display->setStorage() here because it will
      // be set internally by PanelizerFieldType::postSave() which will know
      // the real revision ID of the newly saved entity.

      $panelizer_item->panels_display = $panels_display ? $this->panelsManager->exportDisplay($panels_display) : [];
      $panelizer_item->default = $default;

      // Create a new revision if possible.
      if ($entity instanceof RevisionableInterface && $entity->getEntityType()->isRevisionable()) {
        if ($entity->isDefaultRevision()) {
          $entity->setNewRevision(TRUE);
        }
      }

      // Updates the changed time of the entity, if necessary.
      if ($entity->getEntityType()->isSubclassOf(EntityChangedInterface::class)) {
        $entity->setChangedTime(REQUEST_TIME);
      }

      $entity->panelizer[$panelizer_item->getName()] = $panelizer_item;

      $entity->save();
    }
    else {
      throw new PanelizerException("Custom overrides not enabled on this entity, bundle and view mode");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultPanelsDisplays($entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL) {
    if (!$display) {
      $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
    }

    // Get a list of all the defaults.
    $display_config = $display->getThirdPartySetting('panelizer', 'displays', []);
    $display_names = array_keys($display_config);
    if (empty($display_names)) {
      $display_names = ['default'];
    }

    // Get each one individually.
    $panels_displays = [];
    foreach ($display_names as $name) {
      if ($panels_display = $this->getDefaultPanelsDisplay($name, $entity_type_id, $bundle, $view_mode, $display)) {
        $panels_displays[$name] = $panels_display;
      }
    }

    return $panels_displays;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultPanelsDisplay($name, $entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL) {
    if (!$display) {
      $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
      // If we still don't find a display, then we won't find a Panelizer
      // default for sure.
      if (!$display) {
        return NULL;
      }
    }

    $config = $display->getThirdPartySetting('panelizer', 'displays', []);
    if (!empty($config[$name])) {
      // Set a default just in case.
      $config[$name]['builder'] = empty($config[$name]['builder']) ? 'standard' : $config[$name]['builder'];
      // @todo: validate schema after https://www.drupal.org/node/2392057 is fixed.
      $panels_display = $this->panelsManager->importDisplay($config[$name], FALSE);
    }
    else {
      return NULL;
    }

    // @todo: Should be set when written, not here!
    $storage_id_parts = [
      $entity_type_id,
      $bundle,
      $view_mode,
      $name,
    ];
    $panels_display->setStorage('panelizer_default', implode(':', $storage_id_parts));

    return $panels_display;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultPanelsDisplay($name, $entity_type_id, $bundle, $view_mode, PanelsDisplayVariant $panels_display) {
    $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
    if (!$display) {
      throw new PanelizerException("Unable to find display for given entity type, bundle and view mode");
    }

    // Set this individual Panels display.
    $panels_displays = $display->getThirdPartySetting('panelizer', 'displays', []);
    $panels_displays[$name] = $this->panelsManager->exportDisplay($panels_display);
    $display->setThirdPartySetting('panelizer', 'displays', $panels_displays);

    $display->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayStaticContexts($name, $entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL) {
    if (!$display) {
      $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
      // If we still don't find a display, then we won't find a Panelizer
      // default for sure.
      if (!$display) {
        return NULL;
      }
    }

    $config = $display->getThirdPartySetting('panelizer', 'displays', []);
    if (!empty($config[$name]) && !empty($config[$name]['static_context'])) {
      return $this->contextMapper->getContextValues($config[$name]['static_context']);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayStaticContexts($name, $entity_type_id, $bundle, $view_mode, $contexts) {
    $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
    if (!$display) {
      throw new PanelizerException("Unable to find display for given entity type, bundle and view mode");
    }

    // Set this Panels display's static contexts.
    $panels_displays = $display->getThirdPartySetting('panelizer', 'displays', []);
    $panels_displays[$name]['static_context'] = $contexts;
    $display->setThirdPartySetting('panelizer', 'displays', $panels_displays);

    $display->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isPanelized($entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL) {
    if (!$this->getEntityPlugin($entity_type_id)) {
      return FALSE;
    }

    if (!$display) {
      $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
    }

    return $display->getThirdPartySetting('panelizer', 'enable', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getPanelizerSettings($entity_type_id, $bundle, $view_mode, EntityViewDisplayInterface $display = NULL) {
    if (!$display) {
      $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
    }

    $settings = [
      'enable' => $this->isPanelized($entity_type_id, $bundle, $view_mode, $display),
      'custom' => $display->getThirdPartySetting('panelizer', 'custom', FALSE),
      'allow' => $display->getThirdPartySetting('panelizer', 'allow', FALSE),
      'default' => $display->getThirdPartySetting('panelizer', 'default', 'default'),
    ];

    // Make sure that the Panelizer field actually exists.
    if ($settings['custom']) {
      $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
      $settings['custom'] = isset($fields['panelizer']) && $fields['panelizer']->getType() == 'panelizer';
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setPanelizerSettings($entity_type_id, $bundle, $view_mode, array $settings, EntityViewDisplayInterface $display = NULL) {
    if (!$display) {
      $display = $this->getEntityViewDisplay($entity_type_id, $bundle, $view_mode);
    }

    $display->setThirdPartySetting('panelizer', 'enable', !empty($settings['enable']));
    $display->setThirdPartySetting('panelizer', 'custom', !empty($settings['enable']) && !empty($settings['custom']));
    $display->setThirdPartySetting('panelizer', 'allow', !empty($settings['enable']) && !empty($settings['allow']));
    $display->setThirdPartySetting('panelizer', 'default', $settings['default']);

    if (!empty($settings['enable'])) {
      // Set the default display.
      $displays = $display->getThirdPartySetting('panelizer', 'displays', []);
      if (empty($displays['default'])) {
        /** @var \Drupal\panelizer\Plugin\PanelizerEntityInterface $panelizer_entity_plugin */
        $panelizer_entity_plugin = $this->panelizerEntityManager->createInstance($display->getTargetEntityTypeId(), []);
        $displays['default'] = $this->panelsManager->exportDisplay($panelizer_entity_plugin->getDefaultDisplay($display, $display->getTargetBundle(), $display->getMode()));
        $settings['default'] = "{$display->getTargetEntityTypeId()}__{$display->getTargetBundle()}__{$view_mode}__default";
        $display->setThirdPartySetting('panelizer', 'displays', $displays);
      }

      // Make sure the field exists.
      if (($settings['custom'] || $settings['allow'])) {
        $field_storage = $this->entityTypeManager->getStorage('field_storage_config')->load($entity_type_id . '.panelizer');
        if (!$field_storage) {
          $field_storage = $this->entityTypeManager->getStorage('field_storage_config')->create([
            'entity_type' => $entity_type_id,
            'field_name' => 'panelizer',
            'type' => 'panelizer',
            'cardinality' => -1,
            'settings' => [],
            'status' => TRUE,
          ]);
          $field_storage->save();
        }

        $field = $this->entityTypeManager->getStorage('field_config')->load($entity_type_id . '.' . $bundle . '.panelizer');
        if (!$field) {
          $field = $this->entityTypeManager->getStorage('field_config')->create([
            'field_storage' => $field_storage,
            'bundle' => $bundle,
            'label' => $this->t('Panelizer'),
            'settings' => [],
          ]);
          $field->save();
        }
      }
    }

    $display->save();
  }

  /**
   * Get a list of all the Panelizer operations.
   *
   * @return array
   *   Associative array of the human-readable operation names, keyed by the
   *   path.
   */
  protected function getOperations() {
    return [
      'content' => $this->t('Content'),
      'layout' => $this->t('Layout'),
      'revert' => $this->t('Revert to default'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions = [];

    // Only look at entity types that have a corresponding Panelizer plugin.
    $entity_types = array_intersect_key(
      $this->entityTypeManager->getDefinitions(),
      $this->panelizerEntityManager->getDefinitions()
    );

    foreach ($entity_types as $entity_type_id => $entity_type) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundles as $bundle => $bundle_info) {
        $permissions["administer panelizer $entity_type_id $bundle defaults"] = [
          'title' => t('%entity_name %bundle_name: Administer Panelizer default panels, allowed content and settings.', [
            '%entity_name' => $entity_type->getLabel(),
            '%bundle_name' => $bundle_info['label'],
          ]),
          'description' => t('Users with this permission can fully administer panelizer for this entity bundle.'),
        ];

        foreach ($this->getOperations() as $path => $operation) {
          $permissions["administer panelizer $entity_type_id $bundle $path"] = [
            'title' => $this->t('%entity_name %bundle_name: Administer Panelizer @operation', [
              '%entity_name' => $entity_type->getLabel(),
              '%bundle_name' => $bundle_info['label'],
              '@operation' => $operation,
            ]),
          ];
        }
      }
    }

    ksort($permissions);
    return $permissions;
  }

  /**
   * Check permission for an individual operation only.
   *
   * Doesn't check any of the baseline permissions that you need along with
   * the operation permission.
   *
   * @param string $op
   *   The operation.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if the user has permission; FALSE otherwise.
   */
  protected function hasOperationPermission($op, $entity_type_id, $bundle, AccountInterface $account) {
    switch ($op) {
      case 'change content':
        return $account->hasPermission("administer panelizer $entity_type_id $bundle content");

      case 'change layout':
        return $account->hasPermission("administer panelizer $entity_type_id $bundle layout");

      case 'revert to default':
        return $account->hasPermission("administer panelizer $entity_type_id $bundle revert");
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasEntityPermission($op, EntityInterface $entity, $view_mode, AccountInterface $account = NULL) {
    if (!$account) {
      $account = $this->currentUser->getAccount();
    }

    // Must be able to edit the entity.
    if (!$entity->access('update', $account)) {
      return FALSE;
    }

    // Must have overrides enabled.
    $panelizer_settings = $this->getPanelizerSettings($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    if (empty($panelizer_settings['custom'])) {
      return FALSE;
    }

    // Check admin permission.
    if ($account->hasPermission('administer panelizer')) {
      return TRUE;
    }

    // @todo: check field access too!

    // if ($op == 'revert to default') {
    //   // We already have enough permissions at this point.
    //   return TRUE;
    // }

    return $this->hasOperationPermission($op, $entity->getEntityTypeId(), $entity->bundle(), $account);
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefaultPermission($op, $entity_type_id, $bundle, $view_mode, $default, AccountInterface $account = NULL) {
    if (!$this->isPanelized($entity_type_id, $bundle, $view_mode)) {
      return FALSE;
    }

    if (!$account) {
      $account = $this->currentUser->getAccount();
    }

    // Check admin permissions.
    if ($account->hasPermission('administer panelizer')) {
      return TRUE;
    }
    if ($account->hasPermission("administer panelizer $entity_type_id $bundle defaults")) {
      return TRUE;
    }

    return $this->hasOperationPermission($op, $entity_type_id, $bundle, $account);
  }

}
