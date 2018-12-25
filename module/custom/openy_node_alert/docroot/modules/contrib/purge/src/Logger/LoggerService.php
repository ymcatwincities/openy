<?php

namespace Drupal\purge\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DestructableInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\purge\Logger\LoggerChannelPartFactoryInterface;
use Drupal\purge\Logger\LoggerServiceInterface;
use Drupal\purge\Logger\LoggerChannelPart;

/**
 * Provides logging services to purge and its submodules, via a single channel.
 */
class LoggerService extends ServiceProviderBase implements LoggerServiceInterface, DestructableInterface {

  /**
   * The name of the configuration object.
   *
   * @var string
   */
  const CONFIG = 'purge.logger_channels';

  /**
   * The name of the used sequence key in the configuration object.
   *
   * @var string
   */
  const CKEY = 'channels';

  /**
   * Initialized channel part instances.
   *
   * @var \Drupal\purge\Logger\LoggerChannelPartInterface[]
   */
  protected $channels = [];

  /**
   * Raw configuration payload as stored in CMI.
   *
   * @var array[]
   */
  protected $config = [];

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Available RFC 5424 log types.
   *
   * @var int[]
   */
  protected $grants = [
    RfcLogLevel::EMERGENCY,
    RfcLogLevel::ALERT,
    RfcLogLevel::CRITICAL,
    RfcLogLevel::ERROR,
    RfcLogLevel::WARNING,
    RfcLogLevel::NOTICE,
    RfcLogLevel::INFO,
    RfcLogLevel::DEBUG,
  ];

  /**
   * The channel part factory.
   *
   * @var \Drupal\purge\Logger\LoggerChannelPartFactoryInterface
   */
  protected $purgeLoggerPartsFactory;

  /**
   * Whether configuration needs to get written to CMI at object destruction.
   *
   * @var false|true
   */
  protected $write_config = FALSE;

  /**
   * Construct \Drupal\purge\Logger\LoggerService.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Logger\LoggerChannelPartFactoryInterface $purge_logger_parts_factory
   *   The channel part factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelPartFactoryInterface $purge_logger_parts_factory) {
    $this->configFactory = $config_factory;
    $this->purgeLoggerPartsFactory = $purge_logger_parts_factory;

    // Set configuration when CMI has it.
    if (is_array($c = $config_factory->get(SELF::CONFIG)->get(SELF::CKEY))) {
      $this->config = $c;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    if ($this->write_config) {
      $this->configFactory
        ->getEditable(SELF::CONFIG)
        ->set(SELF::CKEY, $this->config)
        ->save();
      $this->write_config = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteChannel($id) {
    foreach ($this->config as $i => $channel) {
      if ($channel['id'] === $id) {
        unset($this->config[$i]);
        $this->write_config = TRUE;
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteChannels($id_starts_with) {
    foreach ($this->config as $i => $channel) {
      if (strpos($channel['id'], $id_starts_with) === 0) {
        unset($this->config[$i]);
        if (!$this->write_config) {
          $this->write_config = TRUE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    if (!$this->hasChannel($id)) {
      $this->setChannel($id, [
        RfcLogLevel::EMERGENCY,
        RfcLogLevel::CRITICAL,
        RfcLogLevel::ERROR,
      ]);
    }
    if (!isset($this->channels[$id])) {
      $grants = [];
      foreach ($this->config as $channel) {
        if ($channel['id'] === $id) {
          $grants = $channel['grants'];
        }
      }
      $this->channels[$id] = $this->purgeLoggerPartsFactory->create($id, $grants);
    }
    return $this->channels[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function getChannels() {
    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChannel($id) {
    foreach ($this->config as $channel) {
      if ($channel['id'] === $id) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setChannel($id, array $grants = []) {

    // Perform input validation.
    if (empty($id) || !is_string($id)) {
      throw new \LogicException('The given ID is empty or not a string!');
    }
    foreach ($grants as $grant) {
      if (!in_array($grant, $this->grants)) {
        throw new \LogicException("Passed grant is invalid!");
      }
    }

    // Determine the config index that we'll write to (existing or new).
    $i = end($this->config) ? key($this->config) + 1 : 0;
    foreach ($this->config as $index => $channel) {
      if ($channel['id'] === $id) {
        $i = $index;
        break;
      }
    }

    // (Over)write the channel and its grants.
    $this->config[$i] = ['id' => $id, 'grants' => $grants];
    $this->write_config = TRUE;
  }

}
