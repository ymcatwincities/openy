<?php

namespace Drupal\openy_system;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigManager;
use Drupal\migrate\Plugin\MigrationPluginManager;

/**
 * Openy modules manager.
 */
class OpenyModulesManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The contact settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Migration manager.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Migration manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * Configs prefixes that protected from manual deleting.
   *
   * This configs will be deleted with entity bundle removing automatically,
   * so no need to remove them manually.
   *
   * @var array
   * @see OpenyModulesManager->removeEntityBundle().
   */
  protected $protectedConfigPrefixes = [
    'core.entity_form_display',
    'field.field',
    'field.storage',
    'paragraphs.paragraphs_type',
    'node.type',
    'media.type',
    'taxonomy.vocabulary',
    'block_content.type',
  ];

  /**
   * Configs prefixes that contain entity bundles and protected from deleting.
   *
   * This configs will be deleted with entity bundle removing automatically,
   * so no need to remove them manually.
   *
   * @var array
   * @see OpenyModulesManager->removeEntityBundle().
   */
  protected $entityBundlesConfigPrefixes = [
    'node' => [
      'prefix' => 'node.type',
      'config_entity_type' => 'node_type',
      'bundle_field' => 'type',
    ],
    'paragraph' => [
      'prefix' => 'paragraphs.paragraphs_type',
      'config_entity_type' => 'paragraphs_type',
      'bundle_field' => 'type',
    ],
    'media' => [
      'prefix' => 'media.type',
      'config_entity_type' => 'media_type',
      'bundle_field' => 'bundle',
    ],
    'taxonomy_term' => [
      'prefix' => 'taxonomy.vocabulary',
      'config_entity_type' => 'taxonomy_vocabulary',
      'bundle_field' => 'vid',
    ],
    'block_content' => [
      'prefix' => 'block_content.type',
      'config_entity_type' => 'block_content_type',
      'bundle_field' => 'type',
    ],
  ];

  /**
   * Constructs a OpenyModulesManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, MigrationPluginManager $migration_manager, ConfigManager $config_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->migrationManager = $migration_manager;
    $this->configManager = $config_manager;
  }

  /**
   * Remove Entity bundle.
   *
   * This is helper function for modules uninstall with correct bundle
   * deleting and content cleanup.
   *
   * @param string $content_entity_type
   *   Content entity type (node, block_content, paragraph, etc.)
   * @param string $config_entity_type
   *   Config entity type (node_type, block_content_type, paragraphs_type, etc.)
   * @param string $bundle
   *   Entity bundle machine name.
   * @param string $field
   *   Content entity field name that contain reference to bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeEntityBundle($content_entity_type, $config_entity_type, $bundle, $field = 'type') {
    $content_removed = FALSE;
    if ($content_entity_type) {
      // Remove existing data of content entity.
      $query = $this->entityTypeManager
        ->getStorage($content_entity_type)
        ->getQuery('AND')
        ->condition($field, $bundle);

      $ids = $query->execute();
      $content_removed = $this->removeContent($content_entity_type, $config_entity_type, $bundle, $ids);
    }

    if ($config_entity_type && $content_removed) {
      // Remove bundle.
      $config_entity_type_bundle = $this->entityTypeManager
        ->getStorage($config_entity_type)
        ->load($bundle);
      if ($config_entity_type_bundle) {
        try {
          $config_entity_type_bundle->delete();
        }
        catch (\Exception $e) {
          watchdog_exception('openy', $e, "Error! Can't delete config entity 
          @config_entity_type bundle <b>@bundle</b>, please @remove.", [
            '@config_entity_type' => $config_entity_type,
            '@bundle' => $bundle,
            '@remove' => $this->getLink($config_entity_type, 'remove it manually', $bundle),
          ], RfcLogLevel::NOTICE);
        }
      }
    }
  }

  /**
   * Helper function for entity content deleting.
   *
   * @param string $content_entity_type
   *   Content entity type (node, block_content, paragraph, etc.)
   * @param string $config_entity_type
   *   Config entity type (node_type, block_content_type, paragraphs_type, etc.)
   * @param string $bundle
   *   Entity bundle machine name.
   * @param array $ids
   *   List of entity ID's that will be deleted.
   *
   * @return bool
   *   Operation success status.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeContent($content_entity_type, $config_entity_type, $bundle, array $ids) {
    $chunks = array_chunk($ids, 50);
    $storage_handler = $this->entityTypeManager->getStorage($content_entity_type);
    $operation_success = TRUE;
    foreach ($chunks as $chunk) {
      $entities = $storage_handler->loadMultiple($chunk);

      if ($content_entity_type == 'paragraph') {
        // For paragraph - additional to entity deleting we need to re-save
        // parent entity.
        foreach ($entities as $paragraph) {
          $parent_type = $paragraph->get('parent_type')->value;
          $parent_field_name = $paragraph->get('parent_field_name')->value;
          $parent_id = $paragraph->get('parent_id')->value;
          $paragraph_id = $paragraph->id();
          try {
            $paragraph->delete();
          }
          catch (\Exception $e) {
            // Generate link to parent entity edit page.
            $url = Url::fromUserInput("/$parent_type/$parent_id/edit");
            $link = Link::fromTextAndUrl('parent node', $url)->toRenderable();

            watchdog_exception('openy', $e, "Error! Can't delete @entity_type bundle <b>@bundle</b>,
            please edit @parentEntity and remove this paragraph manually.<br>
            After this @removeBundle @config_entity_type bundle <b>@bundle</b>.", [
              '@entity_type' => $content_entity_type,
              '@config_entity_type' => $config_entity_type,
              '@bundle' => $bundle,
              '@ids' => implode(', ', $ids),
              '@parentEntity' => render($link),
              '@removeBundle' => $this->getLink($config_entity_type, 'delete config entity', $bundle),
            ], RfcLogLevel::NOTICE);
            $operation_success = FALSE;
          }
          // Check that parent entity type is not paragraph.
          if ($parent_type && $parent_id && $parent_type != 'paragraph') {
            // Load parent entity after paragraph deleting and re-save.
            $storage = $this->entityTypeManager->getStorage($parent_type);
            $storage->resetCache([$parent_id]);
            $parent_entity = $storage->load($parent_id);

            if ($parent_entity) {
              $clean_field_data = [];
              foreach ($parent_entity->get($parent_field_name) as $value) {
                // Delete removed paragraph reference.
                if ($value->target_id !== $paragraph_id) {
                  $clean_field_data[] = [
                    'target_id' => $value->target_id,
                    'target_revision_id' => $value->target_revision_id,
                  ];
                }
              }
              $parent_entity->set($parent_field_name, $clean_field_data);
              $parent_entity->save();
            }
          }
        }
      }
      else {
        try {
          $storage_handler->delete($entities);
        }
        catch (\Exception $e) {
          watchdog_exception('openy', $e, "Error! Can't delete content related 
          to @entity_type bundle <b>@bundle</b>, please @removeContent (Entity ID's - @ids).<br>
          After this @removeBundle @config_entity_type bundle <b>@bundle</b>.", [
            '@entity_type' => $content_entity_type,
            '@config_entity_type' => $config_entity_type,
            '@bundle' => $bundle,
            '@ids' => implode(', ', $ids),
            '@removeContent' => $this->getLink($content_entity_type, 'remove it manually'),
            '@removeBundle' => $this->getLink($config_entity_type, 'delete config entity', $bundle),
          ], RfcLogLevel::NOTICE);
          $operation_success = FALSE;
        }
      }
    }
    return $operation_success;
  }


  /**
   * Destroy database migration data for migrations dependent from module.
   *
   * @param string $module_name
   *   Module for destroy data.
   *   Module should be added as enforced dependency in migration config.
   */
  public function destroyMigrationData($module_name) {
    if (empty($module_name)) {
      return;
    }
    // Get config entities that are dependent on module.
    $dependencies = $this->configManager->findConfigEntityDependentsAsEntities('module', (array) $module_name);
    // Create array of dependent migrations for module.
    // That module should be listed in dependencies of migration config.
    foreach ($dependencies as $dependency) {
      /** @var \Drupal\migrate_plus\Entity\Migration $dependency */
      if ($dependency->getEntityTypeId() == 'migration') {
        $migration_list[] = $dependency->get('id');
      }
    }
    if (!empty($migration_list)) {
      $migrations = $this->migrationManager->createInstances($migration_list);
      /** @var \Drupal\migrate\Plugin\Migration $migration */
      foreach ($migrations as $migration) {
        // Remove migration data in DB for this migration.
        $migration->getIdMap()->destroy();
      }
    }
  }

  /**
   * Helper function for ling generation.
   *
   * @param string $entity_type
   *   Config or content entity type.
   * @param string $link_text
   *   Text that will be displayed inside link.
   * @param string $bundle
   *   For config entity type need to set bundle (it used in delete link).
   *
   * @return bool|string
   *   Rendered html link or FALSE.
   */
  public function getLink($entity_type, $link_text = 'here', $bundle = NULL) {
    $url = FALSE;
    switch ($entity_type) {
      case 'node':
        $url = Url::fromUserInput('/admin/content');
        break;

      case 'node_type':
        $url = Url::fromRoute('entity.node_type.delete_form', ['node_type' => $bundle]);
        break;

      case 'block_content':
        $url = Url::fromRoute('entity.block_content.collection');
        break;

      case 'block_content_type':
        $url = Url::fromRoute('entity.block_content_type.delete_form', ['block_content_type' => $bundle]);
        break;

      case 'media':
        $url = Url::fromUserInput('/admin/content/media');
        break;

      case 'media_type':
        $url = Url::fromUserInput("/admin/structure/media/manage/$bundle/delete");
        break;

      case 'webform_submission':
        $url = Url::fromRoute('entity.webform_submission.collection');
        break;

      case 'webform':
        $url = Url::fromUserInput('/admin/structure/webform');
        break;

      case 'paragraphs_type':
        $url = Url::fromRoute('entity.paragraphs_type.delete_form', ['paragraphs_type' => $bundle]);
        break;
    }

    if (!$url) {
      return FALSE;
    }

    $link = Link::fromTextAndUrl($link_text, $url)->toRenderable();
    return render($link);
  }

  /**
   * Remove non-protected configs that related to module from active storage.
   *
   * @param array $module_configs
   *   Module configs to delete.
   */
  public function removeModuleConfigs(array $module_configs) {
    $module_configs_filtered = array_filter($module_configs, [$this, 'filterConfigsList']);
    if (empty($module_configs_filtered)) {
      return;
    }
    foreach ($module_configs_filtered as $config) {
      $this->configFactory->getEditable($config)->delete();
    }
  }

  /**
   * Callback function for array_filter.
   *
   * Check if config name contain protected prefix.
   *
   * @param string $config_name
   *   Config for checking.
   *
   * @return bool
   *   Return FALSE if config contain protected config prefix.
   */
  public function filterConfigsList($config_name) {
    foreach ($this->protectedConfigPrefixes as $prefix) {
      if (strpos($config_name, $prefix) !== FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Find entity bundles in module configs list.
   *
   * @param array $module_configs
   *   Module configs.
   *
   * @return array
   *   Entities list with entity info.
   */
  public function getEntityBundles(array $module_configs) {
    $list = [];
    foreach ($this->entityBundlesConfigPrefixes as $entity_type => $data) {
      $filtered_array = preg_grep('/^' . $data['prefix'] . '.*/', $module_configs);
      if (empty($filtered_array)) {
        continue;
      }

      foreach ($filtered_array as $config_name) {
        // Example - paragraphs.paragraphs_type.blog_posts_listing.
        // Example - taxonomy.vocabulary.amenities.
        // Example - node.type.landing_page.
        // Last array item always entity bundle name.
        $entity_info = explode('.', $config_name);
        $list[] = [
          'entity_type' => $entity_type,
          'config_entity_type' => $data['config_entity_type'],
          'bundle' => end($entity_info),
          'bundle_field' => $data['bundle_field'],
        ];
      }
    }

    return $list;
  }

  /**
   * Uninstall action for OpenY modules.
   *
   * @param string $module_name
   *   Module for uninstall.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see openy_system_modules_uninstalled
   */
  public function postUninstall($module_name) {
    // Get install and optional configs list for module_name.
    $module_path = drupal_get_path('module', $module_name);
    $file_storage_install = new FileStorage($module_path . '/config/install');
    $module_install_configs = $file_storage_install->listAll();
    $file_storage_optional = new FileStorage($module_path . '/config/optional');
    $module_optional_configs = $file_storage_optional->listAll();
    // Find entity bundles based on configs list.
    $entity_bundles_list = $this->getEntityBundles($module_install_configs);
    foreach ($entity_bundles_list as $data) {
      // Delete entity data for bundle and config entity type.
      $this->removeEntityBundle($data['entity_type'], $data['config_entity_type'], $data['bundle'], $data['bundle_field']);
    }
    $this->removeModuleConfigs(array_merge($module_install_configs, $module_optional_configs));
  }

}
