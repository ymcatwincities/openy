<?php

namespace Drupal\openy_digital_signage_screen\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for OpenY Digital Signage Screen edit forms.
 *
 * @ingroup openy_digital_signage_screen
 */
class OpenYScreenForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['field_screen_location']['widget'][0]['target_id']['#ajax'] = [
      'callback' => array($this, 'updateRoomListing'),
      'event' => 'change',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Fetching rooms...'),
      ),
    ];

    $form_state_values = $form_state->getValues();
    $location_id = NULL;
    if (!isset($form_state_values['field_screen_location'][0])) {
      if ($this->entity->field_screen_location->entity) {
        $location_id = $this->entity->field_screen_location->entity->id();
      }
    }
    else {
      $location_id = $form_state->getValue('field_screen_location')[0]['target_id'];
    }
    if ($location_id) {
      $room_manager = \Drupal::service('openy_digital_signage_room.manager');
      $rooms = $room_manager->getLocalizedRoomOptions($location_id);
      $form['room']['widget']['#options'] = $rooms;
    }
    else {
      $form['room']['widget']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Updates room field.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return AjaxResponse
   *   The response
   */
  public function updateRoomListing(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $renderer = \Drupal::service('renderer');
    $response->addCommand(new ReplaceCommand('.field--name-room', $renderer->render($form['room'])));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Digital Signage Screen %label has been created.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Digital Signage Screen %label has been saved.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.openy_digital_signage_screen.canonical', ['openy_digital_signage_screen' => $entity->id()]);
  }

}
