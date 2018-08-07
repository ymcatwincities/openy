<?php

namespace Drupal\Tests\fontyourface\Functional;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that installing @font-your-face provides access to the necessary sections.
 *
 * @group fontyourface
 */
class FontYourFaceInstallTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [];

  /**
   * A test user with permission to access the @font-your-face sections.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('module_installer')->install(['views', 'fontyourface']);

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer font entities',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests @font-your-face install and admin page shows up.
   */
  public function testFontYourFaceSections() {
    // Main font selection page.
    $this->drupalGet(Url::fromRoute('entity.font.collection'));
    $this->assertText(t('Font Selector'));

    // Font display page.
    $this->drupalGet(Url::fromRoute('entity.font_display.collection'));
    $this->assertText(t('There is no Font display yet.'));

    // Font display add page.
    $this->drupalGet(Url::fromRoute('entity.font_display.add_form'));
    $this->assertText(t('Please enable at least one font before creating/updating a font style.'));

    // Font settings page.
    $this->drupalGet(Url::fromRoute('font.settings'));
    $this->assertText(t('Settings form for @font-your-face. Support modules can use this form for settings or to import fonts.'));
    $this->assertRaw(t('Import all fonts'));
  }

}
