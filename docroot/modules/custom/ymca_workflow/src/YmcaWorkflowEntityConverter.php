<?php

namespace Drupal\ymca_workflow;

use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\node\NodeInterface;

/**
 * Overrides EntityConverter to load latest revision on node edit form.
 */
class YmcaWorkflowEntityConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity = parent::convert($value, $definition, $name, $defaults);

    // Proceed only with nodes.
    if (!($entity instanceof NodeInterface)) {
      return $entity;
    }

    // Proceed only with associated nodes.
    if (!$entity->hasField('field_state')) {
      return $entity;
    }

    if ($defaults['_route'] == 'entity.node.edit_form') {
      // Always load the latest revision.
      $node_storage = \Drupal::entityManager()->getStorage('node');
      $vids = $node_storage->revisionIds($entity);
      $latest_revision_vid = array_pop($vids);
      if ($entity->vid->value != $latest_revision_vid) {
        $entity = node_revision_load($latest_revision_vid);
        $request = \Drupal::request();
        $request_route = $request->attributes->get('_route');
        // Code got triggered for building Tabs, so we need to make sure
        // do not display message on a view page.
        if (
          $request->getMethod() == 'GET'
          && (
            empty($request_route)
            || (!empty($request_route) && $request_route == 'entity.node.edit_form')
          )) {
          drupal_set_message('Latest version of the content was loaded.');
        }
      }
    }

    return $entity;
  }

}
