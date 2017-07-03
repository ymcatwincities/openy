<?php

namespace Drupal\panels;

use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Display\VariantManager;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * A service that manages Panels displays.
 */
class PanelsDisplayManager implements PanelsDisplayManagerInterface {

  use SchemaCheckTrait {
    checkConfigSchema as private;
  }

  /**
   * @var \Drupal\Core\Display\VariantManager
   */
  protected $variantManager;

  /**
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * @param \Drupal\Core\Display\VariantManager $variant_manager
   */
  public function __construct(VariantManager $variant_manager, TypedConfigManagerInterface $typed_config_manager) {
    $this->variantManager = $variant_manager;
    $this->typedConfigManager = $typed_config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createDisplay($layout = NULL, $builder = NULL) {
    $display = $this->variantManager->createInstance('panels_variant', []);

    // Set the default builder and layout.
    // @todo: load the defaults from config somewhere.
    $display->setLayout($layout ?: 'layout_onecol');
    $display->setBuilder($builder ?: 'standard');

    return $display;
  }

  /**
   * Validates the config against the schema.
   *
   * @param array $config
   *   The configuration data.
   *
   * @throws \Exception
   *   If the configuration doesn't validate.
   */
  protected function validate(array $config) {
    $this->configName = 'display_variant.plugin.panels_variant';
    $definition = $this->typedConfigManager->getDefinition($this->configName);
    $data_definition = $this->typedConfigManager->buildDataDefinition($definition, $config);
    $this->schema = $this->typedConfigManager->create($data_definition, $config);
    $errors = array();
    foreach ($config as $key => $value) {
      $errors = array_merge($errors, $this->checkValue($key, $value));
    }
    if (!empty($errors)) {
      $error_list = [];
      foreach ($errors as $key => $error) {
        $error_list[] = $key . ': ' . $error;
      }
      throw new \Exception("Config for Panels display doesn't validate: " . implode(', ', $error_list));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function importDisplay(array $config, $validate = TRUE) {
    // Validate against the schema if requested.
    if ($validate) {
      $this->validate($config);
    }

    return $this->variantManager->createInstance('panels_variant', $config);
  }

  /**
   * {@inheritdoc}
   */
  public function exportDisplay(PanelsDisplayVariant $display) {
    return $display->getConfiguration();
  }

}
