<?php

namespace Drupal\panelizer\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Basic functional tests of using Panelizer with user entities.
 *
 * @group panelizer
 */
class PanelizerUserFunctionalTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'ctools',
    'ctools_block',
    'layout_plugin',
    'panelizer',
    'panelizer_test',
    'panels',
    'panels_ipe',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the admin user.
    $user = $this->drupalCreateUser([
      // Required for Panelizer.
      'administer panelizer',
      'access panels in-place editing',
      // Allow managing user entities.
      'administer users',
      // Allow managing user entity settings.
      'administer account settings',
      // View access to user profiles.
      'access user profiles',
      // Allow managing the user entity fields and display settings.
      'administer user display',
      'administer user fields',
    ]);
    $this->drupalLogin($user);

    // Enable Panelizer for this entity.
    $this->drupalGet('admin/config/people/accounts/display');
    $this->assertResponse(200);
    $edit = [
      'panelizer[enable]' => TRUE,
      'panelizer[custom]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);

    // Reload all caches.
    $this->rebuildAll();
  }

  /**
   * Tests rendering a user with Panelizer default.
   */
  public function testPanelizerDefault() {
    /** @var \Drupal\panelizer\PanelizerInterface $panelizer */
    $panelizer = \Drupal::service('panelizer');
    $displays = $panelizer->getDefaultPanelsDisplays('user', 'user', 'default');
    $display = $displays['default'];
    $display->addBlock([
      'id' => 'panelizer_test',
      'label' => 'Panelizer test',
      'provider' => 'block_content',
      'region' => 'middle',
    ]);
    $panelizer->setDefaultPanelsDisplay('default', 'user', 'user', 'default', $display);

    // Create a user, and check that the IPE is visible on it.
    $account = $this->drupalCreateUser();

    // Check the user entity page.
    $out = $this->drupalGet('user/' . $account->id());
    $this->assertResponse(200);
    $this->verbose($out);

    // Verify that 
    $elements = $this->xpath('//*[@id="panels-ipe-content"]');
    if (is_array($elements)) {
      $this->assertIdentical(count($elements), 1);
    }
    else {
      $this->fail('Could not parse page content.');
    }

    // Check that the block we added is visible.
    $this->assertText('Panelizer test');
    $this->assertText('Abracadabra');
  }

}
