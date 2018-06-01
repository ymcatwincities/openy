<?php

namespace Drupal\openy_system;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\Core\Link;

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
   * Constructs a OpenyModulesManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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

          if ($parent_type && $parent_id) {
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
        // TODO: Replace url and route after switching to core media.
        $url = Url::fromUserInput('/admin/content/media');
        break;

      case 'media_bundle':
        // TODO: Replace url and route after switching to core media.
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

}
