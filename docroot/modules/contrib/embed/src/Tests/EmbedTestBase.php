<?php

/**
 * @file
 * Contains \Drupal\embed\Tests\TestBase.
 */

namespace Drupal\embed\Tests;

use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for all embed tests.
 */
abstract class EmbedTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'embed', 'embed_test', 'editor', 'ckeditor'];

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Filtered HTML text format and enable entity_embed filter.
    $format = FilterFormat::create([
      'format' => 'embed_test',
      'name' => 'Embed format',
      'filters' => [
      ],
    ]);
    $format->save();

    $editor_group = [
      'name' => 'Embed',
      'items' => [
        'embed_test_default',
      ],
    ];
    $editor = Editor::create([
      'format' => 'embed_test',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [[$editor_group]],
        ],
      ],
    ]);
    $editor->save();

    // Create a user with required permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer embed buttons',
      'use text format embed_test',
    ]);

    // Create a user with required permissions.
    $this->webUser = $this->drupalCreateUser([
      'use text format embed_test',
    ]);

    // Set up some standard blocks for the testing theme (Classy).
    // @see https://www.drupal.org/node/507488?page=1#comment-10291517
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @return \Drupal\file\FileInterface
   */
  protected function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles($type_name, $size));

    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
    $file->filesize = filesize($file->uri);

    $file = File::create((array) $file);
    $file->save();
    return $file;
  }

  /**
   * {@inheritdoc}
   *
   * This is a duplicate of WebTestBase::drupalProcessAjaxResponse() that
   * includes the fix from https://www.drupal.org/node/2554449 for using ID
   * selectors in AJAX commands.
   */
  protected function drupalProcessAjaxResponse($content, array $ajax_response, array $ajax_settings, array $drupal_settings) {
    // ajax.js applies some defaults to the settings object, so do the same
    // for what's used by this function.
    $ajax_settings += array(
      'method' => 'replaceWith',
    );
    // DOM can load HTML soup. But, HTML soup can throw warnings, suppress
    // them.
    $dom = new \DOMDocument();
    @$dom->loadHTML($content);
    // XPath allows for finding wrapper nodes better than DOM does.
    $xpath = new \DOMXPath($dom);
    foreach ($ajax_response as $command) {
      // Error messages might be not commands.
      if (!is_array($command)) {
        continue;
      }
      switch ($command['command']) {
        case 'settings':
          $drupal_settings = NestedArray::mergeDeepArray([$drupal_settings, $command['settings']], TRUE);
          break;

        case 'insert':
          $wrapperNode = NULL;
          // When a command specifies a specific selector, use it.
          if (!empty($command['selector']) && strpos($command['selector'], '#') === 0) {
            $wrapperNode = $xpath->query('//*[@id="' . substr($command['selector'], 1) . '"]')->item(0);
          }
          // When a command doesn't specify a selector, use the
          // #ajax['wrapper'] which is always an HTML ID.
          elseif (!empty($ajax_settings['wrapper'])) {
            $wrapperNode = $xpath->query('//*[@id="' . $ajax_settings['wrapper'] . '"]')->item(0);
          }
          // @todo Ajax commands can target any jQuery selector, but these are
          //   hard to fully emulate with XPath. For now, just handle 'head'
          //   and 'body', since these are used by
          //   \Drupal\Core\Ajax\AjaxResponse::ajaxRender().
          elseif (in_array($command['selector'], array('head', 'body'))) {
            $wrapperNode = $xpath->query('//' . $command['selector'])->item(0);
          }
          if ($wrapperNode) {
            // ajax.js adds an enclosing DIV to work around a Safari bug.
            $newDom = new \DOMDocument();
            // DOM can load HTML soup. But, HTML soup can throw warnings,
            // suppress them.
            @$newDom->loadHTML('<div>' . $command['data'] . '</div>');
            // Suppress warnings thrown when duplicate HTML IDs are encountered.
            // This probably means we are replacing an element with the same ID.
            $newNode = @$dom->importNode($newDom->documentElement->firstChild->firstChild, TRUE);
            $method = isset($command['method']) ? $command['method'] : $ajax_settings['method'];
            // The "method" is a jQuery DOM manipulation function. Emulate
            // each one using PHP's DOMNode API.
            switch ($method) {
              case 'replaceWith':
                $wrapperNode->parentNode->replaceChild($newNode, $wrapperNode);
                break;
              case 'append':
                $wrapperNode->appendChild($newNode);
                break;
              case 'prepend':
                // If no firstChild, insertBefore() falls back to
                // appendChild().
                $wrapperNode->insertBefore($newNode, $wrapperNode->firstChild);
                break;
              case 'before':
                $wrapperNode->parentNode->insertBefore($newNode, $wrapperNode);
                break;
              case 'after':
                // If no nextSibling, insertBefore() falls back to
                // appendChild().
                $wrapperNode->parentNode->insertBefore($newNode, $wrapperNode->nextSibling);
                break;
              case 'html':
                foreach ($wrapperNode->childNodes as $childNode) {
                  $wrapperNode->removeChild($childNode);
                }
                $wrapperNode->appendChild($newNode);
                break;
            }
          }
          break;

        // @todo Add suitable implementations for these commands in order to
        //   have full test coverage of what ajax.js can do.
        case 'remove':
          break;
        case 'changed':
          break;
        case 'css':
          break;
        case 'data':
          break;
        case 'restripe':
          break;
        case 'add_css':
          break;
        case 'update_build_id':
          $buildId = $xpath->query('//input[@name="form_build_id" and @value="' . $command['old'] . '"]')->item(0);
          if ($buildId) {
            $buildId->setAttribute('value', $command['new']);
          }
          break;
      }
    }
    $content = $dom->saveHTML();
    $this->setRawContent($content);
    $this->setDrupalSettings($drupal_settings);
  }

}
