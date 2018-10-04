<?php

namespace Drupal\Tests\fontyourface\Functional;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that installing @font-your-face submodules is not broken.
 *
 * @group fontyourface
 */
class FontYourFaceSubmoduleInstallTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['views', 'fontyourface', 'websafe_fonts_test'];

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
  public function testFontYourFaceSections() {
    // Font settings page.
    $this->drupalGet(Url::fromRoute('font.settings'));
    $this->assertText(t('Settings form for @font-your-face. Support modules can use this form for settings or to import fonts.'));
    $this->assertRaw(t('Import from websafe_fonts_test'));
  }

  /**
   * Tests importing fonts from websafe_fonts_test.
   */
  public function testImportWebSafeFonts() {
    // Assert no fonts exist to start.
    $this->drupalGet(Url::fromRoute('entity.font.collection'));
    $this->assertNoText('Arial');

    $this->drupalPostForm(Url::fromRoute('font.settings'), [], t('Import from websafe_fonts_test'));
    $this->assertResponse(200);
    $this->assertText(t('Finished importing fonts.'));

    // Assert all fonts were imported.
    $this->drupalGet(Url::fromRoute('entity.font.collection'));
    $this->assertText('Arial');
    $this->assertText('Verdana');
    $this->assertText('Courier New');
    $this->assertText('Georgia');

    // Assert fonts load on font collection page.
    $this->assertRaw('<meta name="Websafe Font" content="Arial" />');
    $this->assertRaw('<meta name="Websafe Font" content="Courier New" />');
    $this->assertRaw('<meta name="Websafe Font" content="Georgia" />');
    $this->assertRaw('<meta name="Websafe Font" content="Verdana" />');

    // ENsure font is not loaded on front page because font is not enabled.
    $this->drupalGet('<front>');
    $this->assertNoRaw('<meta name="Websafe Font" content="Arial" />');
  }

  /**
   * Tests enabling and seeing fonts load.
   */
  public function testEnableWebSafeFonts() {
    // Assert no fonts load to start.
    $this->drupalGet('/node');
    $this->assertNoRaw('<meta name="Websafe Font" content="Arial" />');

    $this->drupalPostForm(Url::fromRoute('font.settings'), ['load_all_enabled_fonts' => 1], t('Import from websafe_fonts_test'));
    $this->drupalGet(url::fromRoute('entity.font.activate', ['font' => 1, 'js' => 'nojs']));
    $this->assertText('Font Arial successfully enabled');

    // Flush the caches. Not an issue in prod but seems to be in simpletest. Will keep an eye on it.
    $this->resetAll();

    $this->drupalGet('/node');
    $this->assertRaw('<meta name="Websafe Font" content="Arial" />');
  }

}
