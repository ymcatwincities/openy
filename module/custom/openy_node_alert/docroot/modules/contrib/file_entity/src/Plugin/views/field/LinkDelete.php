<?php

namespace Drupal\file_entity\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete the file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_entity_link_delete")
 */
class LinkDelete extends Link {

  /**
   * Prepares the link to delete the media item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $file
   *   The file entity this field belongs to.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string|null
   *   Returns a string for the link text or returns null if user has no access.
   */
  protected function renderLink($file, ResultRow $values) {
    $text = NULL;

    // Ensure user has access to delete this media item.
    if ($file->access('delete')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'file/' . $file->id() . '/delete';
      $this->options['alter']['query'] = drupal_get_destination();

      $text = !empty($this->options['text']) ? $this->options['text'] : t('Delete');
    }

    return $text;
  }

}
