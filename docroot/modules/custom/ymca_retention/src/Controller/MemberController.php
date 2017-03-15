<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Component\Utility\Unicode;
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
      'email' => $member->getEmail(),
      'visitsGoal' => $member->getVisitGoal(),
    ];

    return new JsonResponse($member_values);
  }

  /**
   * Register member bonus.
   */
  public function addBonus(Request $request) {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (empty($member_id)) {
      return new JsonResponse();
    }
    $post = $request->request->all();

    // Check the timestamp is within campaign dates.
    $general_config = $this->config('ymca_retention.general_settings');
    $open_date = (new \DateTime($general_config->get('date_campaign_open')))->setTime(0, 0);
    $close_date = (new \DateTime($general_config->get('date_campaign_close')))->setTime(0, 0);

    if ($post['timestamp'] < $open_date->getTimestamp() || $post['timestamp'] > $close_date->getTimestamp()) {
      return new JsonResponse();
    }

    // Check that member exists.
    $member = Member::load($member_id);
    if (!$member) {
      return new JsonResponse();
    }

    $bonus_values = $this->getMemberBonuses($member_id);

    // @todo use bonus code from config.
    if (!isset($bonus_values[$post['timestamp']])) {
      // Register bonus.
      $bonus = \Drupal::entityTypeManager()
        ->getStorage('ymca_retention_member_bonus')
        ->create([
          'created' => REQUEST_TIME,
          'date' => $post['timestamp'],
          'member' => $member_id,
          'bonus_code' => $post['bonus_code'],
        ]);
      $bonus->save();
      $bonus_values[$post['timestamp']] = $post['bonus_code'];
    }

    return new JsonResponse($bonus_values);
  }

  /**
   * Returns member activities.
   */
  public function memberActivitiesJson(Request $request) {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (empty($member_id)) {
      return new JsonResponse();
    }

    $post = $request->request->all();
    if ($request->getMethod() == 'POST' && !empty($post)) {
      if ($post['value'] === 'true') {
        $this->registerActivity($member_id, $post);
      }
      elseif ($post['value'] === 'false') {
        $this->removeActivity($member_id, $post);
      }
    }

    /** @var \Drupal\ymca_retention\ActivityManager $activity_manager */
    $activity_manager = \Drupal::service('ymca_retention.activity_manager');
    $member_activities = $activity_manager->getMemberActivitiesModel($member_id);

    return new JsonResponse($member_activities);
  }

  /**
   * Register member activity.
   */
  public function registerActivity($member_id, $post) {
    // Check the timestamp is within campaign dates.
    $settings = $this->config('ymca_retention.general_settings');
    $date_open = new \DateTime($settings->get('date_reporting_open'));
    $date_close = new \DateTime($settings->get('date_reporting_close'));

    if ($post['timestamp'] < $date_open->getTimestamp() || $post['timestamp'] > $date_close->getTimestamp()) {
      return;
    }

    // Register activity.
    $activity = MemberActivity::create([
      'timestamp' => $post['timestamp'],
      'member' => $member_id,
      'activity_type' => $post['id'],
    ]);
    $activity->save();
  }

  /**
   * Remove member activity.
   */
  public function removeActivity($member_id, $post) {
    // Remove activity.
    $activities_ids = \Drupal::entityQuery('ymca_retention_member_activity')
      ->condition('member', $member_id)
      ->condition('activity_type', (int) $post['id'])
      ->condition('timestamp', [$post['timestamp'], $post['timestamp'] + 24 * 60 * 60 - 1], 'BETWEEN')
      ->execute();
    $storage = $this->entityTypeManager()->getStorage('ymca_retention_member_activity');
    $activities = $storage->loadMultiple($activities_ids);
    $storage->delete($activities);
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
        ->sort('id')
        ->execute();
      $chance_id = array_shift($chances_ids);

      if ($chance_id) {
        $chance = MemberChance::load($chance_id);

        /** @var \Drupal\ymca_retention\InstantWin $instant_win */
        $instant_win = \Drupal::service('ymca_retention.instant_win');
        $instant_win->play($member, $chance);
      }
    }

    $chances = $this->entityTypeManager()
      ->getStorage('ymca_retention_member_chance')
      ->loadByProperties(['member' => $member_id]);

    $chances_values = [];
    /** @var MemberChance $chance */
    foreach ($chances as $chance) {
      $chances_values[] = [
        'id' => $chance->id(),
        'type' => $chance->get('type')->value,
        'played' => $chance->get('played')->value,
        'winner' => $chance->get('winner')->value,
        'value' => $chance->get('value')->value,
        'message' => $chance->get('message')->value,
      ];
    }

    return new JsonResponse($chances_values);
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

    $checkins = $this->entityTypeManager()
      ->getStorage('ymca_retention_member_checkin')
      ->loadMultiple($checkin_ids);

    $checkin_values = [];
    foreach ($checkins as $checkin) {
      $checkin_values[$checkin->get('date')->value] = (int) $checkin->get('checkin')->value;
    }

    return new JsonResponse($checkin_values);
  }

  /**
   * Returns member bonuses history.
   */
  public function memberBonusesJson() {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (!$member_id) {
      return new JsonResponse();
    }

    $bonus_values = $this->getMemberBonuses($member_id);

    return new JsonResponse($bonus_values);
  }

  /**
   * Returns recent winners.
   */
  public function recentWinnersJson() {
    $settings = $this->config('ymca_retention.general_settings');
    $limit = $settings->get('recent_winners_limit');
    $chances_ids = \Drupal::entityQuery('ymca_retention_member_chance')
      ->condition('winner', 1)
      ->condition('played', 0, '<>')
      ->sort('played', 'DESC')
      ->range(0, $limit)
      ->execute();

    if (!$chances_ids) {
      return new JsonResponse();
    }

    $chances = $this->entityTypeManager()
      ->getStorage('ymca_retention_member_chance')
      ->loadMultiple($chances_ids);

    $winners_values = [];
    /** @var MemberChance $chance */
    foreach ($chances as $chance) {
      $winners_values[] = [
        'name' => $chance->member->entity->getFirstName() . ' ' . Unicode::substr($chance->member->entity->getLastName(), 0, 1) . '.',
        'played' => $chance->get('played')->value,
      ];
    }

    return new JsonResponse($winners_values);
  }

  /**
   * Returns member bonuses array.
   */
  private function getMemberBonuses($member_id) {
    $bonus_values = [];

    // Check that member exists.
    $member = Member::load($member_id);
    if (!$member) {
      return $bonus_values;
    }

    $bonus_ids = \Drupal::entityQuery('ymca_retention_member_bonus')
      ->condition('member', $member_id)
      ->execute();

    $bonuses = $this->entityTypeManager()
      ->getStorage('ymca_retention_member_bonus')
      ->loadMultiple($bonus_ids);

    foreach ($bonuses as $bonus) {
      $bonus_values[$bonus->getDate()] = $bonus->getBonusCode();
    }

    return $bonus_values;
  }

}
