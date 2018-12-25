<?php

/**
 * @file
 * Contains Drupal\page_manager\PageVariantInterface.
 */

namespace Drupal\page_manager;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface defining a PageVariant entity.
 */
interface PageVariantInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the variant plugin.
   *
   * @return \Drupal\Core\Display\VariantInterface
   */
  public function getVariantPlugin();

  /**
   * Gets the plugin ID of the variant plugin.
   *
   * @return string
   */
  public function getVariantPluginId();

  /**
   * Sets the plugin ID of the variant plugin without loading the Plugin
   *   collections.
   *
   * @param string $variant
   *   The plugin ID of the variant plugin.
   *
   * @return $this
   *
   * @see \Drupal\page_manager\Entity\PageVariant::getPluginCollections()
   */
  public function setVariantPluginId($variant);

  /**
   * Gets the page this variant is on.
   *
   * @return \Drupal\page_manager\PageInterface
   */
  public function getPage();

  /**
   * Sets the page with a full entity object.
   *
   * This is mainly useful for setting an unsaved page on a page variant so you
   * can continue to work with it prior to saving.
   *
   * @param \Drupal\page_manager\PageInterface $page
   *   The page entity object this variant is associated with.
   *
   * @return $this
   */
  public function setPageEntity(PageInterface $page);

  /**
   * Gets the values for all defined contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   An array of set context values, keyed by context name.
   */
  public function getContexts();

  /**
   * Resets the collected contexts.
   *
   * @return $this
   */
  public function resetCollectedContexts();

  /**
   * Gets the weight of this variant (compared to other variants on the page).
   *
   * @return int
   */
  public function getWeight();

  /**
   * Sets the weight of this variant (compared to other variants on the page).
   *
   * @param int $weight
   *   The weight of the variant.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the selection condition collection.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   */
  public function getSelectionConditions();

  /**
   * Adds selection criteria.
   *
   * @param array $configuration
   *   Configuration of the selection criteria.
   *
   * @return string
   *   The condition ID of the new criteria.
   */
  public function addSelectionCondition(array $configuration);

  /**
   * Gets selection criteria by condition id.
   *
   * @param string $condition_id
   *   The ID of the condition.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   */
  public function getSelectionCondition($condition_id);

  /**
   * Removes selection criteria by condition id.
   *
   * @param string $condition_id
   *   The ID of the condition.
   *
   * @return $this
   */
  public function removeSelectionCondition($condition_id);

  /**
   * Gets the selection logic used by the criteria (ie. "and" or "or").
   *
   * @return string
   *   Either "and" or "or"; represents how the selection criteria are combined.
   */
  public function getSelectionLogic();

  /**
   * Returns the static context configurations for this page entity.
   *
   * @return array[]
   *   An array of static context configurations.
   */
  public function getStaticContexts();

  /**
   * Retrieves a specific static context.
   *
   * @param string $name
   *   The static context unique name.
   *
   * @return array
   *   The configuration array of the static context
   */
  public function getStaticContext($name);

  /**
   * Adds/updates a given static context.
   *
   * @param string $name
   *   The static context unique machine name.
   * @param array $configuration
   *   A new array of configuration for the static context.
   *
   * @return $this
   */
  public function setStaticContext($name, $configuration);

  /**
   * Removes a specific static context.
   *
   * @param string $name
   *   The static context unique name.
   *
   * @return $this
   */
  public function removeStaticContext($name);

}
