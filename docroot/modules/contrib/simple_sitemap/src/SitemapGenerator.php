<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\SitemapGenerator.
 *
 * Generates a sitemap for entities and custom links.
 */

namespace Drupal\simple_sitemap;

use \XMLWriter;

/**
 * SitemapGenerator class.
 */
class SitemapGenerator {

  const PRIORITY_DEFAULT = 0.5;
  const PRIORITY_HIGHEST = 10;
  const PRIORITY_DIVIDER = 10;
  const XML_VERSION = '1.0';
  const ENCODING = 'UTF-8';
  const XMLNS = 'http://www.sitemaps.org/schemas/sitemap/0.9';
  const XMLNS_XHTML = 'http://www.w3.org/1999/xhtml';

  private $entity_types;
  private $custom;
  private $links;
  private $generating_from;

  function __construct($from = 'form') {
    $this->links = array();
    $this->generating_from = $from;
  }

  /**
   * Gets the values needed to display the priority dropdown setting.
   *
   * @return array $options
   */
  public static function get_priority_select_values() {
    $options = array();
    foreach(range(0, self::PRIORITY_HIGHEST) as $value) {
      $value = $value / self::PRIORITY_DIVIDER;
      $options[(string)$value] = (string)$value;
    }
    return $options;
  }

  public function set_entity_types($entity_types) {
    $this->entity_types = is_array($entity_types) ? $entity_types : array();
  }

  public function set_custom_links($custom) {
    $this->custom = is_array($custom) ? $custom : array();
  }

  /**
   * Adds all operations to the batch and starts it.
   */
  public function start_batch() {
    $batch = new Batch($this->generating_from);
    $batch->add_operations('custom_paths', $this->batch_add_custom_paths());
    $batch->add_operations('entity_types', $this->batch_add_entity_type_paths());
    $batch->start();
  }

  /**
   * Returns the custom path generating operation.
   *
   * @return array $operation.
   */
  private function batch_add_custom_paths() {
    $link_generator = new CustomLinkGenerator();
    return $link_generator->get_custom_paths($this->custom);
  }

  /**
   * Collects the entity path generating information from all simeple_sitemap
   * plugins to be added to the batch.
   *
   * @return array $operations.
   */
  private function batch_add_entity_type_paths() {

    $manager = \Drupal::service('plugin.manager.simple_sitemap');
    $plugins = $manager->getDefinitions();
    $operations = array();

    // Let all simple_sitemap plugins add their links to the sitemap.
    foreach ($plugins as $link_generator_plugin) {
      if (isset($this->entity_types[$link_generator_plugin['id']])) {
        $instance = $manager->createInstance($link_generator_plugin['id']);
        foreach($this->entity_types[$link_generator_plugin['id']] as $bundle => $bundle_settings) {
          if ($bundle_settings['index']) {
            $operation = $instance->get_entities_of_bundle($bundle);
            $operation['info']['bundle_settings'] = $bundle_settings;
            $operations[] = $operation;
          }
        }
      }
    }
    return $operations;
  }

  /**
   * Wrapper method which takes links along with their options and then
   * generates and saves the sitemap.
   *
   * @param array $links
   *  All links with their multilingual versions and settings.
   */
  public static function generate_sitemap($links) {
    Simplesitemap::save_sitemap(array(
        'id' => db_query('SELECT MAX(id) FROM {simple_sitemap}')->fetchField() + 1,
        'sitemap_string' => self::generate_sitemap_chunk($links),
        'sitemap_created' => REQUEST_TIME)
    );
  }

  /**
   * Generates and returns the sitemap index for all sitemap chunks.
   *
   * @param array $sitemap
   *  All sitemap chunks keyed by the chunk ID.
   *
   * @return string sitemap index
   */
  public function generate_sitemap_index($sitemap) {
    $writer = new XMLWriter();
    $writer->openMemory();
    $writer->setIndent(TRUE);
    $writer->startDocument(self::XML_VERSION, self::ENCODING);
    $writer->startElement('sitemapindex');
    $writer->writeAttribute('xmlns', self::XMLNS);

    foreach ($sitemap as $chunk_id => $chunk_data) {
      $writer->startElement('sitemap');
      $writer->writeElement('loc', $GLOBALS['base_url'] . '/sitemaps/'
        . $chunk_id . '/' . 'sitemap.xml');
      $writer->writeElement('lastmod', date_iso8601($chunk_data->sitemap_created));
      $writer->endElement();
    }
    $writer->endElement();
    $writer->endDocument();
    return $writer->outputMemory();
  }

  /**
   * Generates and returns a sitemap chunk.
   *
   * @param array $sitemap_links
   *  All links with their multilingual versions and settings.
   *
   * @return string sitemap chunk
   */
  private static function generate_sitemap_chunk($sitemap_links) {
    $default_language_id = Simplesitemap::get_default_lang_id();

    $writer = new XMLWriter();
    $writer->openMemory();
    $writer->setIndent(TRUE);
    $writer->startDocument(self::XML_VERSION, self::ENCODING);
    $writer->startElement('urlset');
    $writer->writeAttribute('xmlns', self::XMLNS);
    $writer->writeAttribute('xmlns:xhtml', self::XMLNS_XHTML);

    foreach ($sitemap_links as $link) {
      $writer->startElement('url');

      // Adding url to standard language.
      $writer->writeElement('loc', $link['urls'][$default_language_id]);

      // Adding alternate urls (other languages) if any.
      if (count($link['urls']) > 1) {
        foreach($link['urls'] as $language_id => $localised_url) {
          $writer->startElement('xhtml:link');
          $writer->writeAttribute('rel', 'alternate');
          $writer->writeAttribute('hreflang', $language_id);
          $writer->writeAttribute('href', $localised_url);
          $writer->endElement();
        }
      }

      // Add priority if any.
      if (isset($link['priority'])) {
        $writer->writeElement('priority', $link['priority']);
      }

      // Add lastmod if any.
      if (isset($link['lastmod'])) {
        $writer->writeElement('lastmod', $link['lastmod']);
      }
      $writer->endElement();
    }
    $writer->endElement();
    $writer->endDocument();
    return $writer->outputMemory();
  }
}
