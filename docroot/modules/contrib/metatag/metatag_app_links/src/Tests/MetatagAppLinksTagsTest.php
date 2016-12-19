<?php

namespace Drupal\metatag_app_links\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the App Links tags work correctly.
 *
 * @group metatag
 */
class MetatagAppLinksTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [];

  /**
   * {@inheritdoc}
   */
  public $test_tag = 'link';

  /**
   * {@inheritdoc}
   */
  public $test_name_attribute = 'rel';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_app_links';
    parent::setUp();
  }

}
