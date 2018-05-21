<?php

namespace Drupal\openy_tools;

use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class FieldHelper.
 */
class FixEntityEmbed {

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
      'node__field_lead_description' => ['field_lead_description_value'],
      'node__field_secondary_sidebar' => ['field_secondary_sidebar_value'],
      'node__field_sidebar' => ['field_sidebar_value'],
      'node__field_summary' => ['field_summary_value'],
      'node__field_ygtc_content' => ['field_ygtc_content_value'],
      'paragraph_revision__field_prgf_description' => ['field_prgf_description_value'],
      'paragraph__field_prgf_description' => ['field_prgf_description_value'],
      'block_content_revision__body' => ['body_value'],
      'block_content_revision__field_block_content' => ['field_block_content_value'],
      'block_content_r__6de56f762b' => ['field_ygtc_content_date_between_value'],
      'block_content_r__d31902f689' => ['field_ygtc_content_date_before_value'],
      'block_content_r__df5185bd6d' => ['field_ygtc_content_date_end_value'],
      'block_content__body' => ['body_value'],
      'block_content__field_block_content' => ['field_block_content_value'],
      'block_content__field_ygtc_content_date_before' => ['field_ygtc_content_date_before_value'],
      'block_content__field_ygtc_content_date_between' => ['field_ygtc_content_date_between_value'],
      'block_content__field_ygtc_content_date_end' => ['field_ygtc_content_date_end_value'],
      'node_revision__field_lead_description' => ['field_lead_description_value'],
      'node_revision__field_secondary_sidebar' => ['field_secondary_sidebar_value'],
      'node_revision__field_sidebar' => ['field_sidebar_value'],
      'node_revision__field_summary' => ['field_summary_value'],
      'node_revision__field_ygtc_content' => ['field_ygtc_content_value'],
    ];
  }

  /**
   * Fix file.
   */
  public function fixFile() {
    $db = \Drupal::database();
    $tables = $this->getTables();

    foreach ($tables as $table => $columns) {
      foreach ($columns as $field) {
        $result = $db->select($table, 't')
          ->fields('t')
          ->condition(
            't.' . $field,
            '%' . $db->escapeLike('drupal-entity-inline') . '%',
            'LIKE'
          )
          ->execute();

        while ($data = $result->fetchObject()) {
          $replace = [];

          preg_match_all(
            "/<drupal-entity-inline.*<\/drupal-entity-inline>/miU",
            $data->$field,
            $test
          );

          if (count($test[0])) {
            foreach ($test[0] as $drupalEntityInline) {

              // Check if there is more than one drupal-entity-inline with menu_link .
              preg_match_all("/data-entity-type=\"file\"/miU", $drupalEntityInline, $fail);
              if (count($fail[0]) >= 2) {
                $this->loggerChannel->error(sprintf('Failed to parse entities for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
                throw new \Exception('Regex is wrong');
              }
              else {
                if (count($fail[0]) == 0) {
                  continue;
                }
              }

              // Load entity properties via DOM.
              $dom = Html::load($drupalEntityInline);
              $xpath = new \DOMXPath($dom);
              foreach ($xpath->query(
                '//*[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]'
              ) as $node) {
                $uuid = $node->getAttribute('data-entity-uuid');
                $replacement = '<drupal-entity
                  data-button="0"
                  data-embed-button="embed_document"
                  data-entity-embed-display="entity_reference:entity_reference_entity_view"
                  data-entity-embed-display-settings="{&quot;view_mode&quot;:&quot;embedded_link&quot;}"
                  data-entity-type="media"
                  data-entity-uuid="' . $uuid . '"></drupal-entity>';

                // Prepare replacement array.
                $replace['from'][] = $drupalEntityInline;
                $replace['to'][] = $replacement;
              }
            }
          }

          // Replace all entities in the text.
          if ($replace) {
            $updated = str_replace($replace['from'], $replace['to'], $data->$field);
            $db->update($table)
              ->fields([
                $field => $updated,
              ])
              ->condition('entity_id', $data->entity_id)
              ->condition('revision_id', $data->revision_id)
              ->execute();

            $this->loggerChannel->info(sprintf('Fixed entity embed for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
          }
        }

      }
    }

  }

  /**
   * Fix menu_link entity.
   */
  public function fixMenuLink() {
    $db = \Drupal::database();
    $tables = $this->getTables();

    foreach ($tables as $table => $columns) {
      foreach ($columns as $field) {
        $result = $db->select($table, 't')
          ->fields('t')
          ->condition(
            't.' . $field,
            '%' . $db->escapeLike('drupal-entity-inline') . '%',
            'LIKE'
          )
          ->execute();

        while ($data = $result->fetchObject()) {
          $replace = [];

          preg_match_all(
            "/<drupal-entity-inline.*<\/drupal-entity-inline>/miU",
            $data->$field,
            $test
          );

          if (count($test[0])) {
            foreach ($test[0] as $drupalEntityInline) {

              // Check if there is more than one drupal-entity-inline with menu_link .
              preg_match_all("/data-entity-type=\"menu_link\"/miU", $drupalEntityInline, $fail);
              if (count($fail[0]) >= 2) {
                $this->loggerChannel->error(sprintf('Failed to parse entities for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
                throw new \Exception('Regex is wrong');
              }
              else {
                if (count($fail[0]) == 0) {
                  continue;
                }
              }

              // Load entity properties via DOM.
              $dom = Html::load($drupalEntityInline);
              $xpath = new \DOMXPath($dom);
              foreach ($xpath->query(
                '//*[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]'
              ) as $node) {
                $uuid = $node->getAttribute('data-entity-uuid');
                $label = $node->getAttribute('data-entity-label');
                $replacement = '<drupal-entity
                  data-button="0"
                  data-embed-button="menu_link"
                  data-entity-embed-display="entity_reference:entity_reference_label_url"
                  data-entity-embed-display-settings="{&quot;route_link&quot;:1,&quot;route_title&quot;:&quot;' . $label . '&quot;}"
                  data-entity-type="menu_link_content"
                  data-entity-uuid="' . $uuid . '"></drupal-entity>';

                // Prepare replacement array.
                $replace['from'][] = $drupalEntityInline;
                $replace['to'][] = $replacement;
              }
            }
          }

          // Replace all entities in the text.
          if ($replace) {
            $updated = str_replace($replace['from'], $replace['to'], $data->$field);
            $db->update($table)
              ->fields([
                $field => $updated,
              ])
              ->condition('entity_id', $data->entity_id)
              ->condition('revision_id', $data->revision_id)
              ->execute();

            $this->loggerChannel->info(sprintf('Fixed entity embed for entity_id: %d, revision_id: %d in table: %s', $data->entity_id, $data->revision_id, $table));
          }
        }

      }
    }

  }

}
