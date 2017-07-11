<?php

namespace Drupal\paragraphs;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a paragraphs entity.
 * @ingroup paragraphs
 */
interface ParagraphInterface extends ContentEntityInterface, EntityOwnerInterface
{

  /**
   * Gets the parent entity of the paragraph.
   *
   * Preserves language context with translated entities.
   *
   * @return ContentEntityInterface
   *   The parent entity.
   */
  public function getParentEntity();
}
