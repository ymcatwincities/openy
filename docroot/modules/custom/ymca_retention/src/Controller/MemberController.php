<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\MemberActivity;
use Drupal\ymca_retention\Entity\MemberChance;
use Drupal\ymca_retention\AnonymousCookieStorage;

/**
 * Class MemberController.
 */
class MemberController extends ControllerBase {

  /**
   * Returns member data.
   */
  public function memberJson() {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (!$member_id) {
      return new JsonResponse();
    }

    $member = Member::load($member_id);
    if (!$member) {
      return new JsonResponse();
    }

    $member_values = [
      'firstName' => $member->getFirstName(),
    ];

    $response = new JsonResponse($member_values);
    return $response;
  }

  /**
   * Returns member activities.
   */
  public function memberActivitiesJson(Request $request) {
    $post = $request->request->all();
    if ($request->getMethod() == 'POST' && !empty($post)) {
      $member_id = AnonymousCookieStorage::get('ymca_retention_member');
      if (!empty($member_id)) {
        if ($post['value'] === 'true') {
          // Register activity.
          $activity = MemberActivity::create([
            'timestamp' => $post['timestamp'],
            'member' => $member_id,
            'activity_type' => $post['id'],
          ]);
          $activity->save();
        }
        elseif ($post['value'] === 'false') {
          // Remove activity.
          $activities_ids = \Drupal::entityQuery('ymca_retention_member_activity')
            ->condition('member', $member_id)
            ->condition('activity_type', (int) $post['id'])
            ->condition('timestamp', [$post['timestamp'], $post['timestamp'] + 24 * 60 * 60 - 1], 'BETWEEN')
            ->execute();
          $storage = \Drupal::entityTypeManager()->getStorage('ymca_retention_member_activity');
          $activities = $storage->loadMultiple($activities_ids);
          $storage->delete($activities);
        }
      }
    }

    /** @var \Drupal\ymca_retention\ActivityManager $service */
    $service = \Drupal::service('ymca_retention.activity_manager');
    $member_activities = $service->getMemberActivitiesModel();

    $response = new JsonResponse($member_activities);
    return $response;
  }

  /**
   * Returns member chances to win.
   */
  public function memberChancesJson(Request $request) {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (!$member_id) {
      return new JsonResponse();
    }

    // Check that member exists.
    $member = Member::load($member_id);
    if (!$member) {
      return new JsonResponse();
    }

    // Use one chance to win.
    if ($request->getMethod() == 'POST') {
      $chances_ids = \Drupal::entityQuery('ymca_retention_member_chance')
        ->condition('member', $member_id)
        ->condition('played', 0)
        ->execute();
      $chance_id = array_shift($chances_ids);
      $chance = MemberChance::load($chance_id);

      $chance->set('played', time());
      $chance->set('winner', 1);
      $chance->set('message', 'Won a card');
      $chance->save();
    }

    $storage = \Drupal::entityTypeManager()->getStorage('ymca_retention_member_chance');
    $chances = $storage->loadByProperties(['member' => $member_id]);

    $chances_values = [];
    foreach ($chances as $chance) {
      $chances_values[] = [
        'type' => $chance->get('type')->value,
        'played' => $chance->get('played')->value,
        'message' => $chance->get('message')->value,
      ];
    }

    $response = new JsonResponse($chances_values);
    return $response;
  }

  /**
   * Returns member checkins history.
   */
  public function memberCheckInsJson() {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (!$member_id) {
      return new JsonResponse();
    }

    // Check that member exists.
    $member = Member::load($member_id);
    if (!$member) {
      return new JsonResponse();
    }

    $checkin_ids = \Drupal::entityQuery('ymca_retention_member_checkin')
      ->condition('member', $member_id)
      ->execute();
    $storage = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member_checkin');
    $checkins = $storage->loadMultiple($checkin_ids);

    $checkin_values = [];
    foreach ($checkins as $checkin) {
      $checkin_values[$checkin->get('date')->value] = (int) $checkin->get('checkin')->value;
    }

    return new JsonResponse($checkin_values);
  }

}
