<?php

namespace Drupal\Tests\panels_ipe\FunctionalJavascript;

/**
 * Tests the JavaScript functionality of Panels IPE with PageManager.
 *
 * @group panels
 */
class PageManagerIntegrationTest extends PanelsIPETestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'panels',
    'panels_ipe',
    'page_manager',
    'panels_ipe_page_manager_test_config',
    'system',
  ];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user with appropriate permissions to use Panels IPE.
    $this->user1 = $this->drupalCreateUser([
      'access panels in-place editing',
      'administer blocks',
      'administer pages',
    ]);
    $this->user2 = $this->drupalCreateUser([
      'access panels in-place editing',
      'administer blocks',
      'administer pages',
    ]);

    $this->drupalLogin($this->user1);

    $this->test_route = 'test-page';
  }

  /**
   * Tests that the IPE editing session is specific to a user.
   */
  public function testUserEditSession() {
    $this->visitIPERoute();
    $this->assertSession()->elementExists('css', '.layout--onecol');

    // Change the layout to lock the IPE.
    $this->changeLayout('Columns: 2', 'layout_twocol');
    $this->assertSession()->elementExists('css', '.layout--twocol');
    $this->assertSession()->elementNotExists('css', '.layout--onecol');
    $this->assertSession()->elementExists('css', '[data-tab-id="save"]');

    // Ensure the second user does not see the session of the other user.
    $this->drupalLogin($this->user2);
    $this->visitIPERoute();
    $this->assertSession()->elementExists('css', '.layout--onecol');
    $this->assertSession()->elementNotExists('css', '.layout--twocol');
    // Ensure the IPE is locked.
    $this->assertSession()->elementNotExists('css', '[data-tab-id="edit"]');
    $this->assertSession()->elementExists('css', '[data-tab-id="locked"]');

    // Click the break lock button.
    $this->breakLock();
    $this->assertSession()->waitForElementVisible('css', '[data-tab-id="edit"]');

    // Log back in as the first user to find the edits gone.
    $this->drupalLogin($this->user1);
    $this->visitIPERoute();
    $this->assertSession()->elementExists('css', '[data-tab-id="edit"]');
    $this->assertSession()->elementNotExists('css', '[data-tab-id="save"]');
    $this->assertSession()->elementExists('css', '.layout--onecol');
  }

}
