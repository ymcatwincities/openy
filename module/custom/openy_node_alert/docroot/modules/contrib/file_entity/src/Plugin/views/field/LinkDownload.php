<?php

namespace Drupal\file_entity\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete the file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("file_entity_link_download")
 */
class LinkDownload extends Link {

  /**
   * Prepares the link to download the file.
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
    if ($file->access('download')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $file->downloadUrl()->toString();
      $text = !empty($this->options['text']) ? $this->options['text'] : t('Download');
    }

    return $text;
  }

}
