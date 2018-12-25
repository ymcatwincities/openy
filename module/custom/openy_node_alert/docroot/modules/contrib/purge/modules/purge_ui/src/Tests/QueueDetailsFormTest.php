<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests:
 *   - \Drupal\purge_ui\Form\PluginDetailsForm.
 *   - \Drupal\purge_ui\Controller\QueueFormController::detailForm().
 *   - \Drupal\purge_ui\Controller\QueueFormController::detailFormTitle().
 *
 * @group purge_ui
 */
class QueueDetailsFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.queue_detail_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_queue_test', 'purge_ui'];

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
    $this->drupalGet(Url::fromRoute($this->route, []));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, []));
    $this->assertResponse(200);
  }

  /**
   * Tests that the close button works and that content exists.
   *
   * @see \Drupal\purge_ui\Form\QueueDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDetailForm() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, []));
    $this->assertRaw('Memory');
    $this->assertRaw('A non-persistent, per-request memory queue (not useful on production systems).');
    $this->assertRaw(t('Close'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, [])->toString(), [], ['op' => t('Close')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

}
