<?php

namespace Drupal\Tests\fontyourface\Functional;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that installing @font-your-face local fonts module is not broken.
 *
 * @group fontyourface
 */
class FontYourFaceLocalFontInstallTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['views', 'fontyourface', 'local_fonts'];

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
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer font entities',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests @font-your-face install and admin page shows up.
   */
  public function testFontYourFaceLocalFontsSection() {
    // Font settings page.
    $this->drupalGet(Url::fromRoute('entity.local_font_config_entity.collection'));
    $this->assertText(t('There is no Custom Font yet.'));
    $this->drupalGet(Url::fromRoute('entity.local_font_config_entity.add_form'));
    $this->assertText(t('Name of the Custom Font'));
  }

}
