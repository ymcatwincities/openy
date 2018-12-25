<?php

namespace Drupal\Tests\panels_ipe\FunctionalJavascript;

use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Trait which can be used to test Panels IPE components.
 */
trait PanelsIPETestTrait {

  /**
   * Asserts that the IPE is loaded.
   */
  protected function assertIPELoaded() {
    $this->waitUntilVisible('#panels-ipe-content');
  }

  /**
   * Asserts that an on-screen Block contains the given content.
   *
   * @param string $block_id
   *   The unique ID of the Block.
   * @param string $content
   *   The content to check.
   * @param string $message
   *   (Optional) message to pass to the assertContains() call.
   */
  protected function assertBlockContains($block_id, $content, $message = '') {
    $selector = '[data-block-id="' . $block_id . '"]';
    $block_element = $this->getSession()->getPage()->find('css', $selector);
    $this->assertContains($content, $block_element->getHtml(), $message);
  }

  /**
   * Enables the in-place editing mode of IPE.
   */
  protected function enableEditing() {
    // Click the "Edit" tab if it's not already active.
    $selector = '[data-tab-id="edit"]:not(.active)';
    $inactive_tab = $this->getSession()->getPage()->find('css', $selector);
    if ($inactive_tab) {
      $this->clickAndWait($selector);
    }
    $this->assertSession()->elementExists('css', '[data-tab-id="edit"].active');
  }

  /**
   * Disables the in-place editing mode of IPE.
   */
  protected function disableEditing() {
    // Click the "Edit" tab if it's already active.
    $selector = '[data-tab-id="edit"].active';
    $active_tab = $this->getSession()->getPage()->find('css', $selector);
    if ($active_tab) {
      $this->clickAndWait($selector);
    }
    $this->assertSession()->elementNotExists('css', '[data-tab-id="edit"].active');
  }

  /**
   * Breaks the lock of an IPE session.
   */
  protected function breakLock() {
    // Click the "Locked" tab.
    $selector = '[data-tab-id="locked"]:not(.active)';
    $inactive_tab = $this->getSession()->getPage()->find('css', $selector);
    if ($inactive_tab) {
      $this->click($selector);
    }
  }

  /**
   * Changes the IPE layout.
   *
   * This function assumes you're using Panels layouts and as a result expects
   * the PanelsIPELayoutForm to auto-submit.
   *
   * @param string $category
   *   The name of the category, i.e. "One Column".
   * @param string $layout_id
   *   The ID of the layout, i.e. "layout_onecol".
   */
  protected function changeLayout($category, $layout_id) {
    // Open the "Change Layout" tab.
    $this->clickAndWait('[data-tab-id="change_layout"]');

    // Wait for layouts to be pulled into our collection.
    $this->waitUntilNotPresent('.ipe-icon-loading');

    // Select the target category.
    $this->clickAndWait('[data-category="' . $category . '"]');

    // Select the target layout.
    $this->clickAndWait('[data-layout-id="' . $layout_id . '"]');

    // Wait for the form to load/submit.
    $this->waitUntilNotPresent('.ipe-icon-loading');

    // Wait for the edit tab to become active (happens automatically after
    // form submit).
    $this->waitUntilVisible('[data-tab-id="edit"].active');
  }

  /**
   * Adds a Block (Plugin) to the page.
   *
   * @param string $category
   *   The name of the category, i.e. "Help".
   * @param string $plugin_id
   *   The ID of the Block Plugin, i.e. "help_block".
   *
   * @return string
   *   The newly created Block ID.
   */
  protected function addBlock($category, $plugin_id) {
    // Get a list of current Block Plugins.
    $old_blocks = $this->getOnScreenBlockIDs();

    // Open the "Manage Content" tab and select the given Block Plugin.
    $this->clickAndWait('[data-tab-id="manage_content"]');

    $this->waitUntilNotPresent('.ipe-icon-loading');

    $this->clickAndWait('[data-category="' . $category . '"]');
    // @todo Remove when https://github.com/jcalderonzumba/gastonjs/issues/19
    // is fixed. Currently clicking anchor tags with nested elements is not
    // possible.
    $this->getSession()->executeScript("jQuery('" . '[data-plugin-id="' . $plugin_id . '"]' . "')[0].click()");

    // Wait for the Block form to finish loading/opening.
    $this->waitUntilNotPresent('.ipe-icon-loading');
    $this->waitUntilVisible('.ipe-form form');

    // Submit the form with default settings.
    $this->saveBlockConfigurationForm();

    // Find the newest Block Plugin.
    $new_blocks = $this->getOnScreenBlockIDs();
    $diff_blocks = array_diff($new_blocks, $old_blocks);
    $new_block = reset($diff_blocks);

    $this->assertNotFalse($new_block, 'New block was placed on screen.');

    return $new_block;
  }

  /**
   * Opens the Block configuration form for a given on-screen block.
   *
   * @param string $block_id
   *   The unique ID of the Block you want to configure.
   */
  protected function openBlockConfigurationForm($block_id) {
    $base_selector = '[data-block-id="' . $block_id . '"]';
    $configure_selector = $base_selector . ' [data-action-id="configure"]';

    // Enable the in place editor and click the configure button.
    $this->enableEditing();
    $this->clickAndWait($configure_selector);

    // Wait for the Block form to finish opening.
    $this->waitUntilNotPresent('.ipe-icon-loading');
    $this->waitForAjaxToFinish();
  }

  /**
   * Sets a configuration value on the Block configuration form.
   *
   * @param string $name
   *   The string name of the form value, i.e. settings[label].
   * @param string|bool|array $value
   *   The value for the given form value.
   */
  protected function setBlockConfigurationFormValue($name, $value) {
    $selector_converter = new CssSelectorConverter();
    $xpath = $selector_converter->toXPath('.panels-ipe-block-plugin-form [name="' . $name . '"]');

    // Set the value of the given form field.
    $this->getSession()->getDriver()->setValue($xpath, $value);
  }

  /**
   * Saves the currently open Block configuration form.
   */
  protected function saveBlockConfigurationForm() {
    $submit_selector = '.panels-ipe-block-plugin-form [data-drupal-selector="edit-submit"]';
    $this->clickAndWait($submit_selector);
  }

  /**
   * Removes a Block from the page.
   *
   * @param string $block_id
   *   The unique ID of the Block you want to remove.
   */
  protected function removeBlock($block_id) {
    $base_selector = '[data-block-id="' . $block_id . '"]';
    $remove_selector = $base_selector . ' [data-action-id="remove"]';

    // Enable the in place editor and click the remove (X) button.
    $this->enableEditing();
    $this->clickAndWait($remove_selector);

    // Ensure that the Block is removed.
    $message = 'Block does not exist after removal';
    $this->assertElementNotPresent($base_selector, $message);
  }

  /**
   * Grabs the current Block IDs from the page.
   *
   * This is required as Block IDs are randomly generated when blocks are
   * placed, so you can't predict their IDs beforehand.
   *
   * @return array
   *   An array of Block IDs.
   */
  protected function getOnScreenBlockIDs() {
    $block_ids = [];
    $session = $this->getSession();
    $blocks = $session->getPage()->findAll('css', '[data-block-id]');
    if (count($blocks)) {
      /** @var \Behat\Mink\Element\NodeElement $block */
      foreach ($blocks as $block) {
        $block_ids[] = $block->getAttribute('data-block-id');
      }
    }
    return $block_ids;
  }

  /**
   * Clicks an arbitrary element and waits for AJAX/animations to finish.
   *
   * @param string $selector
   *   The CSS selector.
   */
  protected function clickAndWait($selector) {
    $this->click($selector);
    $this->waitForAjaxToFinish();
  }

  /**
   * Waits and asserts that a given element is visible.
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  protected function waitUntilVisible($selector, $timeout = 10000, $message = '') {
    $condition = "jQuery('" . $selector . ":visible').length > 0";
    $this->assertJsCondition($condition, $timeout, $message);
  }

  /**
   * Waits and asserts that a given element is not present.
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  protected function waitUntilNotPresent($selector, $timeout = 10000, $message = '') {
    $condition = "jQuery('" . $selector . "').length === 0";
    $this->assertJsCondition($condition, $timeout, $message);
  }

  /**
   * Waits for jQuery to become ready and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
