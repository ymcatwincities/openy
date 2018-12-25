<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\ConfigTranslation\PageVariantConfigMapper.
 */

namespace Drupal\page_manager_ui\ConfigTranslation;

use Drupal\config_translation\ConfigEntityMapper;
use Symfony\Component\Routing\Route;

/**
 * Configuration mapper for page variants.
 *
 * @todo Remove once https://www.drupal.org/node/2670712 is in.
 */
class PageVariantConfigMapper extends ConfigEntityMapper {

  /**
   * {@inheritdoc}
   */
  protected function processRoute(Route $route) {
    parent::processRoute($route);
    // Change the paths for config translation routes to outside the wizard.
    $path = $route->getPath();
    $path = str_replace('manage/{machine_name}/{step}', '{page}/{page_variant}', $path);
    $route->setPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteParameters() {
    $parameters = parent::getBaseRouteParameters();
    $parameters['page'] = $this->entity->get('page');
    $parameters['machine_name'] = $parameters['page'];
    $parameters['step'] = 'page_variant__' . $parameters['page_variant'] . '__general';
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddRouteName() {
    return $this->alterRouteName(parent::getAddRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRouteName() {
    return $this->alterRouteName(parent::getEditRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRouteName() {
    return $this->alterRouteName(parent::getDeleteRouteName());
  }

  /**
   * Alter the route name to be unique from page entity route names.
   *
   * @param string $name
   *   Route name for the mapper.
   *
   * @return string
   *   Altered route name for the mapper.
   */
  protected function alterRouteName($name) {
    return str_replace('page', 'page_variant', $name);
  }

}
