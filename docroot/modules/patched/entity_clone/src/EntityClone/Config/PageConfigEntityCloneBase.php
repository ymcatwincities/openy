<?php

namespace Drupal\entity_clone\EntityClone\Config;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Utility\Random;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\page_manager\Entity\PageVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PageConfigEntityCloneBase.
 */
class PageConfigEntityCloneBase extends ConfigEntityCloneBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Uuid generator.
   *
   * @var Php
   */
  protected $uuid;

  /**
   * Constructs a new PageConfigEntityCloneBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  public function __construct(EntityTypeManager $entity_type_manager, Php $uuid, $entity_type_id) {
    $this->entityTypeManager = $entity_type_manager;
    $this->uuid = $uuid;
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('uuid'),
      $entity_type->id()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, $properties = []) {
    /** @var \Drupal\core\Config\Entity\ConfigEntityInterface $cloned_entity */
    $id_key = $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('id');
    $label_key = $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('label');
    // @todo make this dynamic if needed.
    $path_key = 'path';


    // Set new entity properties.
    if (isset($properties['id'])) {
      if ($id_key) {
        $cloned_entity->set($id_key, $properties['id']);
      }
      unset($properties['id']);
    }

    if (isset($properties['label'])) {
      if ($label_key) {
        $cloned_entity->set($label_key, $properties['label']);
      }
      unset($properties['label']);
    }

    if (isset($properties['path'])) {
      if ($path_key) {
        $cloned_entity->set($path_key, $properties['path']);
      }
      unset($properties['path']);
    }

    foreach ($properties as $key => $property) {
      $cloned_entity->set($key, $property);
    }

    // Add current user as the author of the page.
    $user = \Drupal::currentUser();
    $cloned_entity->setThirdPartySetting('ymca_page_manager', 'author', $user->id());

    // Save for now for ability to use latest data.
    $cloned_entity->save();

    $variants = $entity->getVariants();
    $rand = new Random();
    /** @var PageVariant $variant */
    foreach ($variants as $variant) {
      $hash = strtolower($rand->name(8, TRUE));
      $var = $variant->createDuplicate();
      $var->set('page', $cloned_entity->id());
      $conf = $var->getVariantPlugin()->getConfiguration();
      foreach ($conf['blocks'] as $uuid => $bdata) {
        $buuid = explode(':', $bdata['id']);
        $entities = \Drupal::entityManager()->getStorage($buuid[0])->loadByProperties(['uuid' => $buuid[1]]);
        /** @var BlockContent $block */
        $block = array_shift($entities);
        $cloner = new ConfigWithFieldEntityClone($this->entityTypeManager, $buuid[0]);
        $label = $block->info->getValue()[0]['value'] . ' ' . $hash;
        $dup_block = $cloner->cloneEntity($block, $block->createDuplicate(), ['info' => $label]);
        // Save for ability to have real uuid.
        $dup_block->save();
        $conf['blocks'][$uuid]['id'] = $dup_block->getEntityTypeId() . ':' . $dup_block->uuid();
        $conf['blocks'][$uuid]['label'] = $label;
      }
      $var->getVariantPlugin()->setConfiguration($conf);
      $new_variants[$variant->id() . $hash] = $var->set('id', $variant->id() . $hash);
      // Save for ability to add it to Page.
      $new_variants[$variant->id() . $hash]->save();
    }

    $cloned_entity->set('variants', $new_variants);

    // Add access conditions.
    $a_uuid = $this->uuid->generate();
    $access_conditions = [
      $a_uuid => [
        'id' => 'user_role',
        'roles' => [
          'authenticated' => 'authenticated',
        ],
        'negate' => FALSE,
        'context_mapping' => [
          'user' => 'current_user',
        ],
        'uuid' => $a_uuid,
      ],
    ];

    $cloned_entity->set('access_conditions', $access_conditions);
    $cloned_entity->set('access_logic', 'and');

    // Final save for cloned page.
    $cloned_entity->save();
    return $cloned_entity;

  }

}
