<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\PurgerMoveForm.
 *
 * @group purge_ui
 */
class PurgerMoveFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form (moving down).
   *
   * @var string
   */
  protected $route_down = 'purge_ui.purger_move_down_form';

  /**
   * The route that renders the form (moving up).
   *
   * @var string
   */
  protected $route_up = 'purge_ui.purger_move_up_form';

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
    $args_down = ['id' => 'id0', 'direction' => 'down'];
    $args_up = ['id' => 'id0', 'direction' => 'up'];
    $this->initializePurgersService(['a']);
    $this->drupalGet(Url::fromRoute($this->route_down, $args_down));
    $this->assertResponse(403);
    $this->drupalGet(Url::fromRoute($this->route_up, $args_up));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route_down, $args_down));
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute($this->route_up, $args_up));
    $this->assertResponse(200);
    $args_down = ['id' => 'doesnotexist', 'direction' => 'down'];
    $args_up = ['id' => 'doesnotexist', 'direction' => 'up'];
    $this->drupalGet(Url::fromRoute($this->route_down, $args_down));
    $this->assertResponse(404);
    $this->drupalGet(Url::fromRoute($this->route_up, $args_up));
    $this->assertResponse(404);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\PurgerMoveForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo() {
    $args_down = ['id' => 'id0', 'direction' => 'down'];
    $args_up = ['id' => 'id0', 'direction' => 'up'];
    $this->initializePurgersService(['a']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route_down, $args_down));
    $this->assertRaw(t('No'));
    $this->drupalGet(Url::fromRoute($this->route_up, $args_up));
    $this->assertRaw(t('No'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route_down, $args_down)->toString(), [], ['op' => t('No')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route_up, $args_up)->toString(), [], ['op' => t('No')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that 'Yes!', moves the purger in order and closes the window.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::deletePurger
   */
  public function testMove() {
    $down = ['id' => 'id0', 'direction' => 'down'];
    $up = ['id' => 'id2', 'direction' => 'up'];
    $this->initializePurgersService(['a', 'b', 'c']);
    $this->drupalLogin($this->admin_user);
    // Test that the initial order of the purgers is exactly as configured.
    $this->assertEqual(['a', 'b', 'c'], array_values($this->purgePurgers->getPluginsEnabled()));
    // Test the 'down' variant of the move form.
    $this->drupalGet(Url::fromRoute($this->route_down, $down));
    $this->assertRaw('Do you want to move Purger A down in the execution order?');
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route_down, $down)->toString(), [], ['op' => t('Yes!')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertEqual(['b', 'a', 'c'], array_values($this->purgePurgers->getPluginsEnabled()));
    // Test the 'up' variant of the move form.
    $this->drupalGet(Url::fromRoute($this->route_up, $up));
    $this->assertRaw('Do you want to move Purger C up in the execution order?');
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route_up, $up)->toString(), [], ['op' => t('Yes!')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $this->purgePurgers->reload();
    $this->assertEqual(['b', 'c', 'a'], array_values($this->purgePurgers->getPluginsEnabled()));
  }

}
