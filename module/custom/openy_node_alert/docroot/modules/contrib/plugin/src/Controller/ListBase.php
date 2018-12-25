<?php

namespace Drupal\plugin\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the listing routes.
 */
abstract class ListBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('module_handler'));
  }

  /**
   * Gets the human-readable provider label.
   *
   * @param $provider string
   *
   * @return string
   */
  protected function getProviderLabel($provider) {
    if ($provider == 'core') {
      return $this->t('Core');
    }
    else {
      return $this->moduleHandler->getName($provider);
    }
  }

}
