<?php

namespace Drupal\config_import;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\features\Package;
use Drupal\features\ConfigurationItem;
use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\config_update\ConfigRevertInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigFeaturesImporterService.
 */
class ConfigFeaturesImporterService implements ConfigFeaturesImporterServiceInterface {

  /**
   * Config revert service.
   *
   * @var ConfigRevertInterface
   */
  protected $configRevert;
  /**
   * Features manager service.
   *
   * @var FeaturesManagerInterface
   */
  protected $featuresManager;
  /**
   * Features assigner service.
   *
   * @var FeaturesAssignerInterface
   */
  protected $featuresAssigner;
  /**
   * Logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * ConfigFeaturesImporterService constructor.
   *
   * @param ConfigRevertInterface $config_revert
   *   Config revert service.
   * @param FeaturesManagerInterface $features_manager
   *   Features manager service.
   * @param FeaturesAssignerInterface $features_assigner
   *   Features assigner service.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   */
  public function __construct(
    ConfigRevertInterface $config_revert,
    FeaturesManagerInterface $features_manager,
    FeaturesAssignerInterface $features_assigner,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configRevert = $config_revert;
    $this->featuresManager = $features_manager;
    $this->featuresAssigner = $features_assigner;
    $this->loggerChannel = $logger_factory->get('config_update');
  }

  /**
   * {@inheritdoc}
   *
   * @see ConfigImportServiceProvider::registerFeaturesImporter()
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_update.config_update'),
      $container->get('features.manager'),
      $container->get('features_assigner'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function importFeatures(array $features) {
    $packages = $this->featuresManager->getPackages();

    foreach ($features as $feature) {
      if (empty($packages[$feature])) {
        $error = 'Feature "@feature" does not exist.';
      }
      elseif ($packages[$feature]->getStatus() === FeaturesManagerInterface::STATUS_NO_EXPORT) {
        $error = 'Feature "@feature" marked as non exportable.';
      }

      if (isset($error)) {
        $this->loggerChannel->error($error, [
          '@feature' => $feature,
        ]);
      }
      else {
        $this->import($packages[$feature]);
      }
    }
  }

  /**
   * Import/revert a package.
   *
   * @param Package $package
   *   Package definition.
   */
  protected function import(Package $package) {
    $missing = $this->featuresManager->reorderMissing($this->featuresManager->detectMissing($package));
    $overrides = $this->featuresManager->detectOverrides($package, TRUE);

    $this->featuresAssigner->assignConfigPackages();

    $configs = $this->featuresManager->getConfigCollection();

    foreach ($missing + $overrides as $config_name) {
      if (isset($configs[$config_name])) {
        $item = $configs[$config_name];
        $message = 'Failed to revert the "@config_name" configuration.';

        if ($this->configRevert->revert(ConfigurationItem::fromConfigStringToConfigType($item->getType()), $item->getShortName())) {
          $message = 'The "@config_name" configuration have been reverted.';
        }
      }
      else {
        $item = $this->featuresManager->getConfigType($config_name);
        $message = 'Failed to import the "@config_name" configuration.';

        if ($this->configRevert->import(ConfigurationItem::fromConfigStringToConfigType($item['type']), $item['name_short'])) {
          $message = 'The "@config_name" configuration have been imported.';
        }
      }

      $this->loggerChannel->info($message, ['@name' => $config_name]);
    }
  }

}
