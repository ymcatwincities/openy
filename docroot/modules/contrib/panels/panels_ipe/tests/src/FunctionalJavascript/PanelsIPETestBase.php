<?php

namespace Drupal\Tests\panels_ipe\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Base class which runs through standard Panels IPE test routines.
 */
abstract class PanelsIPETestBase extends JavascriptTestBase {

  use PanelsIPETestTrait;

  /**
   * The route that IPE tests should be ran on.
   */
  protected $test_route;

  /**
   * The window size set when calling $this->visitIPERoute().
   */
  protected $window_size = [1024, 768];

  /**
   * Tests that the IPE is loaded on the current test route.
   */
  public function testIPEIsLoaded() {
    $this->visitIPERoute();

    $this->assertIPELoaded();
  }

  /**
   * Tests that adding a block with default configuration works.
   */
  public function testIPEAddBlock() {
    $this->visitIPERoute();

    $this->addBlock('System', 'system_breadcrumb_block');
  }

  /**
   * Tests that changing layout from one (default) to two columns works.
   */
  public function testIPEChangeLayout() {
    $this->visitIPERoute();

    // Change the layout to two columns.
    $this->changeLayout('Columns: 2', 'layout_twocol');
    $this->waitUntilVisible('.layout--twocol', 10000, 'Layout changed to two column.');
  }

  /**
   * Visits the test route and sets an appropriate window size for IPE.
   */
  protected function visitIPERoute() {
    $this->drupalGet($this->test_route);

    // Set the window size to ensure that IPE elements are visible.
    call_user_func_array([$this->getSession(), 'resizeWindow'], $this->window_size);
  }

}
