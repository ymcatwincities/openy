<?php

namespace Drupal\ymca_cdn_sync\syncer;

/**
 * Class Cleaner.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
class Cleaner implements CleanerInterface {

  /**
   * {@inheritdoc}
   */
  public function clean() {
    $controller = \Drupal::entityManager()->getStorage('cdn_prs_product');
    $entities = $controller->loadMultiple();
    $controller->delete($entities);
  }

}
