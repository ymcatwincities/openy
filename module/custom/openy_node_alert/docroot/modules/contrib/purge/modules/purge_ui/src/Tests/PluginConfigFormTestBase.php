<?php

namespace Drupal\purge_ui\Tests;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\purge\Tests\WebTestBase;

/**
 * Testbase for \Drupal\purge_ui\Form\PluginConfigFormBase derivatives.
 */
abstract class PluginConfigFormTestBase extends WebTestBase {

  /**
   * User account with suitable permission to access the form.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $admin_user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['purge_ui'];

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route;

  /**
   * The route to the plugin's configuration form, takes argument 'id'.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $routeDialog;

  /**
   * The plugin ID for which the form tested is rendered for.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The full class of the form being tested.
   *
   * @var string
   */
  protected $formClass;

  /**
   * Form arguments.
   *
   * @var array
   */
  protected $formArgs = ['id' => NULL, 'dialog' => FALSE];

  /**
   * Form arguments.
   *
   * @var array
   */
  protected $formArgsDialog = ['id' => NULL, 'dialog' => TRUE];

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Assert that the title is present.
   */
  protected function assertFormTitle() {
    throw new \Exception("Derivatives need to implement ::assertFormTitle().");
  }

  /**
   * Initialize the plugin instance required to render the form.
   */
  protected function initializePlugin() {
    throw new \Exception("Derivatives need to implement ::initializePlugin().");
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(['administer site configuration']);

    // Initialize the plugin, form arguments and the form builder.
    $this->formArgs['id'] = $this->formArgsDialog['id'] = $this->getId();
    $this->formBuilder = $this->container->get('form_builder');
    $this->initializePlugin();

    // Instantiate the routes.
    if (is_string($this->route)) {
      $this->route = Url::fromRoute($this->route, ['id' => $this->getId()]);
      $this->route->setAbsolute(FALSE);
    }
    if (is_string($this->routeDialog)) {
      $this->routeDialog = Url::fromRoute($this->routeDialog, ['id' => $this->getId()]);
      $this->routeDialog->setAbsolute(FALSE);
    }
  }

  /**
   * Return a new instance of the form being tested.
   *
   * @return \Drupal\purge_ui\Form\PluginConfigFormBase derivative.
   */
  protected function getFormInstance() {
    $class = $this->formClass;
    return $class::create($this->container);
  }

  /**
   * Retrieve a new formstate instance.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   */
  protected function getFormStateInstance() {
    return new FormState();
  }

  /**
   * Return the ID argument given to the form.
   */
  protected function getId() {
    return $this->plugin;
  }

  /**
   * Verify that the form loads at the expected place.
   */
  public function testFormArray() {
    // Test the form - without dialog switch - on basic shared characteristics.
    $form = $this->formBuilder->getForm($this->formClass, $this->formArgs);
    $this->assertFalse(isset($form['#attached']['library'][0]));
    $this->assertFalse(isset($form['#prefix']));
    $this->assertFalse(isset($form['#suffix']));
    $this->assertFalse(isset($form['actions']['submit']['#ajax']['callback']));
    // Test the dialog version, which should have all of these fields.
    $form = $this->formBuilder->getForm($this->formClass, $this->formArgsDialog);
    $this->assertTrue(isset($form['#attached']['library'][0]));
    $this->assertTrue(isset($form['#prefix']));
    $this->assertTrue(isset($form['#suffix']));
    $this->assertTrue(isset($form['actions']['submit']['#ajax']['callback']));
  }

  /**
   * Verify that the form loads at the expected place.
   */
  public function testFormAccess() {
    $this->drupalGet($this->route);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->route);
    $this->assertResponse(200);
    $this->assertFormTitle();
    $this->assertNoField('edit-cancel');
    $this->assertField('edit-submit');
    $this->assertRaw('Save configuration');
  }

  /**
   * Verify that the form loads at the expected place.
   */
  public function testFormAccessDialog() {
    $this->drupalGet($this->routeDialog);
    $this->assertResponse(403);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet($this->routeDialog);
    $this->assertResponse(200);
    $this->assertFormTitle();
    $this->assertField('edit-cancel');
    $this->assertRaw('Cancel');
    $this->assertField('edit-submit');
    $this->assertRaw('Save configuration');
  }

}
