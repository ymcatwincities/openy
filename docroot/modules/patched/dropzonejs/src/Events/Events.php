<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\Events\Events.
 */

namespace Drupal\dropzonejs\Events;

/**
 * Contains all events thrown by dropzonejs.
 */
final class Events {

  /**
   * The MEDIA_ENTITY_CREATE event occurs when creating a new Media Entity,
   * before it is saved to the database.
   *
   * @var string
   */
  const MEDIA_ENTITY_CREATE = 'dropzonejs.media_entity_create';

}
