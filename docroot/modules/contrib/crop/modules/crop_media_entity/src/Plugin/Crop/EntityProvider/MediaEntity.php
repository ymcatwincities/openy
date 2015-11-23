<?php

/**
 * @file
 * Contains \Drupal\crop_media_entity\Plugin\EntityProvider\MediaEntity.
 */

namespace Drupal\crop_media_entity\Plugin\Crop\EntityProvider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\crop\EntityProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media entity crop integration.
 *
 * @CropEntityProvider(
 *   entity_type = "media",
 *   label = @Translation("Media"),
 *   description = @Translation("Provides crop integration for media entity.")
 * )
 */
class MediaEntity extends EntityProviderBase implements ContainerFactoryPluginInterface {

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs media entity integration plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function uri(EntityInterface $entity) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $this->entityManager->getStorage('media_bundle')->load($entity->bundle());
    $image_field = $bundle->getThirdPartySetting('crop', 'image_field');

    if ($entity->{$image_field}->first()->isEmpty()) {
      return FALSE;
    }

    /** @var \Drupal\file\FileInterface $image */
    $image = $this->entityManager->getStorage('file')->load($entity->{$image_field}->target_id);

    if (strpos($image->getMimeType(), 'image') !== 0) {
      return FALSE;
    }

    return $image->getFileUri();
  }

}
