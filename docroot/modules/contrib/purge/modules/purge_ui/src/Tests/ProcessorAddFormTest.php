<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests \Drupal\purge_ui\Form\ProcessorAddForm.
 *
 * @group purge_ui
 */
class ProcessorAddFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.processor_add_form';

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
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->initializeProcessorsService([]);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->initializeProcessorsService(['a', 'b', 'c']);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(200);
    $this->initializeProcessorsService(['a', 'b', 'c', 'withform', 'purge_ui_block_processor']);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertResponse(404);
    $this->initializeProcessorsService(['a', 'b']);
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
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
   * Tests clicking the add button, adds it and closes the screen.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAdd() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertRaw(t('Add'));
    $this->assertNoRaw(t('Processor A'));
    $this->assertNoRaw(t('Processor B'));
    $this->assertRaw(t('Processor C'));
    $this->assertRaw(t('Processor with form'));
    $this->assertTrue(count($this->purgeProcessors->getPluginsEnabled()) === 2);
    $this->assertTrue(in_array('a', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertTrue(in_array('b', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertFalse(in_array('c', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertFalse(in_array('withform', $this->purgeProcessors->getPluginsEnabled()));
    // Test that adding the plugin succeeds and results in a redirect command,
    // which only happens when it was able to save the data.
    $json = $this->drupalPostAjaxForm(Url::fromRoute($this->route)->toString(), ['id' => 'c'], ['op' => t('Add')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual('redirect', $json[2]['command']);
    $this->assertEqual(3, count($json));
  }

}
