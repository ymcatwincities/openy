<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformHelpManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform help.
 */
class WebformHelpController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform help manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $helpManager;

  /**
   * Constructs a WebformHelpController object.
   *
   * @param \Drupal\webform\WebformHelpManagerInterface $help_manager
   *   The webform help manager.
   */
  public function __construct(WebformHelpManagerInterface $help_manager) {
    $this->helpManager = $help_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.help_manager')
    );
  }

  /**
   * Returns dedicated help about (aka How can we help you?) page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A renderable array containing a help about (aka How can we help you?) page.
   */
  public function about(Request $request) {
    $build = $this->helpManager->buildAbout();
    unset($build['title']);
    $build += [
      '#prefix' => '<div class="webform-help">',
      '#suffix' => '</div>',
    ];
    $build['#attached']['library'][] = 'webform/webform.help';
    return $build;
  }

}
