<?php

namespace Drupal\openy_analytics;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Extension\ExtensionList;
use GuzzleHttp\Client;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Utility\Error;

/**
 * Class AnalyticsCron
 *
 * @package Drupal\openy_analytics
 */
class AnalyticsCron {

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

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  protected $endpoint = 'http://openy.org:1880/analytics';

  /**
   * AnalyticsCron constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Extension\ExtensionList $extensionListModule
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \GuzzleHttp\Client $httpClient
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              Connection $database,
                              ConfigFactoryInterface $configFactory,
                              ExtensionList $extensionListModule,
                              EntityFieldManagerInterface $entityFieldManager,
                              Client $httpClient,
                              EntityTypeBundleInfoInterface $entityTypeBundleInfo,
                              LoggerChannelFactoryInterface $loggerFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->configFactory = $configFactory;
    $this->extensionListModule = $extensionListModule;
    $this->entityFieldManager = $entityFieldManager;
    $this->httpClient = $httpClient;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Returns last changed node information
   *
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
   *
   * @return array
   */
  function getServerInfo() {
    $db_version = $this->database->query('select version();')->fetchField();
    $db_detailed_version = $this->database->query("SHOW VARIABLES LIKE '%version%';")
      ->fetchAllKeyed();
    $server_software = $_SERVER['SERVER_SOFTWARE'];
    $php_version = phpversion();
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
   *
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
   *
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
   * Returns array of paragraph bundles used on homepage
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

    $field_ids = ['field_bottom_content', 'field_content', 'field_header_content', 'field_sidebar_content'];

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
   * Returns array of fields allowed paragraphs settings
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  function getLandingPageParagraphsAllowedSettings() {
    $front_page = $this->configFactory->get('system.site')->get('page.front');
    $front_page = explode('/', $front_page);
    $nid = end($front_page);

    $node = $this->entityTypeManager->getStorage('node')
      ->load($nid);

    $field_ids = ['field_bottom_content', 'field_content', 'field_header_content', 'field_sidebar_content'];

    $node_fields_settings = [];
    foreach ($field_ids as $field_id) {
      if (!$node->hasField($field_id)) {
        continue;
      }
      /**
       * @var \Drupal\Core\Field\FieldItemList $field
       */
      $field = $node->get($field_id);
      $fieldConfig = $field->getFieldDefinition();
      $settings = $fieldConfig->getSettings();
      $negate = $settings['handler_settings']['negate'];
      $target_bundles = $settings['handler_settings']['target_bundles'];
      $node_fields_settings[$field_id] = [
        $negate,
        $target_bundles
      ];
    }

    return $node_fields_settings;
  }

  /**
   * Returns statistics of paragraphs used on the site
   *
   * @return array
   */
  function getParagraphsUsage() {
    $statement = $this->database->select('paragraphs_item')
      ->fields('paragraphs_item', ['type'])
      ->groupBy('type')
      ->orderBy('count', 'DESC');
    $statement->addExpression('COUNT(type)', 'count');
    $paragraphs_counted = $statement->execute()->fetchAllKeyed();

    // $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo('paragraph'));
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
   *
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
   *
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
    if (!$this->isEnabled()) {
      return;
    }

    try {
      $data = [
        'http_host' => $_SERVER['HTTP_HOST'],
        'last_changed_node' => $this->getLastChangedNode(),
        'server_info' => $this->getServerInfo(),
        'theme_info' => $this->getThemeInfo(),
        'enabled_modules' => $this->getEnabledModules(),
        'frontpage_paragraphs' => $this->getFrontpageParagraphs(),
        'landing_page_settings' => $this->getLandingPageParagraphsAllowedSettings(),
        'paragraphs_usage' => $this->getParagraphsUsage(),
        'content_type_bundle_usage' => $this->getContentTypeBundleUsage(),
      ];

      $json = json_encode($data, true);

      $response = $this->httpClient->post($this->endpoint, [
        'body' => $json,
        'multipart',
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);
    } catch (\Exception $e) {
      $message = '%type: @message in %function (line %line of %file).';
      $variables = Error::decodeException($e);
      $this->loggerFactory->get('openy_analytics')
        ->log(RfcLogLevel::ERROR, $message, $variables);
      return NULL;
    }
  }

}
