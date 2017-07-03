<?php

namespace Drupal\plugin\PluginType;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides default operations for plugin types.
 */
class DefaultPluginTypeOperationsProvider implements PluginTypeOperationsProviderInterface, ContainerInjectionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'));
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_type_id) {
    $operations['list'] = [
      'title' => $this->t('View'),
      'url' => new Url('plugin.plugin.list', [
        'plugin_type' => $plugin_type_id,
      ]),
    ];

    return $operations;
  }

}
