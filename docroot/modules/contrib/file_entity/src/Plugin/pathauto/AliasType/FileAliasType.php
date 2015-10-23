<?php

/**
 * @file
 * Contains \Drupal\file_entity\Plugin\AliasType\FileAliasType.
 */

namespace Drupal\file_entity\Plugin\pathauto\AliasType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\pathauto\AliasTypeBatchUpdateInterface;
use Drupal\pathauto\Plugin\pathauto\AliasType\EntityAliasTypeBase;

/**
 * A pathauto alias type plugin for file entities.
 *
 * @AliasType(
 *   id = "file",
 *   label = @Translation("File"),
 *   types = {"file"},
 * )
 */
class FileAliasType extends EntityAliasTypeBase implements AliasTypeBatchUpdateInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getPatternDescription() {
    return $this->t('Default path pattern (applies to all files with blank patterns below)');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('default' => array('files/[file:name]')) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function batchUpdate(&$context) {
    if (!isset($context['sandbox']['current'])) {
      $context['sandbox']['count'] = 0;
      $context['sandbox']['current'] = 0;
    }

    $query = db_select('file_managed', 'fm');
    $query->leftJoin('url_alias', 'ua', "CONCAT('file/', fm.fid) = ua.source");
    $query->addField('fm', 'fid');
    $query->isNull('ua.source');
    $query->condition('fm.fid', $context['sandbox']['current'], '>');
    $query->orderBy('fm.fid');
    $query->addTag('pathauto_bulk_update');
    $query->addMetaData('entity', 'file');

    // Get the total amount of items to process.
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $query->countQuery()->execute()->fetchField();

      // If there are no files to update, the stop immediately.
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $query->range(0, 25);
    $fids = $query->execute()->fetchCol();

    $options = array('message' => FALSE);

    $files = File::loadMultiple($fids);
    foreach ($files as $file) {
      \Drupal::service('pathauto.manager')->updateAlias($file, 'bulkupdate', $options);
    }

    if (!empty($options['message'])) {
      drupal_set_message(\Drupal::translation()->formatPlural(count($fids), 'Updated URL alias for 1 file.', 'Updated URL aliases for @count files.'));
    }

    $context['sandbox']['count'] += count($fids);
    $context['sandbox']['current'] = max($fids);
    $context['message'] = t('Updated alias for file @fid.', array('@fid' => end($fids)));

    if ($context['sandbox']['count'] != $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['count'] / $context['sandbox']['total'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePrefix() {
    return 'file/';
  }

}
