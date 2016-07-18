<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\Entity\Term;
use Drupal\ymca_retention\Entity\MemberActivity;
use Drupal\Core\Url;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Defines activities manager service.
 */
class ActivityManager implements ActivityManagerInterface {

  /**
   * The session manager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The injected session manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The injected current user account.
   */
  public function __construct(SessionManagerInterface $session_manager, AccountProxyInterface $current_user) {
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getDates() {
    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');

    // Get start and end date of retention campaign.
    $date_start = new \DateTime($settings->get('date_reporting_open'));
    $date_end = new \DateTime($settings->get('date_reporting_close'));
    $date_now = new \DateTime();
    $date_now->setTime(0, 0, 0);

    // Calculate number of days to show.
    $date_interval = $date_start->diff($date_end);
    $days = $date_interval->d;
    if ($date_interval->h > 0 || $date_interval->i > 0) {
      $days++;
    }

    // Prepare dates data.
    $dates = [];
    $date = $date_start->setTime(0, 0, 0);
    $day_interval = new \DateInterval('P1D');
    for ($i = 0; $i < $days; $i++) {
      $timestamp = $date->getTimestamp();
      $date_diff_now = $date->diff($date_now);
      $dates[] = [
        'weekday' => $date->format('D'),
        'month_day' => $date->format('j'),
        'timestamp' => $timestamp,
        'past' => !(bool) $date_diff_now->invert,
        'future' => (bool) $date_diff_now->invert,
        'activities_count' => rand(0, 3),
      ];

      $date = $date->add($day_interval);
    }

    return $dates;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityGroups() {
    // Prepare taxonomy data.
    $activity_groups = [];
    /** @var TermStorage $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $parents = $term_storage->loadTree('ymca_retention_activities', 0, 1, TRUE);
    /** @var Term $parent */
    foreach ($parents as $parent) {
      $children = $term_storage->loadTree('ymca_retention_activities', $parent->id(), 1, TRUE);
      $activities = [];
      /** @var Term $child */
      foreach ($children as $child) {
        $activities[] = [
          'id' => $child->id(),
          'name' => $child->getName(),
          'description' => $child->getDescription(),
        ];
      }

      $activity_groups[] = [
        'id' => $parent->id(),
        'name' => $parent->getName(),
        'description' => $parent->getDescription(),
        'activities' => $activities,
      ];
    }

    return $activity_groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberActivities() {
    $member_activities = [];
    $activity_groups = $this->getActivityGroups();
    $model = [];
    foreach ($activity_groups as $activity_group) {
      foreach ($activity_group['activities'] as $activity) {
        $model[$activity['id']] = FALSE;
      }
    }
    $dates = $this->getDates();
    foreach ($dates as $date) {
      $member_activities[$date['timestamp']] = $model;
    }

    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (empty($member_id)) {
      return $member_activities;
    }

    $activities_ids = \Drupal::entityQuery('ymca_retention_member_activity')
      ->condition('member', $member_id)
      ->execute();
    $activities = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member_activity')
      ->loadMultiple($activities_ids);

    $date = new \DateTime();
    /** @var MemberActivity $activity */
    foreach ($activities as $activity) {
      $timestamp = $date->setTimestamp($activity->get('timestamp')->value)->setTime(0, 0, 0)->getTimestamp();
      $id = $activity->activity_type->target_id;
      $member_activities[$timestamp][$id] = TRUE;
    }

    return $member_activities;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    // We need to start session for the CSRF token protection to work.
    // TODO: replace this protection to smth custom?
    if ($this->currentUser->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = TRUE;
      $this->sessionManager->start();
    }

    $urlBubbleable = Url::fromRoute('ymca_retention.member_activities_json')->toString(TRUE);
    $urlRender = array(
      '#markup' => $urlBubbleable->getGeneratedUrl(),
    );
    BubbleableMetadata::createFromRenderArray($urlRender)->merge($urlBubbleable)->applyTo($urlRender);
    $url = \Drupal::service('renderer')->renderPlain($urlRender);
    return $url;
  }

}
