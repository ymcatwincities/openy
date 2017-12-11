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

    // Get all enabled view modes machine names for page.
    $view_modes = array_keys(\Drupal::service('entity_display.repository')
                               ->getViewModeOptionsByBundle('node', 'page'));
    foreach ($view_modes as $i => $view_mode_name) {
      // Be sure view mode can be panelized.
      $this->panelize('page', $view_mode_name);
      // Create an additional default layout so we can assert that it's available
      // as an option when choosing the layout on the node form.
      $panelizer_id = $this->addPanelizerDefault('page', $view_mode_name);
      $this->assertDefaultExists('page', $view_mode_name, $panelizer_id);
      // The user should only be able to choose the layout if specifically allowed
      // to (the panelizer[allow] checkbox in the view display configuration). By
      // default, they aren't.
      $this->drupalGet('node/add/page');
      $this->assertResponse(200);
      $this->assertNoFieldByName("panelizer['{$i}][default]");
      // Allow user to select panelized modes in UI.
      $this->panelize('page', $view_mode_name, [
        'panelizer[custom]' => TRUE,
        'panelizer[allow]' => TRUE,
      ]);
      $this->drupalGet('node/add/page');
      $this->assertResponse(200);
      $this->assertFieldByName("panelizer[{$i}][default]");
      $this->assertOption("edit-panelizer-{$i}-default", 'default');
      $this->assertOption("edit-panelizer-{$i}-default", $panelizer_id);
      // Clean up.
      $this->deletePanelizerDefault('page', $view_mode_name, $panelizer_id);
      $this->assertDefaultNotExists('page', $view_mode_name, $panelizer_id);
    }
  }

}
