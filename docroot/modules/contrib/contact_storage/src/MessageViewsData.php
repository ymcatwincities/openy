<?php

namespace Drupal\contact_storage;

use Drupal\views\EntityViewsData;

/**
 * Provides data to integrate messages with Views.
 */
class MessageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['contact_message']['contact_form_label'] = array(
      'title' => $this->t('Form'),
      'help' => $this->t('The label of the associated form.'),
      'real field' => 'contact_form',
      'field' => array(
        'id' => 'contact_form',
      ),
    );

    $data['contact_message']['message_bulk_form'] = array(
      'title' => $this->t('Message operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple messages.'),
      'field' => array(
        'id' => 'message_bulk_form',
      ),
    );

    return $data;
  }

}
