<?php

namespace Drupal\openy_tools;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class DrupalEntityDoubleQuotes
 *
 * @package Drupal\openy_tools
 */
class DrupalEntityDoubleQuotes {

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
      'block_content_revision__body',
      'block_content_revision__field_block_content',
      'block_content_r__6de56f762b',
      'block_content_r__d31902f689',
      'block_content_r__df5185bd6d',
      'block_content__body',
      'block_content__field_block_content',
      'block_content__field_ygtc_content_date_before',
      'block_content__field_ygtc_content_date_between',
      'block_content__field_ygtc_content_date_end',
      'mapping__field_cdn_prd_panorama',
      'node_revision__field_lead_description',
      'node_revision__field_secondary_sidebar',
      'node_revision__field_sidebar',
      'node_revision__field_summary',
      'node_revision__field_title_description',
      'node_revision__field_ygtc_content',
      'node__field_lead_description',
      'node__field_secondary_sidebar',
      'node__field_sidebar',
      'node__field_summary',
      'node__field_title_description',
      'node__field_ygtc_content',
      'paragraph_revision__field_prgf_description',
      'paragraph__field_prgf_description',
    ];
  }

  /**
   * Fix double quotes inside <drupal-entity>.
   */
  public function fixDoubleQuotes() {
    $disappeared = [];
    $problems = [];

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
            ->fields('t', [$field, 'entity_id', 'revision_id'])
            ->execute();

          while ($data = $result->fetchObject()) {
            $replace = [];

            preg_match_all("/data-caption=\"\"(.+)\"\"/imU", $data->{$field}, $test);
            if ($test && $test[0] && $test[0][0]) {
              foreach ($test[0] as $i => $item) {

                // Check if found data is valid.
                if (!isset($test[1][$i])) {
                  $this->loggerChannel->warning(sprintf("Invalid data were found while replacing."));
                  continue;
                }

                // Skip already corrupted items.
                if (strpos($item, 'data-caption="" ') !== FALSE) {
                  $disappeared[$table][] = [
                    'data' => $item,
                    'entity_id' => $data->entity_id,
                    'revision_id' => $data->revision_id,
                  ];
                  continue;
                }

                // Log each available problem.
                $problems[$table][] = [
                  'data' => $item,
                  'entity_id' => $data->entity_id,
                  'revision_id' => $data->revision_id,
                ];

                // Prepare replacement array.
                $replace['from'][] = $item;
                $replace['to'][] = sprintf('data-caption="%s"', $test[1][$i]);
              }
            }

            if (!empty($replace)) {
              $updated = str_replace($replace['from'], $replace['to'], $data->{$field});
              $db->update($table)
                ->fields([
                  $field => $updated,
                ])
                ->condition('entity_id', $data->entity_id)
                ->condition('revision_id', $data->revision_id)
                ->execute();

              $this->loggerChannel->info(sprintf('Fixed double quotes for entity_id: %d, revision_id: %d in table: %s, field: %s', $data->entity_id, $data->revision_id, $table, $field));
            }

          }

          break;
        }
      }
    }
  }

}
