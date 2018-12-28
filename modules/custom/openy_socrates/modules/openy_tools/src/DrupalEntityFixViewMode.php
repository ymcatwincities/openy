<?php

namespace Drupal\openy_tools;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class DrupalEntityFixViewMode.
 *
 * @package Drupal\openy_tools
 */
class DrupalEntityFixViewMode {

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * FixEntityEmbed constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   * Logger channel.
   */
  public function __construct(LoggerChannelInterface $loggerChannel) {
    $this->loggerChannel = $loggerChannel;
  }

  /**
   * Get tables for replacement.
   *
   * @return array
   *   A list of tables & fields with embed_entity instances.
   */
  protected function getTables() {
    return [
      'node_revision__field_ygtc_content',
      'node__field_ygtc_content',
    ];
  }

  /**
   * Fix double quotes inside <drupal-entity>.
   */
  public function fixDisplay() {
    // Prepare replacement array.
    $replaceDisplay['from'][] = 'data-entity-embed-display="entity_reference:entity_reference_entity_view"';
    $replaceDisplay['to'][] = 'data-entity-embed-display="entity_reference:media_thumbnail"';
    $replaceSettings['from'][] = 'data-entity-embed-display-settings="{&quot;view_mode&quot;:&quot;embedded_full&quot;}"';
    $replaceSettings['to'][] = 'data-entity-embed-display-settings="{&quot;image_style&quot;:&quot;&quot;,&quot;image_link&quot;:&quot;&quot;}"';

    $db = \Drupal::database();
    $tables = $this->getTables();
    foreach ($tables as $table) {
      $result = $db->query('SHOW columns FROM '. $table)->fetchAll();

      foreach ($result as $column) {
        // Find only column which has '_value' suffix.
        $field = $column->Field;
        $name = substr($field, -6, 6);
        if ($name == '_value' && $column->Type == 'longtext') {
          $result = $db->select($table, 't')
            ->fields('t')
            ->execute();

          while ($data = $result->fetchObject()) {
            $replaceEmbed['from'] = [];
            $replaceEmbed['to'] = [];

            // Process only blog items.
            if ($data->bundle != 'blog') {
              continue;
            }

            preg_match_all("/<drupal-entity.*\"embed_image\".*><\/drupal-entity>/miUs", $data->{$field}, $test);

            if (isset($test[0]) && isset($test[0][0])) {
              foreach ($test[0] as $embedItem) {
                $replaceEmbed['from'][] = $embedItem;
                $updatedEmbed = str_replace($replaceDisplay['from'], $replaceDisplay['to'], $embedItem);
                $updatedEmbed = str_replace($replaceSettings['from'], $replaceSettings['to'], $updatedEmbed);
                $replaceEmbed['to'][] = $updatedEmbed;
              }

              $updated = str_replace($replaceEmbed['from'], $replaceEmbed['to'], $data->{$field});
              $db->update($table)
                ->fields([
                  $field => $updated,
                ])
                ->condition('entity_id', $data->entity_id)
                ->condition('revision_id', $data->revision_id)
                ->execute();

              $this->loggerChannel->info(sprintf('Fixed display mode for entity_id: %d, revision_id: %d in table: %s, field: %s', $data->entity_id, $data->revision_id, $table, $field));
            }
          }
        }
      }
    }
  }

}
