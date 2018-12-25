<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\ConfigTranslation\PageConfigMapper.
 */

namespace Drupal\page_manager_ui\ConfigTranslation;

use Drupal\config_translation\ConfigEntityMapper;
use Symfony\Component\Routing\Route;


/**
 * Configuration mapper for page_manager pages.
 */
class PageConfigMapper extends ConfigEntityMapper {

  /**
   * {@inheritdoc}
   */
  protected function processRoute(Route $route) {
    parent::processRoute($route);
    // Change the paths for config translation routes to outside the wizard.
    $path = $route->getPath();
    $path = str_replace('manage/{machine_name}/{step}', '{page}', $path);
    $route->setPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteParameters() {
    $parameters = parent::getBaseRouteParameters();
    $parameters['step'] = 'general';
    $parameters['machine_name'] = $parameters['page'];
    return $parameters;
  }

}
