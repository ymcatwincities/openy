<?php

namespace Drupal\Tests\simple_sitemap\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Base for SimplesitemapTest.
 */
abstract class SimplesitemapTestBase extends UnitTestCase {

  protected $config;
  protected $container;
  protected $backupGlobals = FALSE;

  protected $simplesitemapMock;

  /**
   * Used to set a Drupal global. Does not need to be a real URL ATM.
   */
  const BASE_URL = 'https://some-url';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a dummy container.
    $this->container = new ContainerBuilder();

    // The string translation service will be used for most test cases.
    $this->container->set('string_translation', $this->getStringTranslationStub());

    // Initial config set up. These are the settings the module sets upon
    // installation (see sitemap_settings.settings.yml).
    $this->config = [
      'max_links' => 2000,
      'cron_generate' => TRUE,
      'remove_duplicates' => TRUE,
      'batch_process_limit' => 1500,
      'enabled_entity_types' => [
        'node',
        'taxonomy_term',
        'menu_link_content',
      ],
      'base_url' => '',
    ];

    // Mock the digtap service with the above settings.
    $this->mockSimplesitemapService();

    // Set this Drupal global as it may be used in tested methods.
    $GLOBALS['base_url'] = self::BASE_URL;
  }

  /**
   * Mock Drupal Simplesitemap service.
   */
  protected function mockSimplesitemapService() {
    // $configFactory = $this->getConfigFactoryStub(['simple_sitemap.settings' => $this->config]);
    //    $this->simplesitemapMock = $this->getMockBuilder('\Drupal\simple_sitemap\Simplesitemap')
    //      ->setConstructorArgs([
    //        $configFactory
    //        // todo: Add constructor args
    //      ])
    //      ->setMethods(NULL)
    //      ->getMock();
    //    $this->container->set('simple_sitemap.settings', $this->simplesitemapMock);
    //    \Drupal::setContainer($this->container);.
  }

}
