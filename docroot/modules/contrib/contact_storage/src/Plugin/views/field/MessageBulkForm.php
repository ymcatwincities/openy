<?php

namespace Drupal\contact_storage\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a contact message operations bulk form element.
 *
 * @ViewsField("message_bulk_form")
 */
class MessageBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No message selected.');
  }

}
