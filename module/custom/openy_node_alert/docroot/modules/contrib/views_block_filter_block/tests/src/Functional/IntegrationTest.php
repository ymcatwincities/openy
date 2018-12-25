<?php

namespace Drupal\Tests\views_block_filter_block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class IntegrationTest.
 *
 * @group views_block_filter_block
 */
class IntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'node',
    'views',
    'views_block_filter_block',
    'views_ui',
  ];

  /**
   * A user with admin rights.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create and log in a user with administer views permission.
    $permissions = [
      'administer modules',
      'administer views',
      'administer blocks',
      'bypass node access',
      'access user profiles',
      'view all revisions',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test view & block creation trough the UI.
   *
   * Creates a view trough the UI, configures it to use an exposed filter block
   * and checks if the block shows up on the block listing.
   */
  public function testCreateBlockViewAndSave() {
    // Create a simple view trough the UI.
    $view = [];
    $view['label'] = 'Human readable name';
    $view['id'] = 'machine';
    $view['page[create]'] = FALSE;
    $view['block[create]'] = TRUE;
    $this->drupalPostForm('admin/structure/views/add', $view, 'Save and edit');

    // Assert that the "exposed form in block" text exists; click and submit it.
    $this->assertText('Exposed form in block');
    $this->clickViewsOperationLink('No', '/exposed_block');
    $this->drupalPostForm(NULL, ['exposed_block' => TRUE], 'Apply');

    // Save the view.
    $this->drupalPostForm(NULL, [], 'Save');

    // Ensure the view exists on the Views overview page.
    $this->drupalGet('admin/structure/views');
    $this->assertText($view['label']);

    // Ensure the exposed block edit value was saved.
    $this->clickViewsOperationLink('Edit', '/view/' . $view['id']);
    $this->clickViewsOperationLink('Yes', '/exposed_block');

    // Ensure the exposed form block exists as a block.
    $this->drupalGet('/admin/structure/block/library/classy');
    $this->assertText('Exposed form: ' . $view['id'] . '-block');
  }

  /**
   * Tests the placement of the block.
   */
  public function testPlacedBlock() {
    // Visit the front page, where the filter block should appear.
    $this->drupalGet('node');

    // Ensure the configured filters are exposed.
    $this->assertFieldByName('status', 1);

    // Ensure the form action points to the absolute URL of the current page.
    $expected_url = $this->getAbsoluteUrl('node');
    $form = $this->xpath($this->buildXPathQuery('//form[@id=:id]', [':id' => 'views-exposed-form-test-view-block']));
    $action = (string) $form[0]['action'];
    $this->assertEquals($action, $expected_url);

    // Try using the exposed filter form.
    $this->drupalGet($action . '?status=0');

    // Ensure the value was changed.
    $this->assertFieldByName('status', 0);
  }

  /**
   * Click a link to perform an operation on a view.
   *
   * In general, we expect lots of links titled "enable" or "disable" on the
   * various views listing pages, and they might have tokens in them. So we
   * need this to find the correct one to click.
   *
   * @param string $label
   *   Text between the anchor tags of the desired link.
   * @param string $unique_href_part
   *   A unique string that is expected to occur within the href of the desired
   *   link. For example, if the link URL is expected to look like
   *   "admin/structure/views/view/frontpage/...", then "/frontpage/" could be
   *   passed as the expected unique string.
   */
  protected function clickViewsOperationLink($label, $unique_href_part) {
    /** @var \Behat\Mink\Element\NodeElement[] $links */
    $links = $this->xpath('//a[normalize-space(text())=:label]', [':label' => $label]);
    foreach ($links as $link_index => $link) {
      $position = strpos($link->getOuterHtml(), $unique_href_part);
      if ($position !== FALSE) {
        $this->assertTrue(TRUE, "Link to $label containing $unique_href_part found.");
        $link->click();
        return;
      }
    }

    $this->fail("Link to $label containing $unique_href_part found.");
  }

}
