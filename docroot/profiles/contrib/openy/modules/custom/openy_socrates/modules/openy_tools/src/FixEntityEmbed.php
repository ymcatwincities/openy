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
   * Run data processing.
   */
  public function process() {
    $db = \Drupal::database();

    $tables = [
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
      'node__field_lead_description' => ['field_lead_description_value'],
      'node__field_secondary_sidebar' => ['field_secondary_sidebar_value'],
      'node__field_sidebar' => ['field_sidebar_value'],
      'node__field_summary' => ['field_summary_value'],
      'node__field_ygtc_content' => ['field_ygtc_content_value'],
      'paragraph_revision__field_prgf_description' => ['field_prgf_description_value'],
      'paragraph__field_prgf_description' => ['field_prgf_description_value'],
    ];

    foreach ($tables as $table => $columns) {
      foreach ($columns as $field) {
        $result = $db->select($table, 't')
          ->fields('t')
          ->condition('t.' . $field, '%' . $db->escapeLike('drupal-entity-inline') . '%', 'LIKE')
          ->execute();

        while ($data = $result->fetchObject()) {
          $replacement = [];
          $dom = Html::load($data->$field);
          $xpath = new \DOMXPath($dom);

          /** @var \DOMElement $node */
          foreach ($xpath->query('//*[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]') as $node) {
            // Skip if it's not menu_link.
            if ($node->getAttribute('data-embed-button') != 'menu_link') {
              continue;
            }

            $uuid = $node->getAttribute('data-entity-uuid');
            $label = $node->getAttribute('data-entity-label');
            $replacement[] = '<drupal-entity
              data-button="0"
              data-embed-button="menu_link"
              data-entity-embed-display="entity_reference:entity_reference_label_url"
              data-entity-embed-display-settings="{&quot;route_link&quot;:1,&quot;route_title&quot;:&quot;' . $label . '&quot;}"
              data-entity-type="menu_link_content"
              data-entity-uuid="' . $uuid . '"></drupal-entity>';
          }

          // Find all embeds that need to be replaced.
          preg_match_all("/<drupal-entity-inline.*<\/drupal-entity-inline>/miU", $data->$field, $test);
          if (empty($test[0])) {
            continue;
          }

          // Skip if it's not menu_link.
          foreach ($test[0] as $i => $item) {
            if (strpos($item, 'menu_link') === FALSE) {
              unset($test[0][$i]);
            }
          }

          if (count($replacement) != count($test[0])) {
            throw new \Exception('Possibly invalid regex.');
          }

          // Create map for str_replace.
          foreach ($test[0] as $i => $embed) {
            $replace['from'][] = $embed;
            $replace['to'][] = $replacement[$i];
          }

          if ($replace) {
            $data->$field = str_replace($replace['from'], $replace['to'], $data->$field);
          }

          $db->update($table)
            ->fields([
              $field => $data->$field,
            ])
            ->execute();

          $this->loggerChannel->info(sprintf('Fixed entity embed in field %s in table %s', $field, $table));
        }

      }
    }

  }

}
