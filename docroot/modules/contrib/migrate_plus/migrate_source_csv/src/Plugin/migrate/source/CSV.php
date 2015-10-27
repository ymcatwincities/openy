<?php
/**
 * @file
 * Contains \Drupal\migrate_source_csv\Plugin\migrate\source\CSV.
 */

namespace Drupal\migrate_source_csv\Plugin\migrate\source;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate_source_csv\CSVFileObject;

/**
 * Source for CSV.
 *
 * If the CSV file contains non-ASCII characters, make sure it includes a
 * UTF BOM (Byte Order Marker) so they are interpreted correctly.
 *
 * @MigrateSource(
 *   id = "csv"
 * )
 */
class CSV extends SourcePluginBase {

  /**
   * List of available source fields.
   *
   * Keys are the field machine names as used in field mappings, values are
   * descriptions.
   *
   * @var array
   */
  protected $fields = array();

  /**
   * The source ids, as indexes.
   *
   * @var array
   */
  protected $identifiers = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    // Path is required.
    if (empty($this->configuration['path'])) {
      throw new MigrateException('You must declare the "path" to the source CSV file in your source settings.');
    }

    // Identifier field(s) are required.
    if (empty($this->configuration['identifiers'])) {
      throw new MigrateException('You must declare "identifiers" as a unique array of fields in your source settings.');
    }

  }

  /**
   * Return a string representing the source query.
   *
   * @return string
   *   The file path.
   */
  public function __toString() {
    return $this->configuration['path'];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    // File handler using header-rows-respecting extension of SPLFileObject.
    $file = new CSVFileObject($this->configuration['path']);

    // Set basics of CSV behavior based on configuration.
    $delimiter = !empty($this->configuration['delimiter']) ? $this->configuration['delimiter'] : ',';
    $enclosure = !empty($this->configuration['enclosure']) ? $this->configuration['enclosure'] : '"';
    $escape = !empty($this->configuration['escape']) ? $this->configuration['escape'] : '\\';
    $file->setCsvControl($delimiter, $enclosure, $escape);

    // Figure out what CSV column(s) to use.
    if (!empty($this->configuration['header_row_count'])) {
      $file->setHeaderRowCount($this->configuration['header_row_count']);

      // Find the last header line.
      $file->rewind();
      $file->seek($file->getHeaderRowCount() - 1);

      // Use the header row(s).
      if (empty($this->configuration['column_names'])) {
        $row = $file->current();
        foreach ($row as $header) {
          $header = trim($header);
          $column_names[] = array($header => $header);
        }
        $file->setColumnNames($column_names);
      }
    }
    // An explicit list of column name(s) is provided.
    if (!empty($this->configuration['column_names'])) {
      $file->setColumnNames($this->configuration['column_names']);
    }

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = array();
    foreach ($this->configuration['identifiers'] as $key) {
      $ids[$key]['type'] = 'string';
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array();
    foreach ($this->getIterator()->getColumnNames() as $column) {
      $key = key($column);
      $fields[$key] = $key;
    }

    // Any caller-specified fields with the same names as extracted fields will
    // override them; any others will be added.
    if (!empty($this->configuration['fields'])) {
      $fields = $this->configuration['fields'] + $fields;
    }

    return $fields;
  }

}
