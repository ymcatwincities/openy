<?php

/**
 * @file
 * Contains \Drupal\search_api\Processor\ProcessorInterface.
 */

namespace Drupal\search_api\Processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\IndexPluginInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Represents a Search API pre- and/or post-processor.
 *
 * While processors are enabled or disabled for both pre- and postprocessing at
 * once, many processors will only need to run in one of those two phases. Then,
 * the other method(s) should simply be left blank. A processor should make it
 * clear in its description or documentation when it will run and what effect it
 * will have.
 *
 * @see \Drupal\search_api\Annotation\SearchApiProcessor
 * @see \Drupal\search_api\Processor\ProcessorPluginManager
 * @see \Drupal\search_api\Processor\ProcessorPluginBase
 * @see plugin_api
 */
interface ProcessorInterface extends IndexPluginInterface {

  /**
   * Processing stage: preprocess index.
   */
  const STAGE_PREPROCESS_INDEX = 'preprocess_index';

  /**
   * Processing stage: preprocess query.
   */
  const STAGE_PREPROCESS_QUERY = 'preprocess_query';

  /**
   * Processing stage: postprocess query.
   */
  const STAGE_POSTPROCESS_QUERY = 'postprocess_query';

  /**
   * Checks whether this processor is applicable for a certain index.
   *
   * This can be used for hiding the processor on the index's "Filters" tab. To
   * avoid confusion, you should only use criteria that are more or less
   * constant, such as the index's datasources. Also, since this is only used
   * for UI purposes, you should not completely rely on this to ensure certain
   * index configurations and at least throw an exception with a descriptive
   * error message if this is violated on runtime.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to check for.
   *
   * @return bool
   *   TRUE if the processor can run on the given index; FALSE otherwise.
   */
  public static function supportsIndex(IndexInterface $index);

  /**
   * Checks whether this processor implements a particular stage.
   *
   * @param string $stage_identifier
   *   The stage to check: self::STAGE_PREPROCESS_INDEX,
   *   self::STAGE_PREPROCESS_QUERY
   *   or self::STAGE_POSTPROCESS_QUERY.
   *
   * @return bool
   *   TRUE if the processor runs on a particular stage; FALSE otherwise.
   */
  public function supportsStage($stage_identifier);

  /**
   * Returns the default weight for a specific processing stage.
   *
   * Some processors should ensure they run earlier or later in a particular
   * stage. Processors with lower weights are run earlier. The default value is
   * used when the processor is first enabled. It can then be changed through
   * reordering by the user.
   *
   * @param string $stage
   *   The stage whose default weight should be returned. See
   *   \Drupal\search_api\Processor\ProcessorPluginManager::getProcessingStages()
   *   for the valid values.
   *
   * @return int
   *   The default weight for the given stage.
   */
  public function getDefaultWeight($stage);

  /**
   * Determines whether this processor should always be enabled.
   *
   * @return bool
   *   TRUE if this processor should be forced enabled; FALSE otherwise.
   */
  public function isLocked();

  /**
   * Determines whether this processor should be hidden from the user.
   *
   * @return bool
   *   TRUE if this processor should be hidden from the user; FALSE otherwise.
   */
  public function isHidden();

  /**
   * Alters the given datasource's property definitions.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   An array of property definitions for this datasource.
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   (optional) The datasource this set of properties belongs to. If NULL, the
   *   datasource-independent properties should be added (or modified).
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL);

  /**
   * Preprocesses search items for indexing.
   *
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array of items to be preprocessed for indexing, passed by reference.
   */
  public function preprocessIndexItems(array &$items);

  /**
   * Preprocesses a search query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The object representing the query to be executed.
   */
  public function preprocessSearchQuery(QueryInterface $query);

  /**
   * Postprocess search results before they are returned by the query.
   *
   * If a processor is used for both pre- and post-processing a search query,
   * the same object will be used for both calls (so preserving some data or
   * state locally is possible).
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The search results.
   */
  public function postprocessSearchResults(ResultSetInterface $results);

}
