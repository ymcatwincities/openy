<?php

namespace Drupal\metatag_favicons\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Favicons tags work correctly.
 *
 * @group metatag
 */
class MetatagFaviconsTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
  ];

  /**
   * The tag to look for when testing the output.
   */
  public $test_tag = 'meta';

  /**
   * The attribute to look for to indicate which tag.
   */
  public $test_name_attribute = 'property';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_favicons';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function get_test_tag_name($tag_name) {
    // Replace the first underline with a colon.
    $tag_name = str_replace('og_', 'og:', $tag_name);
    $tag_name = str_replace('article_', 'article:', $tag_name);

    // Some tags have an additional underline that turns into a colon.
    $tag_name = str_replace('og:image_', 'og:image:', $tag_name);
    $tag_name = str_replace('og:video_', 'og:video:', $tag_name);

    // Additional fixes.
    if ($tag_name == 'og:locale_alternative') {
      $tag_name = 'og:locale:alternate';
    }

    return $tag_name;
  }

}
