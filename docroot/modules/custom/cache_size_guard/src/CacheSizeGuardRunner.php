<?php

namespace Drupal\cache_size_guard;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\dbsize\DbSizeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class CacheSizeGuardRunner.
 */
class CacheSizeGuardRunner implements CacheSizeGuardRunnerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The settings.
   *
   * @var ImmutableConfig
   */
  protected $settings;

  /**
   * Logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * DbSize manager.
   *
   * @var DbSizeManagerInterface
   */
  protected $dbSize;

  /**
   * CacheSizeGuardRunner constructor.
   *
   * @param ImmutableConfig $settings
   *   The settings.
   * @param LoggerChannelInterface $logger
   *   Logger channel.
   * @param DbSizeManagerInterface $dbsize
   *   Dbsize manager.
   */
  public function __construct(ImmutableConfig $settings, LoggerChannelInterface $logger, DbSizeManagerInterface $dbsize) {
    $this->settings = $settings;
    $this->logger = $logger;
    $this->dbSize = $dbsize;
  }

  /**
   * {@inheritdoc}
   */
  public function run($guard = 'all') {
    $available = $this->settings->get('guards');
    $guards = $available;

    if ($guard != 'all') {
      if (!isset($available[$guard])) {
        $this->logger->error('Cache guard %guard was not found.', ['%guard']);
        return;
      }

      $guards[$guard] = $available[$guard];
    }

    // Loop over each guard and invoke it if needed.
    foreach ($guards as $name => $options) {

      // Check if actual size is bigger than threshold.
      $size = $this->dbSize->getEntitySize($options['entity_type_id']);
      // Note, threshold in megabits, but size in bites.
      if ($size > ($options['threshold'] * 1024 * 1024)) {

        $msg = 'The actual entity %entity size %size is bigger than threshold %threshold. Guard %guard will be invoked.';
        $this->logger->info(
          $msg,
          [
            '%entity' => $options['entity_type_id'],
            '%size' => $this->format($size),
            '%threshold' => $options['threshold'] . 'M',
            '%guard' => $name,
          ]
        );

        try {
          // Run soft cleaner.
          $cleaner = $this->container->get($options['cleaners']['soft']['service']);
          $cleaner->$options['cleaners']['soft']['method']($options['cleaners']['soft']['arguments']);

          $msg = 'Soft cleaner for guard %guard has been invoked successfully. Old entity %entity size: %old.';
          $this->logger->info(
            $msg,
            [
              '%guard' => $name,
              '%entity' => $options['entity_type_id'],
              '%old' => $this->format($size),
            ]
          );
        }
        catch (\Exception $e) {
          $msg = 'Failed to invoke soft cleaner for entity %entity with guard %guard with message %msg.';
          $this->logger->error(
            $msg,
            [
              '%entity' => $options['entity_type_id'],
              '%guard' => $name,
              '%msg' => $e->getMessage(),
            ]
          );
        }

        // @todo Check the db size again and run (if needed) hard cleaner 3 times.
        // @todo Check the db size again and run (if needed) cruel cleaner.
      }
      else {
        // The size is smaller than threshold. Just log it.
        $msg = 'The size %size of entity %entity is smaller than threshold %threshold for guard %guard. Enjoy your day!.';
        $this->logger->info(
          $msg,
          [
            '%size' => $this->format($size),
            '%entity' => $options['entity_type_id'],
            '%threshold' => $options['threshold'] . 'M',
            '%guard' => $name,
          ]
        );
      }
    }

  }

  /**
   * Format size.
   *
   * @param int $size
   *   Size in bytes.
   *
   * @return string
   *   The size in M.
   */
  private function format($size) {
    return round($size / 1024 / 1024, 2) . 'M';
  }

}
