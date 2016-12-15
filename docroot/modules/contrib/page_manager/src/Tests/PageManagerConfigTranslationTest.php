<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageManagerConfigTranslationTest.
 */

namespace Drupal\page_manager\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that pages and variants can be translated.
 *
 * @group page_manager
 */
class PageManagerConfigTranslationTest extends WebTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Remove page_manager_ui from the list once config_translation does not
   *   require a UI in https://www.drupal.org/node/2670718.
   */
  public static $modules = ['block', 'page_manager', 'page_manager_ui', 'node', 'config_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->drupalLogin($this->drupalCreateUser(['administer site configuration', 'translate configuration']));

    PageVariant::create([
      'variant' => 'http_status_code',
      'label' => 'HTTP status code',
      'id' => 'http_status_code',
      'page' => 'node_view',
    ])->save();
  }

  /**
   * Tests config translation.
   */
  public function testTranslation() {
    $this->drupalGet('admin/config/regional/config-translation');
    $this->assertLinkByHref('admin/config/regional/config-translation/page');
    $this->assertLinkByHref('admin/config/regional/config-translation/page_variant');

    $this->drupalGet('admin/config/regional/config-translation/page');
    $this->assertText('Node view');
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $this->assertField('translation[config_names][page_manager.page.node_view][label]');

    $this->drupalGet('admin/config/regional/config-translation/page_variant');
    $this->assertText('HTTP status code');
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $this->assertField('translation[config_names][page_manager.page_variant.http_status_code][label]');
  }

}
