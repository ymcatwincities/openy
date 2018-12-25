<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Provides a plugin definition.
 *
 * @ingroup Plugin
 */
abstract class PluginDefinition implements PluginDefinitionInterface {

  use MergeablePluginDefinitionTrait;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The class.
   *
   * @var string
   *   A fully qualified class name.
   */
  protected $class;

  /**
   * The plugin provider.
   *
   * @var string|null
   */
  protected $provider;

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    PluginDefinitionValidator::validateClass($class);

    $this->class = $class;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider($provider) {
    $this->provider = $provider;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

}
