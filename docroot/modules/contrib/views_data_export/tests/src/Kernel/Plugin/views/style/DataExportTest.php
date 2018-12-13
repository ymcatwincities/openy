<?php

namespace Drupal\Tests\views_data_export\Kernel\Plugin\views\style;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the data export style serializer.
 *
 * @coversDefaultClass \Drupal\views_data_export\Plugin\views\style\DataExport
 *
 * @group views_data_export
 */
class DataExportTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_data_export'];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views_data_export',
    'csv_serialization',
    'entity_test',
    'serialization',
    'rest',
    'views_data_export_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(get_class($this), ['views_data_export_test']);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('test_data_export');
    $display = &$view->getDisplay('data_export_1');

    $display['display_options']['defaults']['style'] = FALSE;
    $display['display_options']['style']['type'] = 'data_export';
    $display['display_options']['style']['options']['formats'] = ['csv' => 'csv'];
    // Ensure these schemas are properly defined.
    $display['display_options']['style']['options']['csv_settings']['delimiter'] = '\\t';
    $display['display_options']['style']['options']['csv_settings']['enclosure'] = '';
    $display['display_options']['style']['options']['csv_settings']['escape_char'] = '';
    $display['display_options']['style']['options']['csv_settings']['strip_tags'] = TRUE;
    $display['display_options']['style']['options']['csv_settings']['trim'] = TRUE;
    $display['display_options']['style']['options']['csv_settings']['encoding'] = 'utf8';
    $display['display_options']['style']['options']['xls_settings']['xls_format'] = 'Excel5';
    $display['display_options']['style']['options']['xls_settings']['metadata'] = [
      'creator' => 'J Author',
      'last_modified_by' => 'That one guy, down the hall?',
      'title' => 'A fantastic title. The best title.',
      'description' => 'Such a great description. Everybody is saying.',
      'subject' => 'This spreadsheet is about numbers.',
      'keywords' => 'testing xls spreadsheets',
      'category' => 'test category',
      'manager' => 'J Q Manager',
      'company' => 'Drupal',
    ];
    $view->save();

    $view->calculateDependencies();
    $this->assertEquals(['module' => ['csv_serialization', 'entity_test', 'rest', 'serialization', 'user', 'views_data_export']], $view->getDependencies());
  }

}
