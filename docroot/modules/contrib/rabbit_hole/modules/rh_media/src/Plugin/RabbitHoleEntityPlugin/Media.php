<?php

namespace Drupal\rh_media\Plugin\RabbitHoleEntityPlugin;

use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginBase;

/**
 * Implements rabbit hole behavior for media.
 *
 * @RabbitHoleEntityPlugin(
 *   id = "rh_media",
 *   label = @Translation("Media"),
 *   entityType = "media"
 * )
 */
class Media extends RabbitHoleEntityPluginBase { }
