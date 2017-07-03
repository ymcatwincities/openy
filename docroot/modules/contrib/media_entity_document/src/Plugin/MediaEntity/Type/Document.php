<?php

namespace Drupal\media_entity_document\Plugin\MediaEntity\Type;

use Drupal\media_entity\MediaBundleInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;

/**
 * Provides media type plugin for Document.
 *
 * @MediaType(
 *   id = "document",
 *   label = @Translation("Document"),
 *   description = @Translation("Provides business logic and metadata for local documents."),
 *   allowed_field_types = {
 *     "file"
 *   }
 * )
 */
class Document extends MediaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'mime' => $this->t('File MIME'),
      'size' => $this->t('Size'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $source_field = $this->configuration['source_field'];

    // Get the file document.
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->{$source_field}->entity;

    // Return the field.
    switch ($name) {
      case 'mime':
        return !$file->filemime->isEmpty() ? $file->getMimeType() : FALSE;

      case 'size':
        $size = $file->getSize();
        return $size ? $size : FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    $source_field = $this->configuration['source_field'];
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->{$source_field}->entity;

    if ($file) {
      $mimetype = $file->getMimeType();
      $mimetype = explode('/', $mimetype);
      $thumbnail = $this->config->get('icon_base') . "/{$mimetype[0]}-{$mimetype[1]}.png";

      if (!is_file($thumbnail)) {
        $thumbnail = $this->config->get('icon_base') . "/{$mimetype[1]}.png";

        if (!is_file($thumbnail)) {
          $thumbnail = $this->config->get('icon_base') . '/document.png';
        }
      }
    }
    else {
      $thumbnail = $this->config->get('icon_base') . '/document.png';
    }

    return $thumbnail;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {
    // The default name will be the filename of the source_field, if present.
    $source_field = $this->configuration['source_field'];

    /** @var \Drupal\file\FileInterface $file */
    if (!empty($source_field) && ($file = $media->{$source_field}->entity)) {
      return $file->getFilename();
    }

    return parent::getDefaultName($media);
  }

  /**
   * {@inheritdoc}
   */
  protected function createSourceFieldStorage() {
    return $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->create([
        'entity_type' => 'media',
        'field_name' => $this->getSourceFieldName(),
        'type' => 'file',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function createSourceField(MediaBundleInterface $bundle) {
    return $this->entityTypeManager
      ->getStorage('field_config')
      ->create([
        'field_storage' => $this->getSourceFieldStorage(),
        'bundle' => $bundle->id(),
        'required' => TRUE,
        'label' => $this->t('Source file'),
      ]);
  }

}
