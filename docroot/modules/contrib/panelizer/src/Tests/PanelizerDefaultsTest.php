<?php

namespace Drupal\panelizer\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;

/**
 * Confirm the defaults functionality works.
 *
 * @group panelizer
 */
class PanelizerDefaultsTest extends WebTestBase {

  use PanelizerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'ctools',
    'layout_plugin',
    'node',
    'panelizer',
    'panels',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $account = User::load(1);
    $account->setPassword('foo')->save();
    $account->pass_raw = 'foo';
    $this->drupalLogin($account);
  }

  public function test() {
    $this->panelize('page');

    // Create an additional default layout so we can assert that it's available
    // as an option when choosing the layout on the node form.
    $default_id = $this->addPanelizerDefault('page');
    $this->assertDefaultExists('page', 'default', $default_id);

    // The user should only be able to choose the layout if specifically allowed
    // to (the panelizer[allow] checkbox in the view display configuration). By
    // default, they aren't.
    $this->drupalGet('node/add/page');
    $this->assertResponse(200);
    $this->assertNoFieldByName('panelizer[0][default]');

    // Enable layout selection and assert that all the expected fields show up.
    $this->panelize('page', NULL, ['panelizer[allow]' => TRUE]);
    $this->drupalGet('node/add/page');
    $this->assertResponse(200);
    $view_modes = \Drupal::service('entity_display.repository')->getViewModes('node');
    $view_modes = array_filter($view_modes, function (array $view_mode) {
      // View modes that are inheriting the default display (i.e., status is
      // FALSE) will not show up unless they, too, are panelized. But in this
      // test, we only panelized the default display.
      return $view_mode['status'] == FALSE;
    });
    for ($i = 0; $i < count($view_modes); $i++) {
      $this->assertFieldByName("panelizer[{$i}][default]");
      $this->assertOption("edit-panelizer-{$i}-default", 'default');
      $this->assertOption("edit-panelizer-{$i}-default", $default_id);
    }

    $this->deletePanelizerDefault('page', 'default', $default_id);
    $this->assertDefaultNotExists('page', 'default', $default_id);
  }

}
