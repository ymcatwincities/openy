<?php

/**
 * @file
 * Contains \Drupal\ymca_page_context\PageContextService.
 */

namespace Drupal\ymca_page_context;

/**
 * Controls in what context page header should be rendered.
 */
class PageContextService {
  private $context;

  /**
   * Constructs a new PageContextService.
   */
  public function __construct() {
    $this->context = NULL;
  }

  /**
   * Overrides current context.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Context node entity.
   */
  public function setContext(\Drupal\node\Entity\Node $node) {
    $this->context = $node;
  }

  /**
   * Returns current context.
   *
   * @return mixed
   *   An instance of \Drupal\node\Entity\Node or null.
   */
  public function getContext() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if (in_array($node->bundle(), ['camp', 'location'])) {
        return $node;
      }
      if ($node->hasField('field_related')) {
        if ($value = $node->field_related->getValue()) {
          if ($id = $value[0]['target_id']) {
            return \Drupal::entityTypeManager()->getStorage('node')->load($id);
          }
        }
      }
      if ($node->hasField('field_site_section')) {
        if ($value = $node->field_site_section->getValue()) {
          if ($id = $value[0]['target_id']) {
            return \Drupal::entityTypeManager()->getStorage('node')->load($id);
          }
        }
      }
    }

    return $this->context;
  }

}
