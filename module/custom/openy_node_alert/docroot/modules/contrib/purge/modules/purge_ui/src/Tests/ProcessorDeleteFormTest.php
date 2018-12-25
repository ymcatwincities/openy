<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ProcessorDeleteForm.
 *
 * @group purge_ui
 */
class ProcessorDeleteFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.processor_delete_form';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_ui', 'purge_processor_test'];

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
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'a']));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'a']));
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'c']));
    $this->assertResponse(404);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => "doesnotexist"]));
    $this->assertResponse(404);
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\ProcessorDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'a']));
    $this->assertRaw(t('No'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'a'])->toString(), [], ['op' => t('No')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

  /**
   * Tests that 'Yes, delete..', deletes the processor and closes the window.
   *
   * @see \Drupal\purge_ui\Form\ProcessorDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::disableProcessor
   */
  public function testDeleteProcessor() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route, ['id' => 'a']));
    $this->assertRaw(t('Yes, delete this processor!'));
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route, ['id' => 'a'])->toString(), [], ['op' => t('Yes, delete this processor!')]);
    $this->assertEqual('redirect', $json[1]['command']);
    $this->assertEqual('closeDialog', $json[2]['command']);
    $this->assertEqual(3, count($json));
  }

}
