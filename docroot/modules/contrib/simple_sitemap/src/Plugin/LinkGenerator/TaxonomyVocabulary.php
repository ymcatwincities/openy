<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\LinkGenerator\TaxonomyVocabulary.
 *
 * Plugin for taxonomy term entity link generation.
 */

namespace Drupal\simple_sitemap\Plugin\LinkGenerator;

use Drupal\simple_sitemap\Annotation\LinkGenerator;
use Drupal\simple_sitemap\LinkGeneratorBase;

/**
 * TaxonomyVocabulary class.
 *
 * @LinkGenerator(
 *   id = "taxonomy_vocabulary"
 * )
 */
class TaxonomyVocabulary extends LinkGeneratorBase {

  /**
   * {@inheritdoc}
   */
  function get_entities_of_bundle($bundle) {

    $query = \Drupal::database()->select('taxonomy_term_field_data', 't')
      ->fields('t', array('tid', 'changed'))
      ->condition('vid', $bundle);

    $info = array(
      'field_info' => array(
        'entity_id' => 'tid',
        'lastmod' => 'changed',
      ),
      'path_info' => array(
        'route_name' => 'entity.taxonomy_term.canonical',
        'entity_type' => 'taxonomy_term',
      )
    );
    return array('query' => $query, 'info' => $info);
  }
}
