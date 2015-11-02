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
 * Source plugin for file content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_file"
 * )
 */
class YmcaMigrateFile extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('legacy__file', 'f')
      ->fields('f', ['id', 'url']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('File id'),
      'url' => $this->t('File url'),
      'name' => $this->t('File name'),
      'filepath' => $this->t('File path'),
      'filemime' => $this->t('File MIME'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'f',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $config = \Drupal::config('ymca_migrate.settings');

    $url = $config->get('url_prefix') . $row->getSourceProperty('url');
    $filename = basename($url);
    $row->setSourceProperty('name', $filename);

    // Use cached file if exists.
    $cached = $config->get('cache_dir') . '/' . $filename;
    if (file_exists($cached)) {
      $file = file_get_contents($cached);
    }
    else {
      $file = file_get_contents($url);
      if ($file === FALSE) {
        $this->idMap->saveMessage($this->getCurrentIds(), $this->t('Cannot download @file', array('@file' => $url)), MigrationInterface::MESSAGE_ERROR);
        $this->next();
        // @todo test this. Also we need to catch non existent files.
        return parent::prepareRow($row);
      }
      file_put_contents($cached, $file);
    }

    $file_uri = file_unmanaged_save_data($file, 'temporary://' . $filename);

    $this->idMap->saveMessage($this->getCurrentIds(), $this->t('Processing a file: @file', array('@file' => $url)), MigrationInterface::MESSAGE_INFORMATIONAL);
    $file_path = \Drupal::service('file_system')->realpath($file_uri);
    $row->setSourceProperty('filepath', $file_path);

    $file_mime = mime_content_type($file_path);
    $row->setSourceProperty('filemime', $file_mime);

    return parent::prepareRow($row);
  }

}
