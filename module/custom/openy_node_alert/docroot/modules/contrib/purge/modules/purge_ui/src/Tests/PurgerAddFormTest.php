<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\PurgerAddForm.
 *
 * @group purge_ui
 */
class PurgerAddFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'purge_ui.purger_add_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_ui', 'purge_purger_test'];

  /**
   * Setup the test.
   */
  public function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests permissions, the form controller and general form returning.
   */
  public function testAccess() {
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->initializePurgersService([]);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->initializePurgersService(['a', 'b', 'c']);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->initializePurgersService(['a', 'b', 'c', 'withform', 'good']);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(404);
    $this->initializePurgersService(['a', 'b']);
  }

  /**
   * Tests clicking the add button, adds it and closes the screen.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAdd() {
    $this->initializePurgersService(['a', 'withform', 'good']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertRaw(t('Add'));
    $this->assertTrue(count($this->purgePurgers->getPluginsEnabled()) === 3);
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), ['plugin_id' => 'c'], ['op' => t('Add')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertTrue(in_array('c', $this->purgePurgers->getPluginsEnabled()));
    $this->assertEqual(3, count($json));
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancel() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertRaw(t('Cancel'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), [], ['op' => t('Cancel')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests the 'plugin_id' form element for listing only available purgers.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   */
  public function testTwoAvailablePurgers() {
    $this->initializePurgersService(['c', 'withform']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertFieldByName('plugin_id');
    $this->assertText('Purger A');
    $this->assertText('Purger B');
    $this->assertNoText('Configurable purger');
    $this->assertFieldByName('op', t('Cancel'));
    $this->assertFieldByName('op', t('Add'));
  }

}
