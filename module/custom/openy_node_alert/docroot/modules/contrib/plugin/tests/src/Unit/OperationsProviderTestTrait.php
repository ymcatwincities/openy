<?php

namespace Drupal\Tests\plugin\Unit;
use Drupal\Core\Url;

/**
 * Provides assertions to test operations links integrity.
 */
trait OperationsProviderTestTrait {

  /**
   * Checks the integrity of operations links.
   *
   * @param mixed[] $operations_links
   */
  protected function assertOperationsLinks(array $operations_links) {
    foreach ($operations_links as $link) {
      \PHPUnit_Framework_Assert::assertArrayHasKey('title', $link);
      \PHPUnit_Framework_Assert::assertNotEmpty($link['title']);

      \PHPUnit_Framework_Assert::assertArrayHasKey('url', $link);
      \PHPUnit_Framework_Assert::assertInstanceOf(Url::class, $link['url']);
    }
  }

}
