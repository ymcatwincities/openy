<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapRandomEntityFunctionalTest.
 */

namespace Drupal\xmlsitemap\Tests;

/**
 * Tests the generation of a random content entity links.
 */
class XmlSitemapEntityFunctionalTest extends XmlSitemapTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'xmlsitemap', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap random entity',
      'description' => 'Functional tests for the XML sitemap random entity links.',
      'group' => 'XML sitemap',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer entity_test content', 'administer xmlsitemap'));

    // allow anonymous user to view entity
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('view test entity');
    $user_role->save();
  }

  /**
   * Test the form at admin/config/search/xmlsitemap/entities/settings.
   */
  public function testEntitiesSettingsForms() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/search/xmlsitemap/entities/settings');
    $this->assertResponse(200);
    $this->assertField('entity_types[entity_test]');
    $this->assertField('settings[entity_test][entity_test][settings][bundle]');
    $edit = array(
      'entity_types[entity_test]' => 1,
      'settings[entity_test][entity_test][settings][bundle]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The configuration options have been saved.'));
    $entity = entity_create('entity_test', array(
      'bundle' => 'entity_test',
    ));
    $entity->save();
    $this->assertSitemapLinkValues('entity_test', $entity->id(), array('status' => 0, 'priority' => 0.5, 'changefreq' => 0, 'access' => 1));
  }

  /**
   * Test the form at admin/config/search/xmlsitemap/settings/{entity_type_id}/{bundle_id}.
   */
  public function testEntityLinkBundleSettingsForm() {
    xmlsitemap_link_bundle_enable('entity_test', 'entity_test');
    $this->drupalLogin($this->admin_user);
    // set priority and inclusion for entity_test - entity_test
    $this->drupalGet('admin/config/search/xmlsitemap/settings/entity_test/entity_test');
    $this->assertResponse(200);
    $this->assertField('xmlsitemap[status]');
    $this->assertField('xmlsitemap[priority]');
    $this->assertField('xmlsitemap[changefreq]');
    $edit = array(
      'xmlsitemap[status]' => 0,
      'xmlsitemap[priority]' => 0.3,
      'xmlsitemap[changefreq]' => XMLSITEMAP_FREQUENCY_WEEKLY,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $entity = entity_create('entity_test', array(
      'bundle' => 'entity_test',
    ));
    $entity->save();
    $this->assertSitemapLinkValues('entity_test', $entity->id(), array('status' => 0, 'priority' => 0.3, 'changefreq' => XMLSITEMAP_FREQUENCY_WEEKLY, 'access' => 1));

    $this->regenerateSitemap();
    $this->drupalGet('sitemap.xml');
    $this->assertResponse(200);
    $this->assertNoText($entity->url());

    $id = $entity->id();
    $entity->delete();
    $this->assertNoSitemapLink('entity_test');

    $edit = array(
      'xmlsitemap[status]' => 1,
      'xmlsitemap[priority]' => 0.6,
      'xmlsitemap[changefreq]' => XMLSITEMAP_FREQUENCY_YEARLY,
    );
    $this->drupalPostForm('admin/config/search/xmlsitemap/settings/entity_test/entity_test', $edit, t('Save configuration'));
    $entity = entity_create('entity_test', array(
      'bundle' => 'entity_test',
    ));
    $entity->save();
    $this->assertSitemapLinkValues('entity_test', $entity->id(), array('status' => 1, 'priority' => 0.6, 'changefreq' => XMLSITEMAP_FREQUENCY_YEARLY, 'access' => 1));

    $this->regenerateSitemap();
    $this->drupalGet('sitemap.xml');
    $this->assertResponse(200);
    $this->assertText($entity->url());

    $id = $entity->id();
    $entity->delete();
    $this->assertNoSitemapLink('entity_test', $id);
  }

  public function testUserCannotViewEntity() {
    // allow anonymous user to view entity
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->revokePermission('view test entity');
    $user_role->save();

    xmlsitemap_link_bundle_enable('entity_test', 'entity_test');

    $entity = entity_create('entity_test', array(
      'bundle' => 'entity_test',
    ));
    $entity->save();
    $this->assertSitemapLinkValues('entity_test', $entity->id(), array('status' => 0, 'priority' => 0.5, 'changefreq' => 0, 'access' => 0));
  }

}
