<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapTaxonomyFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Component\Utility\Unicode;

/**
 * Tests the generation of taxonomy links.
 */
class XmlSitemapTaxonomyFunctionalTest extends XmlSitemapTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'xmlsitemap');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap taxonomy',
      'description' => 'Functional tests for the XML sitemap module taxonomy entity.',
      'group' => 'XML sitemap',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    xmlsitemap_link_bundle_enable('taxonomy_vocabulary', 'taxonomy_vocabulary');

    // allow anonymous user to view user profiles
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('administer taxonomy');
    $user_role->save();

    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'administer xmlsitemap'));
    $this->normal_user = $this->drupalCreateUser(array('access content'));
  }

  /**
   * Test xmlsitemap settings for taxonomies.
   */
  public function testTaxonomySettings() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');
    $edit = array(
      'name' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'xmlsitemap[status]' => '1',
      'xmlsitemap[priority]' => '1.0',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText("Created new vocabulary {$edit['name']}.");

    $vocabulary = \Drupal\taxonomy\Entity\Vocabulary::load($edit['vid']);

    xmlsitemap_link_bundle_enable('taxonomy_term', $vocabulary->id());

    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertResponse(200);
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');
    $this->assertField('xmlsitemap[changefreq]');

    $edit = array(
      'name[0][value]' => $this->randomMachineName(),
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

}
