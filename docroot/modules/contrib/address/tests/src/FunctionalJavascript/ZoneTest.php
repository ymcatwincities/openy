<?php

namespace Drupal\Tests\address\FunctionalJavascript;

use Drupal\address\Entity\Zone;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\simpletest\BlockCreationTrait;

/**
 * Tests the zone entity and UI.
 *
 * @group address
 */
class ZoneTest extends JavascriptTestBase {

  use BlockCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'block',
    'system',
    'user',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('local_tasks_block');
    $this->placeBlock('local_actions_block');
    $this->placeBlock('page_title_block');

    $this->adminUser = $this->drupalCreateUser([
      'administer zones',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests creating a zone via UI.
   */
  function testCreateZone() {
    $this->drupalGet('admin/config/regional/zones/add');
    $session = $this->getSession();

    // Add a Country zone member, select the US.
    $this->submitForm(['plugin' => 'country'], t('Add'));
    $this->waitForAjaxToFinish();
    $session->getPage()->fillField('members[0][form][name]', 'California');
    $session->getPage()->fillField('members[0][form][country_code]', 'US');
    $this->waitForAjaxToFinish();

    // Add an EU zone member.
    $this->submitForm(['plugin' => 'eu'], t('Add'));
    $this->waitForAjaxToFinish();

    // Add, then remove a Zone zone member.
    // Confirms that removing unsaved zone members works.
    $this->submitForm(['plugin' => 'zone'], t('Add'));
    $this->waitForAjaxToFinish();
    $this->submitForm([], 'remove_member2');
    $this->waitForAjaxToFinish();

    // Finish creating the zone and zone members.
    $edit = [
      'id' => 'test_zone',
      'name' => 'Test zone',
      'scope' => $this->randomMachineName(6),
      'members[0][form][name]' => 'California',
      'members[0][form][administrative_area]' => 'CA',
      'members[0][form][included_postal_codes]' => '123',
      'members[0][form][excluded_postal_codes]' => '456',
      'members[1][form][name]' => 'European Union',
    ];
    $this->submitForm($edit, t('Save'));

    // Add country code now. If with send it on previous submit, phantomjs will
    // perform ajax request and other datas will be lost.
    $edit['members[0][form][country_code]'] = 'US';

    // Load new Zone so we can check everything is saved.
    $zone = Zone::load($edit['id']);
    $this->assertEquals($zone->getName(), $edit['name'], 'The created zone has the correct name.');
    $this->assertEquals($zone->getScope(), $edit['scope'], 'The created zone has the correct scope.');
    $members = $zone->getMembers();
    $this->assertTrue(count($members) == 2, 'The created zone has the correct number of members.');
    // $members is a plugin collection which doesn't support array access.
    $members_array = [];
    foreach ($members as $member) {
      $members_array[] = $member;
    }
    $first_member = reset($members_array);
    $this->assertEquals($first_member->getName(), $edit['members[0][form][name]'], 'The first created zone member has the correct name.');
    $first_member_configuration = $first_member->getConfiguration();
    $this->assertEquals($first_member_configuration['country_code'], $edit['members[0][form][country_code]'], 'The first created zone member has the correct country.');
    $this->assertEquals($first_member_configuration['administrative_area'], $edit['members[0][form][administrative_area]'], 'The first created zone member has the correct administrative area.');
    $this->assertEquals($first_member_configuration['included_postal_codes'], $edit['members[0][form][included_postal_codes]'], 'The first created zone member has the correct included postal codes.');
    $this->assertEquals($first_member_configuration['excluded_postal_codes'], $edit['members[0][form][excluded_postal_codes]'], 'The first created zone member has the correct excluded postal codes.');
    $second_member = end($members_array);
    $this->assertEquals($second_member->getName(), $edit['members[1][form][name]'], 'The second created zone member has the correct name.');

    // Add another zone that references the current one.
    $this->drupalGet('admin/config/regional/zones/add');
    $this->submitForm(['plugin' => 'zone'], t('Add'));
    $this->waitForAjaxToFinish();

    $edit = [
      'id' => 'test_zone2',
      'name' => $this->randomMachineName(),
      'members[0][form][name]' => 'Previous zone',
      'members[0][form][zone]' => 'Test zone (test_zone)',
    ];
    $this->submitForm($edit, t('Save'));

    $zone = Zone::load($edit['id']);
    $members = $zone->getMembers();
    $this->assertTrue(count($members) == 1, 'The created zone has the correct number of members.');
    // $members is a plugin collection which doesn't support array access.
    $members_array = [];
    foreach ($members as $member) {
      $members_array[] = $member;
    }
    $first_member = reset($members_array);
    $this->assertEquals($first_member->getName(), $edit['members[0][form][name]'], 'The first created zone member has the correct name.');
    $this->assertEquals($first_member->getConfiguration()['zone'], 'test_zone', 'The first created zone member has the correct zone.');
  }

  /**
   * Tests editing a zone via UI.
   */
  function testEditZone() {
    $zone = $this->createZone([
      'id' => strtolower($this->randomMachineName(6)),
      'name' => $this->randomMachineName(),
      'scope' => $this->randomMachineName(6),
    ]);

    $this->drupalGet('admin/config/regional/zones/manage/' . $zone->id());
    $edit = [
      'name' => $this->randomMachineName(),
    ];
    $this->submitForm($edit, t('Save'));

    \Drupal::service('entity_type.manager')->getStorage('zone')->resetCache([$zone->id()]);
    $zone = Zone::load($zone->id());
    $this->assertEquals($zone->getName(), $edit['name'], 'The zone name has been successfully changed.');
  }

  /**
   * Tests deleting a zone via UI.
   */
  function testDeleteZone() {
    $zone = $this->createZone([
      'id' => strtolower($this->randomMachineName(6)),
      'name' => $this->randomMachineName(),
      'scope' => $this->randomMachineName(6),
    ]);

    $this->drupalGet('admin/config/regional/zones/manage/' . $zone->id() . '/delete');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains(t('This action cannot be undone.'));
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('zone')->resetCache([$zone->id()]);
    $zone_exists = (bool) Zone::load($zone->id());
    $this->assertFalse($zone_exists, 'The zone has been deleted from the database.');
  }

  /**
   * Creates a new zone entity.
   *
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new zone entity.
   */
  protected function createZone(array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage('zone');
    $zone = $storage->create($values);
    $status = $zone->save();
    $this->assertEquals($status, SAVED_NEW, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $zone->getEntityType()->getLabel(),
      '%type' => $zone->id()
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $zone = $storage->load($zone->id());

    return $zone;
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
