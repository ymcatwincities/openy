<?php

namespace Drupal\fullcalendar\Ajax;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @todo.
 */
class Update extends ControllerBase {

  /**
   * @todo.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field
   * @param int $index
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function drop(EntityInterface $entity, $field, $index, Request $request) {
    // @todo Remove once http://drupal.org/node/1915752 is resolved.
    $index--;

    if ($request->request->has('day_delta') && $request->request->has('minute_delta')) {
      $day_delta = SafeMarkup::checkPlain($request->request->get('day_delta'));
      $minute_delta = SafeMarkup::checkPlain($request->request->get('minute_delta'));
      $delta = " $day_delta days $minute_delta minutes";

      $field_item = $entity->{$field}->get($index);
      $value = $field_item->value;
      $field_item->set('value', date(DATETIME_DATETIME_STORAGE_FORMAT, strtotime($value . $delta)));

      // Save the new start/end values.
      $entity->save();
      $message = $this->t('The new event time has been saved.') . ' [' . l($this->t('Close'), NULL, array('attributes' => array('class' => array('fullcalendar-status-close')))) . ']';
    }
    else {
      $message = $this->t('The event has not been updated.');
    }
    return new JsonResponse(array(
      'msg' => $message,
      'dom_id' => $request->request->get('dom_id'),
    ));
  }

}
