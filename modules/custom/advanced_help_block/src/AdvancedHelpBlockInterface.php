<?php

namespace Drupal\advanced_help_block;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Advanced Help Block entity.
 *
 * @ingroup advanced_help_block
 */
interface AdvancedHelpBlockInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
