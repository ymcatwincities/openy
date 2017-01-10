<?php

namespace Drupal\contact_storage;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for contact messages and contact forms.
 */
class ContactRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);

    if ($entity_type->hasLinkTemplate('collection')) {
      $route = (new Route($entity_type->getLinkTemplate('collection')))
        ->addDefaults([
          '_entity_list' => 'contact_message',
          '_title' => 'Contact messages',
        ])
        ->addRequirements([
          '_permission' => 'administer contact forms',
        ]);
      $route_collection->add('entity.' . $entity_type->id() . '.collection', $route);
    }

    if ($entity_type->hasLinkTemplate('clone-form')) {
      $route = (new Route($entity_type->getLinkTemplate('clone-form')))
        ->addDefaults([
          '_entity_form' => 'contact_form.clone',
          '_title' => 'Clone form',
        ])
        ->addRequirements([
          '_entity_access' => 'contact_form.clone',
        ]);
      $route_collection->add('entity.' . $entity_type->id() . '.clone_form', $route);
    }

    return $route_collection;
  }

}
