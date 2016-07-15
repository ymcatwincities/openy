<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ActivityController.
 */
class ActivityController extends ControllerBase {

  /**
   * Returns member activities.
   */
  public function memberActivitiesJson() {
    /** @var \Drupal\ymca_retention\ActivityManager $service */
    $service = \Drupal::service('ymca_retention.activity_manager');
    $member_activities = $service->getMemberActivities();

    $response = new JsonResponse($member_activities);

    return $response;
  }

}
