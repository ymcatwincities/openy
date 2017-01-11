<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\PurgerDeleteForm.
 *
 * @group purge_ui
 */
class PurgerDeleteFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.purger_delete_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_purger_test', 'purge_ui'];

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
    $this->initializePurgersService(['c']);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'id0']));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'id0']));
    $this->assertResponse(200);
    // Non-existing ID's also need to get passed through to the form because
    // else the submit would break exactly after the purger was deleted.
    $this->drupalGet(Url::fromRoute($this->route, ['id' => "doesnotexist"]));
    $this->assertResponse(200);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo() {
    $this->initializePurgersService(['c']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'id0']));
    $this->assertRaw(t('No'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'id0'])->toString(), [], ['op' => t('No')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that 'Yes, delete..', deletes the purger and closes the window.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::deletePurger
   */
  public function testDelete() {
    $this->initializePurgersService(['c']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'id0']));
    $this->assertRaw(t('Yes, delete this purger!'));
    $this->assertTrue(array_key_exists('id0', $this->purgePurgers->getPluginsEnabled()));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'id0'])->toString(), [], ['op' => t('Yes, delete this purger!')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertTrue(is_array($this->purgePurgers->getPluginsEnabled()));
    $this->assertTrue(empty($this->purgePurgers->getPluginsEnabled()));
    $this->assertEqual(3, count($json));
    // Assert that deleting a purger that does not exist, passes silently.
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'doesnotexist'])->toString(), [], ['op' => t('Yes, delete this purger!')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

}
