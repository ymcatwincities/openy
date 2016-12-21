<?php

namespace Drupal\contact_storage;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Customized contact message view that does not do HTML to plain conversion.
 *
 * Also relies on standard field formatters to build the message. Does not
 * extend from MessageViewBuilder to avoid running that code.
 */
class ContactMessageViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The message fields are individually rendered into email templates, so
    // the entity has no template itself.
    // @todo  Remove this when providing a template in
    // https://www.drupal.org/node/2722501.
    unset($build['#theme']);
    return $build;
  }

}
