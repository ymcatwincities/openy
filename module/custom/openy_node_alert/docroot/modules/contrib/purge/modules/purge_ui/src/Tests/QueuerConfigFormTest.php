<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Tests the drop-in configuration form for queuers (modal dialog).
 *
 * @group purge_ui
 * @see \Drupal\purge_ui\Controller\DashboardController
 * @see \Drupal\purge_ui\Controller\QueuerFormController
 * @see \Drupal\purge_ui\Form\QueuerConfigFormBase
 */
class QueuerConfigFormTest extends WebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $admin_user;

  /**
   * Name of the queuer plugin that does have a form configured.
   *
   * @var string
   */
  protected $queuer = 'withform';

  /**
   * The route to a queuers configuration form (takes argument 'id').
   *
   * @var string
   */
  protected $route = 'purge_ui.queuer_config_form';

  /**
   * The route to a queuers configuration form (takes argument 'id') - dialog.
   *
   * @var string
   */
  protected $route_dialog = 'purge_ui.queuer_config_dialog_form';

  /**
   * The URL object constructed from $this->route.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlValid = NULL;

  /**
   * The URL object constructed from $this->route_dialog.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlValidDialog = NULL;

  /**
   * The URL object constructed from $this->route - invalid argument.
   *
   * @var \Drupal\Core\Url
   */
  protected $urlInvalid = NULL;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_queuer_test', 'purge_ui'];

  /**
   * Setup the test.
   */
  public function setUp() {
    parent::setUp();
    $this->initializeQueuersService(['c', $this->queuer]);
    $this->urlValid = Url::fromRoute($this->route, ['id' => $this->queuer]);
    $this->urlValidDialog = Url::fromRoute($this->route_dialog, ['id' => $this->queuer]);
    $this->urlInvalid = Url::fromRoute($this->route, ['id' => 'c']);
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests permissions, the controller and form responses.
   */
  public function testForm() {
    $this->drupalGet($this->urlValid);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->urlInvalid);
    $this->assertResponse(404);
    // Test the plain version of the form.
    $this->drupalGet($this->urlValid);
    $this->assertResponse(200);
    $this->assertRaw(t('Save configuration'));
    $this->assertNoRaw(t('Cancel'));
    $this->assertFieldByName('textfield');
    // Test the modal dialog version of the form.
    $this->drupalGet($this->urlValidDialog);
    $this->assertResponse(200);
    $this->assertRaw(t('Save configuration'));
    $this->assertRaw(t('Cancel'));
    $this->assertFieldByName('textfield');
    // Test the AJAX response of the modal dialog version.
    $json = $this->drupalPostAjaxForm($this->urlValidDialog->toString(), [], ['op' => t('Cancel')]);
    $this->assertEqual('closeDialog', $json[1]['command']);
    $this->assertEqual(2, count($json));
  }

}
