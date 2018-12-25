<?php
/**
 * @file
 * Contains \Drupal\contact_storage\MessageViewsData.
 */

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

    return $data;
  }

}
