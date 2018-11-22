<?php

namespace Drupal\ymca_entity_embed;

use Drupal\Core\Url;
use Drupal\file\FileStorage;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\MediaStorage;

/**
 * Class LinkFinder.
 *
 * @package Drupal\ymca_entity_embed
 */
class LinkFinder {

  /**
   * Find link to file by Media UUID.
   *
   * @param string $uuid
   *   UUID of the processed media entity.
   *
   * @return mixed
   *   Alias of the file or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getFileLinkByMediaUuid($uuid) {

    /** @var MediaStorage $contribMedia */
    $contribMedia = \Drupal::entityTypeManager()->getStorage('media');
    /** @var FileStorage $fileStorage */
    $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
    /** @var Media $mediaEntity */
    $mediaEntity = $contribMedia->loadByProperties(['uuid' => $uuid]);
    $mediaEntity = reset($mediaEntity);
    $mediaBundle = $mediaEntity->bundle();
    switch ($mediaBundle) {
      case 'document':
      case 'archive':
        $fileId = $mediaEntity->field_media_document->target_id;
        if ($fileId) {
          /** @var FileEntity $fileEntity */
          $fileEntity = $fileStorage->load($fileId);
          if ($fileEntity) {
            $fileUri = $fileEntity->getFileUri();
            $fileUrl = file_create_url($fileUri);
            $url = parse_url($fileUrl);
            $alias = $url['path'];
            return $alias;
          }
        }
        break;

      case 'image':
        $fileId = $mediaEntity->field_media_image->target_id;
        if ($fileId) {
          /** @var FileEntity $fileEntity */
          $fileEntity = $fileStorage->load($fileId);
          if ($fileEntity) {
            $fileUri = $fileEntity->getFileUri();
            $fileUrl = file_create_url($fileUri);
            $url = parse_url($fileUrl);
            $alias = $url['path'];
            return $alias;
          }
        }
        break;

      default:
        break;
    }
  }

}
