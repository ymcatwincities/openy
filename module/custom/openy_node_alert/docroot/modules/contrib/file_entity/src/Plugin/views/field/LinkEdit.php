<?php

namespace Drupal\file_entity\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to present a link to edit the file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_entity_link_edit")
 */
class LinkEdit extends Link {

  /**
   * Prepares the link to editing the file entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $file
   *   The media entity this field belongs to.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string
   *   Returns a string for the link text or returns null if user has no access.
   */
  protected function renderLink($file, ResultRow $values) {
    $text = NULL;

    // Ensure user has access to edit this media.
    if ($file->access('update')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'file/' . $file->id() . '/edit';
      $this->options['alter']['query'] = drupal_get_destination();

      $text = !empty($this->options['text']) ? $this->options['text'] : t('Edit');
    }
    return $text;
  }

}
