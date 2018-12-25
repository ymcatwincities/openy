<?php

namespace Drupal\Tests\panelizer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group panelizer
 */
class PanelizerAddDefaultLinkTest extends BrowserTestBase {

  use PanelizerTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'field',
    'field_ui',
    'node',

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
   * Confirm a content type can be panelized and unpanelized.
   */
  public function test() {
    // Place the local actions block in the theme so that we can assert the
    // presence of local actions and such.
    $this->drupalPlaceBlock('local_actions_block', [
      'region' => 'content',
      'theme' => \Drupal::theme()->getActiveTheme()->getName(),
    ]);

    $content_type = 'page';

    // Log in the user.
    $this->loginUser1();

    // Create the content type.
    $this->drupalCreateContentType(['type' => $content_type, 'name' => 'Page']);

    // Panelize the content type.
    $this->panelize($content_type);

    // Confirm that the content type is now panelized.
    $this->assertLink('Add a new Panelizer default display');

    // Un-panelize the content type.
    $this->unpanelize($content_type);

    // Confirm that the content type is no longer panelized.
    $this->assertNoLink('Add a new Panelizer default display');
  }

}
