<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\simple_sitemap\Form\FormHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Class Simplesitemap.
 *
 * @package Drupal\simple_sitemap
 */
class Simplesitemap {

  private $sitemapGenerator;
  private $configFactory;
  private $db;
  private $entityQuery;
  private $entityTypeManager;
  private $pathValidator;
  private static $allowed_link_settings = [
    'entity' => ['index', 'priority'],
    'custom' => ['priority'],
  ];

  /**
   * Simplesitemap constructor.
   *
   * @param $sitemapGenerator
   * @param $configFactory
   * @param $database
   * @param $entityQuery
   * @param $entityTypeManager
   * @param $pathValidator
   * @param $dateFormatter
   */
  public function __construct(
    SitemapGenerator $sitemapGenerator,
    ConfigFactory $configFactory,
    Connection $database,
    QueryFactory $entityQuery,
    EntityTypeManagerInterface $entityTypeManager,
    PathValidator $pathValidator,
    DateFormatter $dateFormatter
  ) {
    $this->sitemapGenerator = $sitemapGenerator;
    $this->configFactory = $configFactory;
    $this->db = $database;
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entityTypeManager;
    $this->pathValidator = $pathValidator;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Fetches all sitemap chunks indexed by chunk ID.
   *
   * @return string
   */
  private function fetchSitemapChunks() {
    return $this->db
      ->query("SELECT * FROM {simple_sitemap}")
      ->fetchAllAssoc('id');
  }

  /**
   * Enables sitemap support for an entity type. Enabled entity types show
   * sitemap settings on their bundle setting forms. If an enabled entity type
   * features bundles (e.g. 'node'), it needs to be set up with
   * setBundleSettings() as well.
   *
   * @param string $entity_type_id
   *   Entity type id like 'node'.
   *
   * @return $this
   */
  public function enableEntityType($entity_type_id) {
    $enabled_entity_types = $this->getSetting('enabled_entity_types');
    if (!in_array($entity_type_id, $enabled_entity_types)) {
      $enabled_entity_types[] = $entity_type_id;
      $this->saveSetting('enabled_entity_types', $enabled_entity_types);
    }
    return $this;
  }

  /**
   * Disables sitemap support for an entity type. Disabling support for an
   * entity type deletes its sitemap settings permanently and removes sitemap
   * settings from entity forms.
   *
   * @param string $entity_type_id
   *  Entity type id like 'node'.
   *
   * @return $this
   */
  public function disableEntityType($entity_type_id) {

    // Updating settings.
    $enabled_entity_types = $this->getSetting('enabled_entity_types');
    if (($key = array_search($entity_type_id, $enabled_entity_types)) !== FALSE) {
      unset ($enabled_entity_types[$key]);
      $this->saveSetting('enabled_entity_types', array_values($enabled_entity_types));
    }

    // Deleting inclusion settings.
    $config_names = $this->configFactory->listAll("simple_sitemap.bundle_settings.$entity_type_id");
    foreach($config_names as $config_name) {
      $this->configFactory->getEditable($config_name)->delete();
    }

    // Deleting entity overrides.
    $this->removeEntityInstanceSettings($entity_type_id);
    return $this;
  }

  /**
   * Sets sitemap settings for a non-bundle entity type (e.g. user) or a bundle
   * of an entity type (e.g. page).
   *
   * @param string $entity_type_id
   *   Entity type id like 'node' the bundle belongs to.
   * @param string $bundle_name
   *   Name of the bundle. NULL if entity type has no bundles.
   * @param array $settings
   *   An array of sitemap settings for this bundle/entity type.
   *   Example: ['index' => TRUE, 'priority' => 0.5].
   *
   * @return $this
   */
  public function setBundleSettings($entity_type_id, $bundle_name = NULL, $settings) {

    $bundle_name = empty($bundle_name) ? $entity_type_id : $bundle_name;

    foreach($settings as $setting_key => $setting) {
      if ($setting_key == 'index') {
        $setting = intval($setting);
      }
      $this->configFactory
        ->getEditable("simple_sitemap.bundle_settings.$entity_type_id.$bundle_name")
        ->set($setting_key, $setting)
        ->save();
    }
    //todo: Use addLinkSettings()?

    // Delete entity overrides which are identical to new bundle setting.
    $sitemap_entity_types = $this->getSitemapEntityTypes();
    if (isset($sitemap_entity_types[$entity_type_id])) {
      $entity_type = $sitemap_entity_types[$entity_type_id];
      $keys = $entity_type->getKeys();
      $keys['bundle'] = $entity_type_id == 'menu_link_content' ? 'menu_name' : $keys['bundle'];

      $query = $this->entityQuery->get($entity_type_id);
      if (!$this->entityTypeIsAtomic($entity_type_id)) {
        $query->condition($keys['bundle'], $bundle_name);
      }
      $entity_ids = $query->execute();

      $bundle_settings = $this->configFactory
        ->get("simple_sitemap.bundle_settings.$entity_type_id.$bundle_name");

      $query = $this->db->select('simple_sitemap_entity_overrides', 'o')
        ->fields('o', ['id', 'inclusion_settings'])
        ->condition('o.entity_type', $entity_type_id);
      if (!empty($entity_ids)) {
        $query->condition('o.entity_id', $entity_ids, 'IN');
      }
      $results = $query->execute()
        ->fetchAll();

      foreach($results as $result) {
        $delete = TRUE;
        $instance_settings = unserialize($result->inclusion_settings);
        foreach ($instance_settings as $setting_key => $instance_setting) {
          if ($instance_setting != $bundle_settings->get($setting_key)) {
            $delete = FALSE;
            break;
          }
        }
        if ($delete) {
          $this->db->delete('simple_sitemap_entity_overrides')
            ->condition('id', $result->id)
            ->execute();
        }
      }
    }
    else {
      //todo: log error
    }
    return $this;
  }

  /**
   * Gets sitemap settings for an entity bundle, a non-bundle entity type or for
   * all entity types and their bundles.
   *
   * @param string|null $entity_type_id
   *  If set to null, sitemap settings for all entity types and their bundles
   *  are fetched.
   * @param string|null $bundle_name
   *
   * @return array|false
   *  Array of sitemap settings or array of entity types with their settings.
   *  False if entity type does not exist.
   */
  public function getBundleSettings($entity_type_id = NULL, $bundle_name = NULL) {
    if (!is_null($entity_type_id)) {
      $bundle_name = empty($bundle_name) ? $entity_type_id : $bundle_name;
      $settings = $this->configFactory
        ->get("simple_sitemap.bundle_settings.$entity_type_id.$bundle_name")
        ->get();
      $bundle_settings = !empty($settings) ? $settings : FALSE;
    }
    else {
      $config_names = $this->configFactory->listAll("simple_sitemap.bundle_settings");
      $bundle_settings = [];
      foreach($config_names as $config_name) {
        $config_name_parts = explode('.', $config_name);
        $bundle_settings[$config_name_parts[2]][$config_name_parts[3]]
          = $this->configFactory->get($config_name)->get();
      }
    }
    return $bundle_settings;
  }

  /**
   * Overrides entity bundle/entity type sitemap settings for a single entity.
   *
   * @param string $entity_type_id
   * @param int $id
   * @param array $settings
   *
   * @return $this
   */
  public function setEntityInstanceSettings($entity_type_id, $id, $settings) {

    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($id);
    $bundle_name = $this->getEntityInstanceBundleName($entity);
    $bundle_settings = $this->configFactory
      ->get("simple_sitemap.bundle_settings.$entity_type_id.$bundle_name")
      ->get();

    if (!empty($bundle_settings)) {

      // Check if overrides are different from bundle setting before saving.
      $override = FALSE;
      foreach ($settings as $key => $setting) {
        if ($setting != $bundle_settings[$key]) {
          $override = TRUE;
          break;
        }
      }
      // Save overrides for this entity if something is different.
      if ($override) {
        $this->db->merge('simple_sitemap_entity_overrides')
          ->key([
            'entity_type' => $entity_type_id,
            'entity_id' => $id])
          ->fields([
            'entity_type' => $entity_type_id,
            'entity_id' => $id,
            'inclusion_settings' => serialize($settings),
          ])
          ->execute();
      }
      // Else unset override.
      else {
        $this->removeEntityInstanceSettings($entity_type_id, $id);
      }
    }
    else {
      //todo: log error
    }
    return $this;
  }

  /**
   * Gets sitemap settings for an entity instance which overrides the sitemap
   * settings of its bundle.
   *
   * @param string $entity_type_id
   * @param int $id
   *
   * @return array
   */
  public function getEntityInstanceSettings($entity_type_id, $id) {
    $results = $this->db->select('simple_sitemap_entity_overrides', 'o')
      ->fields('o', ['inclusion_settings'])
      ->condition('o.entity_type', $entity_type_id)
      ->condition('o.entity_id', $id)
      ->execute()
      ->fetchField();

    if (!empty($results)) {
      return unserialize($results);
    }
    else {
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($id);
      $bundle_name = $this->getEntityInstanceBundleName($entity);
      return $this->getBundleSettings($entity_type_id, $bundle_name);
    }
  }

  /**
   * Removes sitemap settings for an entity that overrides the sitemap settings
   * of its bundle.
   *
   * @param string $entity_type_id
   * @param string|null $entity_ids
   *
   * @return $this
   */
  public function removeEntityInstanceSettings($entity_type_id, $entity_ids = NULL) {
    $query = $this->db->delete('simple_sitemap_entity_overrides')
      ->condition('entity_type', $entity_type_id);
    if (!is_null($entity_ids)) {
      $entity_ids = !is_array($entity_ids) ? [$entity_ids] : $entity_ids;
      $query->condition('entity_id', $entity_ids, 'IN');
    }
    $query->execute();
    return $this;
  }

  /**
   * Checks if an entity bundle (or a non-bundle entity type) is set to be
   * indexed in the sitemap settings.
   *
   * @param string $entity_type_id
   * @param string|null $bundle_name
   *
   * @return bool
   */
  public function bundleIsIndexed($entity_type_id, $bundle_name = NULL) {
    $settings = $this->getBundleSettings($entity_type_id, $bundle_name);
    return !empty($settings['index']);
  }

  /**
   * Checks if an entity type is enabled in the sitemap settings.
   *
   * @param string $entity_type_id
   *
   * @return bool
   */
  public function entityTypeIsEnabled($entity_type_id) {
    return in_array($entity_type_id, $this->getSetting('enabled_entity_types', []));
  }

  /**
   * Stores a custom path along with its sitemap settings to configuration.
   *
   * @param string $path
   * @param array $settings
   *
   * @return $this
   */
  public function addCustomLink($path, $settings) {
    if (!$this->pathValidator->isValid($path)) {
      // todo: log error.
      return $this;
    }
    if ($path[0] != '/') {
      // todo: log error.
      return $this;
    }

    $custom_links = $this->getCustomLinks();
    foreach ($custom_links as $key => $link) {
      if ($link['path'] == $path) {
        $link_key = $key;
        break;
      }
    }
    $link_key = isset($link_key) ? $link_key : count($custom_links);
    $custom_links[$link_key]['path'] = $path;
    $this->addLinkSettings('custom', $settings, $custom_links[$link_key]); //todo: dirty
    $this->configFactory->getEditable("simple_sitemap.custom")
      ->set('links', $custom_links)->save();
    return $this;
  }

  /**
   *
   */
  private function addLinkSettings($type, $settings, &$target) {
    foreach ($settings as $setting_key => $setting) {
      if (in_array($setting_key, self::$allowed_link_settings[$type])) {
        switch ($setting_key) {
          case 'priority':
            if (!FormHelper::isValidPriority($setting)) {
              // todo: log error.
              continue;
            }
            break;

          // todo: add index check.
        }
        $target[$setting_key] = $setting;
      }
    }
  }

  /**
   * Returns an array of custom paths and their sitemap settings.
   *
   * @return array
   */
  public function getCustomLinks() {
    $custom_links = $this->configFactory
      ->get('simple_sitemap.custom')
      ->get('links');
    return $custom_links;
  }

  /**
   * Returns settings for a custom path added to the sitemap settings.
   *
   * @param string $path
   *
   * @return array|false
   */
  public function getCustomLink($path) {
    foreach ($this->getCustomLinks() as $key => $link) {
      if ($link['path'] == $path) {
        return $link;
      }
    }
    return FALSE;
  }

  /**
   * Removes a custom path from the sitemap settings.
   *
   * @param string $path
   *
   * @return $this
   */
  public function removeCustomLink($path) {
    $custom_links = $this->getCustomLinks();
    foreach ($custom_links as $key => $link) {
      if ($link['path'] == $path) {
        unset($custom_links[$key]);
        $custom_links = array_values($custom_links);
        $this->configFactory->getEditable("simple_sitemap.custom")
          ->set('links', $custom_links)->save();
        break;
      }
    }
    return $this;
  }

  /**
   * Removes all custom paths from the sitemap settings.
   *
   * @return $this
   */
  public function removeCustomLinks() {
    $this->configFactory->getEditable("simple_sitemap.custom")
      ->set('links', [])->save();
    return $this;
  }

  /**
   * Gets an entity's bundle name.
   *
   * @param string $entity
   * @return string
   */
  public function getEntityInstanceBundleName($entity) {
    return $entity->getEntityTypeId() == 'menu_link_content'
    // Menu fix.
      ? $entity->getMenuName() : $entity->bundle();
  }

  /**
   * Gets the entity type id for a bundle.
   *
   * @param string $entity
   * @return string
   */
  public function getBundleEntityTypeId($entity) {
    return $entity->getEntityTypeId() == 'menu'
    // Menu fix.
      ? 'menu_link_content' : $entity->getEntityType()->getBundleOf();
  }

  /**
   * Returns the whole sitemap, a requested sitemap chunk,
   * or the sitemap index file.
   *
   * @param int $chunk_id
   *
   * @return string|false
   *   If no sitemap id provided, either a sitemap index is returned, or the
   *   whole sitemap, if the amount of links does not exceed the max links
   *   setting. If a sitemap id is provided, a sitemap chunk is returned. False
   *   if sitemap is not retrievable from the database.
   */
  public function getSitemap($chunk_id = NULL) {
    $chunks = $this->fetchSitemapChunks();
    if (is_null($chunk_id) || !isset($chunks[$chunk_id])) {

      // Return sitemap index, if there are multiple sitemap chunks.
      if (count($chunks) > 1) {
        return $this->getSitemapIndex($chunks);
      }
      // Return sitemap if there is only one chunk.
      else {
        if (isset($chunks[1])) {
          return $chunks[1]->sitemap_string;
        }
        return FALSE;
      }
    }
    // Return specific sitemap chunk.
    else {
      return $chunks[$chunk_id]->sitemap_string;
    }
  }

  /**
   * Generates the sitemap for all languages and saves it to the db.
   *
   * @param string $from
   *   Can be 'form', 'cron', 'drush' or 'nobatch'.
   *   This decides how the batch process is to be run.
   */
  public function generateSitemap($from = 'form') {
    $this->sitemapGenerator
      ->setGenerator($this)
      ->setGenerateFrom($from)
      ->startGeneration();
  }

  /**
   * Generates and returns the sitemap index as string.
   *
   * @param array $chunks
   *   Sitemap chunks which to generate the index from.
   *
   * @return string
   *   The sitemap index.
   */
  private function getSitemapIndex($chunks) {
    return $this->sitemapGenerator
      ->setGenerator($this)
      ->generateSitemapIndex($chunks);
  }

  /**
   * Returns a specific sitemap setting or a default value if setting does not
   * exist.
   *
   * @param string $name
   *   Name of the setting, like 'max_links'.
   *
   * @param mixed $default
   *   Value to be returned if the setting does not exist in the configuration.
   *
   * @return mixed
   *   The current setting from configuration or a default value.
   */
  public function getSetting($name, $default = FALSE) {
    $setting = $this->configFactory
      ->get('simple_sitemap.settings')
      ->get($name);
    return !is_null($setting) ? $setting : $default;
  }

  /**
   * Stores a specific sitemap setting in configuration.
   *
   * @param string $name
   *   Setting name, like 'max_links'.
   * @param mixed $setting
   *   The setting to be saved.
   *
   * @return $this
   */
  public function saveSetting($name, $setting) {
    $this->configFactory->getEditable("simple_sitemap.settings")
      ->set($name, $setting)->save();
    return $this;
  }

  /**
   * Returns a 'time ago' string of last timestamp generation.
   *
   * @return string|false
   *   Formatted timestamp of last sitemap generation, otherwise FALSE.
   */
  public function getGeneratedAgo() {
    $chunks = $this->fetchSitemapChunks();
    if (isset($chunks[1]->sitemap_created)) {
      return $this->dateFormatter
        ->formatInterval(REQUEST_TIME - $chunks[1]->sitemap_created);
    }
    return FALSE;
  }

  /**
   * Returns objects of entity types that can be indexed.
   *
   * @return array
   *   Objects of entity types that can be indexed by the sitemap.
   */
  public function getSitemapEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface
        || !method_exists($entity_type, 'getBundleEntityType')
        || !$entity_type->hasLinkTemplate('canonical')) {
        unset($entity_types[$entity_type_id]);
      }
    }
    return $entity_types;
  }

  /**
   * Checks whether an entity type does not provide bundles.
   *
   * @param string $entity_type_id
   * @return bool
   */
  public function entityTypeIsAtomic($entity_type_id) {
    // Menu fix.
    if ($entity_type_id == 'menu_link_content') {
      return FALSE;
    }

    $sitemap_entity_types = $this->getSitemapEntityTypes();
    if (isset($sitemap_entity_types[$entity_type_id])) {
      $entity_type = $sitemap_entity_types[$entity_type_id];
      if (empty($entity_type->getBundleEntityType())) {
        return TRUE;
      }
    }
    // todo: throw exception.
    return FALSE;
  }

}
