<?php

/**
 * @file
 * Contains \Drupal\embed\Tests\EmbedButtonEditorAccessCheckTest.
 */

namespace Drupal\embed\Tests;

use Drupal\editor\Entity\Editor;

/**
 * Tests EmbedButtonEditorAccessCheck
 *
 * @group embed
 */
class EmbedButtonEditorAccessCheckTest extends EmbedTestBase {

  const SUCCESS = 'Success!';

  /**
   * Tests \Drupal\embed\Access\EmbedButtonEditorAccessCheck.
   */
  public function testEmbedButtonEditorAccessCheck() {
    // The anonymous user should have access to the plain_text format, but it
    // hasn't been configured to use an editor yet.
    $this->getRoute('plain_text', 'embed_test_default');
    $this->assertResponse(404);

    // The anonymous user should not have permission to use embed_test format.
    $this->getRoute('embed_test', 'embed_test_default');
    $this->assertResponse(403);

    // Now login a user that can use the embed_test format.
    $this->drupalLogin($this->webUser);

    $this->getRoute('plain_text', 'embed_test_default');
    $this->assertResponse(404);

    // Add an empty configuration for the plain_text editor configuration.
    $editor = Editor::create([
      'format' => 'plain_text',
      'editor' => 'ckeditor',
    ]);
    $editor->save();
    $this->getRoute('plain_text', 'embed_test_default');
    $this->assertResponse(403);

    $this->getRoute('embed_test', 'embed_test_default');
    $this->assertResponse(200);
    $this->assertText(static::SUCCESS);

    // Test preview route with an empty request.
    $this->getRoute('embed_test', 'embed_test_default', '');
    $this->assertResponse(404);

    // Test preview route with an invalid text format.
    $this->getRoute('invalid_editor', 'embed_test_default');
    $this->assertResponse(404);

    // Test preview route with an invalid embed button.
    $this->getRoute('embed_test', 'invalid_button');
    $this->assertResponse(404);
  }

  /**
   * Performs a request to the embed_test.preview_editor route.
   *
   * @param string $editor_id
   *   ID of the editor.
   * @param string $embed_button_id
   *   ID of the embed button.
   * @param string $value
   *   The query string value to include.
   *
   * @return string
   *   The retrieved HTML string.
   */
  public function getRoute($editor_id, $embed_button_id, $value = NULL) {
    $url = 'embed-test/access/' . $editor_id . '/' . $embed_button_id;
    if (!isset($value)) {
      $value = static::SUCCESS;
    }
    return $this->drupalGet($url, ['query' => ['value' => $value]]);
  }

}
