<?php

namespace Drupal\Tests\search_api_solr\Unit;

use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Drupal\search_api_solr\SolrConnectorInterface;
use Drupal\Tests\search_api_solr\Traits\InvokeMethodTrait;
use Drupal\Tests\UnitTestCase;
use Solarium\Core\Query\Helper;
use Solarium\QueryType\Update\Query\Document\Document;

// @see datetime.module
define('DATETIME_STORAGE_TIMEZONE', 'UTC');

/**
 * Tests functionality of the backend.
 *
 * @coversDefaultClass \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend
 *
 * @group search_api_solr
 */
class SearchApiBackendUnitTest extends UnitTestCase  {

  use InvokeMethodTrait;

  /**
   * @covers       ::addIndexField
   *
   * @dataProvider addIndexFieldDataProvider
   *
   * @param mixed $input
   *   Field value.
   *
   * @param string $type
   *   Field type.
   *
   * @param mixed $expected
   *   Expected result.
   */
  public function testIndexField($input, $type, $expected) {
    $connector = $this->prophesize(SolrConnectorInterface::class);
    $connector->getQueryHelper()->willReturn(new Helper());

    $field = 'testField';
    $document = $this->prophesize(Document::class);
    $document
      ->addField($field, $expected)
      ->shouldBeCalled();

    $backend = $this->prophesize(SearchApiSolrBackend::class);
    $backend->getSolrConnector()->willReturn($connector->reveal());

    $args = [
      $document->reveal(),
      $field,
      [$input],
      $type
    ];

    $backend_instance = $backend->reveal();

    // addIndexField() should convert the $input according to $type and call
    // Document::addField() with the correctly converted $input.
    $this->invokeMethod($backend_instance, 'addIndexField', $args);
  }

  /**
   * Data provider for testIndexField method. Set of values can be extended to
   * check other field types and values.
   *
   * @return array
   */
  public function addIndexFieldDataProvider() {
    return [
      ['0', 'boolean', 'false'],
      ['1', 'boolean', 'true'],
      [0, 'boolean', 'false'],
      [1, 'boolean', 'true'],
      [FALSE, 'boolean', 'false'],
      [TRUE, 'boolean', 'true'],
      ['2016-05-25T14:00:00+10', 'date', '2016-05-25T04:00:00Z'],
      ['1465819200', 'date', '2016-06-13T12:00:00Z'],
    ];
  }

}
