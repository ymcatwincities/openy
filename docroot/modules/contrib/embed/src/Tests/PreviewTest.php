<?php

/**
 * @file
 * Contains \Drupal\embed\Tests\PreviewTest.
 */

namespace Drupal\embed\Tests;

/**
 * Tests the preview controller and route.
 *
 * @group embed
 */
class PreviewTest extends EmbedTestBase {

  const SUCCESS = 'Success!';

  /**
   * Tests the route used for generating preview of embedding entities.
   */
  public function testPreviewRoute() {
    // Ensure the default filter can be previewed by the anonymous user.
    $this->getRoute('plain_text');
    $this->assertResponse(200);
    $this->assertText(static::SUCCESS);

    // The anonymous user should not have permission to use embed_test format.
    $this->getRoute('embed_test');
    $this->assertResponse(403);

    // Now login a user that can use the embed_test format.
    $this->drupalLogin($this->webUser);

    $this->getRoute('plain_text');
    $this->assertResponse(200);
    $this->assertText(static::SUCCESS);

    $this->getRoute('embed_test');
    $this->assertResponse(200);
    $this->assertText(static::SUCCESS);

    // Test preview route with an empty request.
    $this->getRoute('embed_test', '');
    $this->assertResponse(404);

    // Test preview route with an invalid text format.
    $this->getRoute('invalid_format');
    $this->assertResponse(404);
  }

  /**
   * Performs a request to the embed.preview route.
   *
   * @param string $filter_format_id
   *   ID of the filter format.
   * @param string $value
   *   The query string value to include.
   *
   * @return string
   *   The retrieved HTML string.
   */
  public function getRoute($filter_format_id, $value = NULL) {
    $url = 'embed/preview/' . $filter_format_id;
    if (!isset($value)) {
      $value = static::SUCCESS;
    }
    return $this->drupalGet($url, ['query' => ['value' => $value]]);
  }

}
