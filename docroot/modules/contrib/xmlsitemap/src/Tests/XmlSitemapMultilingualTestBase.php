<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapMultilingualTestBase.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Language\Language;

/**
 * Common base test class for XML sitemap internationalization tests.
 */
abstract class XmlSitemapMultilingualTestBase extends XmlSitemapTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'xmlsitemap', 'node', 'locale', 'content_translation', 'system');

  /**
   * Set up an administrative user account and testing keys.
   */
  public function setUp() {
    // Call parent::setUp() allowing test cases to pass further modules.
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'administer site configuration', 'administer xmlsitemap', 'access content'));
    $this->drupalLogin($this->admin_user);

    if (!$this->languageManager->getLanguage('fr')) {
      // Add a new language.
      $language = new Language(array(
        'id' => 'fr',
        'name' => 'French',
      ));
      language_save($language);
    }

    if (!$this->languageManager->getLanguage('en')) {
      // Add a new language.
      $language = new Language(array(
        'id' => 'en',
        'name' => 'English',
      ));
      language_save($language);
    }
    // Create the two different language-context sitemaps.
    $previous_sitemaps = entity_load_multiple('xmlsitemap');
    foreach ($previous_sitemaps as $previous_sitemap) {
      $previous_sitemap->delete();
    }

    $sitemap = $this->entityManager->getStorage('xmlsitemap')->create(array());
    $sitemap->context = array('language' => 'en');
    xmlsitemap_sitemap_save($sitemap);
    $sitemap = $this->entityManager->getStorage('xmlsitemap')->create(array());
    $sitemap->context = array('language' => 'fr');
    xmlsitemap_sitemap_save($sitemap);
  }

}
