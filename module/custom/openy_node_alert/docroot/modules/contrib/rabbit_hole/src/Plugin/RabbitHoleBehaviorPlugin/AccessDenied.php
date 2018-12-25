<?php

namespace Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Entity\Entity;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;

/**
 * Denies access to a page.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "access_denied",
 *   label = @Translation("Access denied")
 * )
 */
class AccessDenied extends RabbitHoleBehaviorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function performAction(Entity $entity, Response $current_response = NULL) {
    throw new AccessDeniedHttpException();
  }

}
