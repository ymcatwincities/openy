<?php

namespace Drupal\openy_google_search\Plugin\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Google Search Block' block.
 *
 * @Block(
 *   id = "google_search_block",
 *   admin_label = @Translation("Google Search Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class GoogleSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {
  use MessengerTrait;
  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Programs Search Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $engine_id = $this->configFactory->get('openy_google_search.settings')->get('google_engine_id');
    if (empty($engine_id)) {
      $this->messenger()->addError('Please set ENGINE_ID in the search settings.');
      return NULL;
    }

    return [
      '#theme' => 'openy_google_search',
      '#cache' => [
        'tags' => ['config:openy_google_search.settings'],
      ],
      '#attached' => [
        'library' => ['openy_google_search/google_search'],
        'drupalSettings' => [
          'engine_id' => $engine_id,
        ],
      ],
    ];
  }

}
