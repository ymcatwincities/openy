<?php

namespace Drupal\Tests\panelizer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Confirm the defaults functionality works.
 *
 * @group panelizer
 */
class PanelizerDefaultsTest extends BrowserTestBase {

  use PanelizerTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'field',
    'field_ui',
    'help',
    'node',
    'user',

    // Core dependencies.
    'layout_discovery',

    // Contrib dependencies.
    'ctools',
    'panels',
    'panels_ipe',

    // This module.
    'panelizer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable the Bartik theme and make it the default.
    $theme = 'bartik';
    \Drupal::service('theme_installer')->install([$theme]);
    \Drupal::service('theme_handler')->setDefault($theme);

    // Place the local actions block in the theme so that we can assert the
    // presence of local actions and such.
    $this->drupalPlaceBlock('local_actions_block', [
      'region' => 'content',
      'theme' => $theme,
    ]);
  }

  public function test() {
    $this->setupContentType();
    $this->loginUser1();

    // Create an additional default layout so we can assert that it's available
    // as an option when choosing the layout on the node form.
    $default_id = $this->addPanelizerDefault();

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
