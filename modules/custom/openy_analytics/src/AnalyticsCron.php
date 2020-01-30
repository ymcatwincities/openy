<?php

namespace Drupal\openy_analytics;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\openy_socrates\OpenyCronServiceInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Extension\ExtensionList;
use GuzzleHttp\Client;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Class AnalyticsCron
 *
 * @package Drupal\openy_analytics
 */
class AnalyticsCron implements OpenyCronServiceInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $extensionListModule;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;


  protected $endpoint = 'http://carnation.demo.ixm.ca/node?_format=hal_json';
  protected $entityType = 'http://carnation.demo.ixm.ca/rest/type/node/analytics';

  /**
   * AnalyticsCron constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Extension\ExtensionList $extension_list_module
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \GuzzleHttp\Client $http_client
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, ConfigFactoryInterface $config_factory, ExtensionList $extension_list_module, EntityFieldManagerInterface $entity_field_manager, Client $http_client, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->extensionListModule = $extension_list_module;
    $this->entityFieldManager = $entity_field_manager;
    $this->httpClient = $http_client;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Returns last changed node information
   * @return mixed
   */
  function getLastChangedNode() {
    $statement = $this->database->select('node_field_data')
      ->fields('node_field_data', ['type', 'langcode', 'status', 'changed'])
      ->orderBy('changed', 'DESC')
      ->range(0, 1);
    return $statement->execute()->fetchAssoc();
  }

  /**
   * Returns server, php and db versions
   * @return array
   */
  function getServerInfo() {
    $db_version = $this->database->query('select version();')->fetchField();
    $db_detailed_version = $this->database->query("SHOW VARIABLES LIKE '%version%';")
      ->fetchAllKeyed();
    $server_software = $_SERVER['SERVER_SOFTWARE'];
    $php_version = $_SERVER['PHP_VERSION'];
    $conn_options = $this->database->getConnectionOptions();
    return [
      'server_software' => $server_software,
      'php_version' => $php_version,
      'db_version' => $db_version,
      'db_detailed_version' => $db_detailed_version,
      'db_driver' => $conn_options['driver'],
    ];
  }

  /**
   * Returns current theme and base theme
   * @return array
   */
  function getThemeInfo() {
    $default_theme = $this->configFactory->get('system.theme')->get('default');
    $base_theme = \Drupal::service('theme_handler')
      ->listInfo()[$default_theme]->base_theme;

    return [
      'default_theme' => $default_theme,
      'base_theme' => $base_theme,
    ];
  }

  /**
   * Returns array of enabled modules with their versions
   * @return array
   */
  function getEnabledModules() {
    $all_modules = $this->extensionListModule->getList();

    $modules = [
      'openy' => [],
      'custom' => [],
      'contrib' => [],
      'profile' => '',
    ];

    function string_contains($needle, $haystack) {
      return strpos($haystack, $needle) !== FALSE;
    }

    foreach ($all_modules as $module) {
      if ($module->status != 1) {
        continue;
      }

      $module_name = $module->info['name'];
      $module_ver = $module->info['version'];

      if ($module->getType() == 'profile') {
        $modules['profile'] = $module_ver;
        continue;
      }

      if (string_contains('profiles/contrib/openy', $module->getPathname())) {
        $module_type = 'openy';
      }
      elseif (string_contains('modules/contrib', $module->getPathname())) {
        $module_type = 'contrib';
      }
      else {
        $module_type = 'custom';
      }

      $modules[$module_type][$module_name] = $module_ver;
    }

    return $modules;
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  function getFrontpageParagraphs() {
    $front_page = $this->configFactory->get('system.site')->get('page.front');
    $front_page = explode('/', $front_page);
    $nid = end($front_page);

    $node = $this->entityTypeManager->getStorage('node')
      ->load($nid);

    $field_ids = [];
    // Get all the entity reference revisions fields.
    $map = $this->entityFieldManager->getFieldMapByFieldType('entity_reference_revisions');

    // Get all fields of the node with paragraphs.
    foreach ($map['node'] as $name => $data) {
      $target_type = FieldStorageConfig::loadByName('node', $name)
        ->getSetting('target_type');

      if ($target_type == 'paragraph' && $node->hasField($name)) {
        $field_ids[] = $name;
      }
    }

    $found_paragraphs = [];
    foreach ($field_ids as $field_id) {
      if (!$node->hasField($field_id)) {
        continue;
      }
      $field = $node->get($field_id)->getValue();
      foreach ($field as $field_paragraph) {
        /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
        $paragraph = Paragraph::load($field_paragraph['target_id']);
        $found_paragraphs[] = $paragraph->bundle();
      }
    }

    return $found_paragraphs;
  }

  /**
   * Returns statistics of paragraphs used on the site
   * @return array
   */
  function getParagraphsUsage() {
    $statement = $this->database->select('paragraphs_item')
      ->fields('paragraphs_item', ['type'])
      ->groupBy('type')
      ->orderBy('count', 'DESC');
    $statement->addExpression('COUNT(type)', 'count');
    $paragraphs_counted = $statement->execute()->fetchAllKeyed();

    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo('paragraph'));

    $bundles_counted = [];
    foreach ($bundles as $bundle) {
      if (isset($paragraphs_counted[$bundle])) {
        $bundles_counted[$bundle] = (int) $paragraphs_counted[$bundle];
      }
      else {
        $bundles_counted[$bundle] = 0;
      }
    }
    arsort($bundles_counted);

    return $bundles_counted;
  }

  /**
   * Returns statistics of content type bundles used on the site
   * @return mixed
   */
  function getContentTypeBundleUsage() {
    $statement = $this->database->select('node_field_data')
      ->fields('node_field_data', ['type'])
      ->condition('status', 1)
      ->groupBy('type')
      ->orderBy('count', 'DESC');
    $statement->addExpression('COUNT(type)', 'count');
    $bundles_counted = $statement->execute()->fetchAllKeyed();

    return $bundles_counted;
  }

  /**
   * Checks is site owner agreed to collect analytics
   * @return bool
   */
  function isEnabled() {
    $analytics_enabled = $this->configFactory->get('openy.terms_and_conditions.schema')
      ->get('analytics');

    if ($analytics_enabled) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @inheritDoc
   */
  public function runCronServices() {
    if(!$this->isEnabled()) {
      return;
    }

    try {
      $data = [
        'title' => $_SERVER['HTTP_HOST'],
        'last_changed_node' => $this->getLastChangedNode(),
        'server_info' => $this->getServerInfo(),
        'theme_info' => $this->getThemeInfo(),
        'enabled_modules' => $this->getEnabledModules(),
        'frontpage_paragraphs' => $this->getFrontpageParagraphs(),
        'paragraphs_usage' => $this->getParagraphsUsage(),
        'content_type_bundle_usage' => $this->getContentTypeBundleUsage(),
      ];

      $serialized_entity = json_encode([
        'title' => [['value' => $data['title']]],
        'type' => [['target_id' => 'analytics']],
        'field_db' => [['value' => $data['server_info']['db_version']]],
        'field_db_detailed' => [['value' => json_encode($data['server_info']['db_detailed_version'], TRUE)]],
        'field_php' => [['value' => $data['server_info']['php_version']]],
        'field_server' => [['value' => $data['server_info']['server_software']]],

        'field_contrib_modules_enabled' => [['value' => json_encode($data['enabled_modules']['contrib'], TRUE)]],
        'field_custom_modules_enabled' => [['value' => json_encode($data['enabled_modules']['custom'], TRUE)]],
        'field_openy_modules_enabled' => [['value' => json_encode($data['enabled_modules']['openy'], TRUE)]],

        'field_last_node_edit_timestamp' => [['value' => date('Y-m-d\TH:i:sP', $data['last_changed_node']['changed'])]],
        'field_nodes_usage' => [['value' => json_encode($data['content_type_bundle_usage'], TRUE)]],
        'field_paragraph_usage' => [['value' => json_encode($data['paragraphs_usage'], TRUE)]],

        'field_profile' => [['value' => $data['modules']['profile']]],
        'field_theme' => [['value' => json_encode($data['theme_info'], TRUE)]],
        '_links' => [
          'type' => [
            'href' => $this->entityType,
          ],
        ],
      ]);

      $response = $this->httpClient->post($this->endpoint, [
        'body' => $serialized_entity,
        'headers' => [
          'Content-Type' => 'application/hal+json',
        ],
      ]);
    } catch (\Exception $e) {
      watchdog_exception('openy_analytics', $e);
      return NULL;
    }
  }

}
