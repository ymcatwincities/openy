<?php

namespace Drupal\openy_style_guide\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\Core\Url;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "style_guide_rest_resource",
 *   label = @Translation("Open Y Style Guide resource"),
 *   uri_paths = {
 *     "canonical" = "/styleguide"
 *   }
 * )
 */
class StyleGuideRestResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get() {
    $query = \Drupal::service('entity.query')
      ->get('menu_link_content')
      ->condition('menu_name', 'style-guide');
    $entity_ids = $query->execute();

    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storageManager */
    $storageManager = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    $items = [];
    $storage = \Drupal::entityTypeManager()->getStorage('domain');
    $domains = $storage->loadMultiple();

    /** @var  $domain */
    foreach ($domains as $domain) {
      $host = $domain->getPath();
      $site_name = $domain->get('name');
      foreach ($entity_ids as $id) {
        $entity = $storageManager->load($id);
        $value = $entity->get('link')->getValue();
        $url = $value[0]['uri'] != NULL ? Url::fromUri($value[0]['uri'])
          ->toString() : NULL;
        $items['domain'][$site_name][] = [
          'title' => $entity->get('title')->value,
          'link' => $host . $url,
        ];
      }
    }
    return new ModifiedResourceResponse($items);
  }
}
