<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateFile.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for file:image content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_file_image"
 * )
 */
class YmcaMigrateFileImage extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('shared_asset', 'a')
      ->fields('a', ['asset_id', 'file_key', 'name'])
      ->fields('ae', ['extension']);
    $query->join(
      'shared_asset_extension',
      'ae',
      'a.extension_id = ae.asset_extension_id'
    );
    $query->condition(
      'a.asset_id',
      [
        11734,
        11714,
        11712,
        11709,
        11707,
        8374,
        8375,
        8376,
        1929,
        9144,
        10312,
        9347,
        11599,
        11633,
        11663,
        9123,
        9568,
      ],
      'IN'
    );
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'asset_id' => $this->t('Asset ID'),
      'file_key' => $this->t('File key'),
      'name' => $this->t('File name (without extension)'),
      'filename' => $this->t('File name'),
      'filepath' => $this->t('File path'),
      'extension' => $this->t('Extension'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'asset_id' => [
        'type' => 'integer',
        'alias' => 'a',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $config = \Drupal::config('ymca_migrate.settings');

    $file_key = $row->getSourceProperty('file_key');
    $file_extension = $row->getSourceProperty('extension');
    $path_rel = 'asset/' . $file_key[0] . '/' . $file_key . '/' . $file_key . '.' . $file_extension;
    $url = $config->get('url_prefix') . $path_rel;
    $structure = dirname($path_rel);
    $filename = basename($url);
    $cache_dir = $config->get('cache_dir');

    // Use cached file if exists.
    $cached = $cache_dir . '/' . $structure . '/' . $filename;
    if (file_exists($cached)) {
      $file = file_get_contents($cached);
    }
    else {
      $file = file_get_contents($url);

      if ($file === FALSE) {
        $this->idMap->saveMessage(
          $this->getCurrentIds(),
          $this->t('Cannot download @file', array('@file' => $url)),
          MigrationInterface::MESSAGE_ERROR
        );
        return FALSE;
      }

      // Saving a file with a path.
      $full_dir = $cache_dir . '/' . $structure;
      if (!file_exists($full_dir)) {
        mkdir($full_dir, 0764, TRUE);
      }
      file_put_contents($cached, $file);
    }

    $filename_human = $row->getSourceProperty(
        'name'
      ) . '.' . $row->getSourceProperty('extension');

    $file_uri = file_unmanaged_save_data(
      $file,
      'temporary://' . $filename_human
    );
    $file_path = \Drupal::service('file_system')->realpath($file_uri);
    $file_mime = mime_content_type($file_path);

    $row->setSourceProperty('filemime', $file_mime);
    $row->setSourceProperty('filename', $filename_human);
    $row->setSourceProperty('filepath', $file_path);

    return parent::prepareRow($row);
  }

}
