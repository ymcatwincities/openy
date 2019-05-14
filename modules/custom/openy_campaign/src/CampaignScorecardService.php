<?php

namespace Drupal\openy_campaign;

use Drupal\node\Entity\Node;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CampaignScorecardService.
 *
 * @package Drupal\openy_campaign
 */
class CampaignScorecardService {

  /**
   * The Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Live Scoreboard generation.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array|bool
   */
  public function generateLiveScorecard(Node $node) {
    if ($node->bundle() != 'campaign') {
      return FALSE;
    }

    // Prepare dates of campaign.
    $campaignTimezone = new \DateTime($node->get('field_campaign_timezone')->getString());
    $campaignTimezone = $campaignTimezone->getTimezone();

    // All time of campaign.
    $localeCampaignStart = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_start_date')->getString());
    $localeCampaignStart->convertTimezone($campaignTimezone);

    $localeCampaignEnd = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_end_date')->getString());
    $localeCampaignEnd->convertTimezone($campaignTimezone);

    // Time of early registration.
    $localeRegistrationStart = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_reg_start_date')->getString());
    $localeRegistrationStart->convertTimezone($campaignTimezone);

    $localeRegistrationEnd = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_reg_end_date')->getString());
    $localeRegistrationEnd->convertTimezone($campaignTimezone);

    $result['dates']['registration_start'] = $localeRegistrationStart->getDate()->format('m/d');
    $result['dates']['registration_end'] = $localeRegistrationEnd->getDate()->format('m/d');
    $result['dates']['campaign_start'] = $localeCampaignStart->getDate()->format('m/d');
    $result['dates']['campaign_end'] = $localeCampaignEnd->getDate()->format('m/d');

    // Current dates. + labels with dates.
    $localTime = OpenYLocaleDate::createDateFromFormat(date('c'));
    $localTime->convertTimezone($campaignTimezone);

    if ($localTime > $localeRegistrationEnd) {
      $actual_early_date = $localeRegistrationEnd->getDate()->format('m/d') . ' at ' . $localeRegistrationEnd->getDate()->format('h:ia');
    }
    else {
      $actual_early_date = $localTime->getDate()->format('m/d');
    }

    if ($localTime > $localeCampaignEnd) {
      $actual_campaign_date = $localeCampaignEnd->getDate()->format('m/d') . ' at ' . $localeCampaignEnd->getDate()->format('h:ia');
    }
    else {
      $actual_campaign_date = $localTime->getDate()->format('m/d');
    }

    $result['dates']['actual_date'] = $localTime->getDate()->format('m/d');
    $result['dates']['actual_early_date'] = $actual_early_date;
    $result['dates']['actual_campaign_date'] = $actual_campaign_date;

    // Get branches for current campaign.
    $branches = $this->getCampaignBranches($node);

    if (!empty($branches['branches'])) {

      $branchIds = [];
      foreach ($branches['branches'] as $branch) {
        $branchIds[] = $branch->branch;
      }

      $targets = $branches['targets'];
      $branchList = $branches['branches'];
      // Calculate registered members by branch, Early registration and During all campain.
      $earlyActualMembers = $this->calculateRegisteredMembersByBranch($node, $branchIds, $localeRegistrationStart->getTimestamp(), $localeRegistrationEnd->getTimestamp());
      $challengeRegistrationOfMembers = $this->calculateRegisteredMembersByBranch($node, $branchIds, $localeRegistrationStart->getTimestamp(), $localeCampaignEnd->getTimestamp());

      // Prepare render array.
      $result['registration']['early'] = [];
      $result['registration']['challenge'] = [];
      $result['utilization'] = [];

      $register_goal = $node->get('field_campaign_registration_goal')->getValue();
      $utilization_goal = $node->get('field_campaign_utilization_goal')->getValue();
      $registerGoal = !empty($register_goal) ? $register_goal[0]['value'] : 5;
      $utilizationGoal = !empty($utilization_goal) ? $utilization_goal[0]['value'] : 45;

      $result['goals']['registration_goal'] = $registerGoal;
      $result['goals']['utilization_goal'] = $utilizationGoal;

      $util_by_branch = $this->getCampaignUtilizationActivitiesByBranches($node);

      foreach ($branchList as $id => $branch) {

        $result['branches'][$id]['nid'] = $branch->personify_branch;
        $result['branches'][$id]['title'] = $branch->title;
        $target = $targets[$id];
        $result['branches'][$id]['target'] = $target;

        // Early registration calculation.
        $goal = number_format($target * $registerGoal / 100);

        $result['registration']['early'][$id]['registration_goal'] = $goal;

        $actual = !empty($earlyActualMembers[$id]->count_id) ? $earlyActualMembers[$id]->count_id : 0;
        $result['registration']['early'][$id]['actual'] = $actual;

        $of_members = number_format($actual / $targets[$id] * 100, 1);
        $result['registration']['early'][$id]['of_members'] = $of_members;

        $of_goal = $goal ? number_format($actual / $goal * 100, 1) : 0;
        $result['registration']['early'][$id]['of_goal'] = $of_goal;
        if (!array_key_exists('total', $result)) {
          $result['total'] = [];
        }
        // Total Early registration calculation.
        if (!array_key_exists('target', $result['total'])){
          $result['total']['target'] = 0;
        }
        $result['total']['target'] += $target;
        if (!array_key_exists('registration_goal', $result['total'])){
          $result['total']['registration_goal'] = 0;
        }
        $result['total']['registration_goal'] += $goal;
        if (!array_key_exists('actual', $result['total'])){
          $result['total']['actual'] = 0;
        }
        $result['total']['actual'] += $actual;
        if (!array_key_exists('of_members', $result['total'])){
          $result['total']['of_members'] = 0;
        }
        $result['total']['of_members'] += $of_members;
        if (!array_key_exists('of_goal', $result['total'])){
          $result['total']['of_goal'] = 0;
        }
        $result['total']['of_goal'] += $of_goal;

        // Challenge registration calculation.
        $result['registration']['challenge'][$id]['registration_goal'] = $goal;

        $reg_actual = !empty($challengeRegistrationOfMembers[$id]->count_id) ? $challengeRegistrationOfMembers[$id]->count_id : 0;
        $result['registration']['challenge'][$id]['actual'] = $reg_actual;

        $reg_of_member = number_format($reg_actual / $target * 100, 1);
        $result['registration']['challenge'][$id]['of_members'] = $reg_of_member;

        $reg_of_goal = $goal ? number_format($reg_actual / $goal * 100, 1) : 0;
        $result['registration']['challenge'][$id]['of_goal'] = $reg_of_goal;
        if (!array_key_exists('reg_registration_goal', $result['total'])){
          $result['total']['reg_registration_goal'] = 0;
        }
        $result['total']['reg_registration_goal'] += $goal;
        if (!array_key_exists('reg_actual', $result['total'])){
          $result['total']['reg_actual'] = 0;
        }
        $result['total']['reg_actual'] += $reg_actual;
        if (!array_key_exists('reg_of_members', $result['total'])){
          $result['total']['reg_of_members'] = 0;
        }
        $result['total']['reg_of_members'] += $reg_of_member;
        if (!array_key_exists('reg_of_goal', $result['total'])){
          $result['total']['reg_of_goal'] = 0;
        }
        $result['total']['reg_of_goal'] += $reg_of_goal;

        // Utilization calculation.
        if ($actual >= $goal) {
          $util_goal = number_format($actual * $utilizationGoal / 100);
        }
        else {
          $util_goal = number_format($goal * $utilizationGoal / 100);
        }

        $result['utilization'][$id]['goal'] = $util_goal;

        $util_actual = isset($util_by_branch[$id]) ? $util_by_branch[$id] : 0;
        $result['utilization'][$id]['actual'] = $util_actual;

        if (!empty($reg_actual)) {
          $util_of_member = number_format($util_actual / $reg_actual * 100, 1);
        }
        else {
          $util_of_member = 0;
        }

        $result['utilization'][$id]['of_members'] = $util_of_member;

        $util_of_goal = $util_goal ? number_format($util_actual / $util_goal * 100, 1) : 0;

        $result['utilization'][$id]['of_goal'] = $util_of_goal;

        // Utilization total.
        if (!array_key_exists('util_registration_goal', $result['total'])){
          $result['total']['util_registration_goal'] = 0;
        }
        $result['total']['util_registration_goal'] += $util_goal;
        // Doesn't include results for members without branch ($id = 0 or '').
        if (!array_key_exists('util_actual', $result['total'])){
          $result['total']['util_actual'] = 0;
        }
        if (!array_key_exists('util_of_members', $result['total'])){
          $result['total']['util_of_members'] = 0;
        }
        $result['total']['util_of_members'] += $util_of_member;
        if (!array_key_exists('util_of_goal', $result['total'])){
          $result['total']['util_of_goal'] = 0;
        }
        $result['total']['util_of_goal'] += $util_of_goal;

      }

      $result['total']['of_members'] = number_format($result['total']['of_members'] / count($targets), 1);
      $result['total']['of_goal'] = number_format($result['total']['of_goal'] / count($targets), 1);
      $result['total']['reg_of_members'] = number_format($result['total']['reg_of_members'] / count($targets), 1);
      $result['total']['reg_of_goal'] = number_format($result['total']['reg_of_goal'] / count($targets), 1);
      $result['total']['util_of_members'] = number_format($result['total']['util_of_members'] / count($targets), 1);
      $result['total']['util_of_goal'] = number_format($result['total']['util_of_goal'] / count($targets), 1);
    }
    else {
      $result['empty'] = t('Campaign @campaign has no active branches. Please edit current campaign', ['@campaign' => $node->label()]);
    }
    $build = [
      '#theme' => 'openy_campaign_scorecard',
      '#result' => $result
    ];

    return $build;
  }

  /**
   * @param $node Node Campaign node.
   *
   * @return array
   */
  public function getCampaignUtilizationActivitiesByBranches($node) {
    $branchIds = $node->get('field_campaign_branch_target')->getValue();
    $ids = [];
    foreach ($branchIds as $branchId) {
      $ids[] = $branchId['target_id'];
    }

    // Check for the started campaign.
    $isCampaignStarted = $this->checkIsCampaignStarted($node);

    if (!empty($ids) && $isCampaignStarted) {
      $query = $this->connection->select('openy_campaign_util_activity', 'ua');

      $query->leftJoin('openy_campaign_member_campaign', 'mc', 'ua.member_campaign = mc.id');
      $query->condition('mc.campaign', $node->id());
      $query->leftJoin('openy_campaign_member', 'cm', 'mc.member = cm.id');
      $query->fields('cm', ['branch']);
      $query->fields('ua', ['id']);
      $result = $query->execute()->fetchAll();

      $counter = [];
      foreach ($result as $item) {
        if (isset($counter[$item->branch])) {
          $counter[$item->branch]++;
        }
        else {
          $counter[$item->branch] = 1;
        }
      }

      return $counter;

    }
    return [];

  }

  /**
   * Get selected Branches from current campaign.
   *
   * @param $node
   *
   * @return mixed
   */
  public function getCampaignBranches($node) {

    $targets = [];
    $ids = [];
    $output = [
      'branches' => [],
      'targets' => [],
    ];

    $branchIds = $node->get('field_campaign_branch_target')->getValue();

    foreach ($branchIds as $branchId) {
      $ids[] = $branchId['target_id'];
      $targets[$branchId['target_id']] = $branchId['value'];
    }

    if (!empty($ids)) {
      $query = $this->connection->select('openy_campaign_mapping_branch', 'b');
      $query->fields('b', ['personify_branch', 'branch']);
      $query->join('node_field_data', 'n', 'n.nid = b.branch');
      $query->fields('n', ['title']);
      $query->condition('b.branch', $ids, 'IN');
      $query->orderBy('b.personify_branch');

      $result = $query->execute()->fetchAllAssoc('branch');

      $output['branches'] = $result;
      $output['targets'] = $targets;
    }

    return $output;
  }

  /**
   * Get Count of registered users by branches for campaign.
   *
   * @param $node
   * @param $branches
   * @param $dateStart
   * @param $dateEnd
   *
   * @return mixed
   */
  public function calculateRegisteredMembersByBranch($node, $branches, $dateStart, $dateEnd) {
    $query = $this->connection->select('openy_campaign_member', 'cm');
    $query->leftJoin('openy_campaign_member_campaign', 'mc', 'mc.member = cm.id');
    $query->addExpression('COUNT(cm.id)', 'count_id');
    $query->fields('cm', ['branch']);
    $query->condition('cm.branch', $branches, 'IN');
    $query->condition('mc.campaign', $node->id(), '=');
    $query->condition('mc.created', [$dateStart, $dateEnd], 'BETWEEN');
    $query->groupBy('cm.branch');

    $result = $query->execute()->fetchAllAssoc('branch');

    return $result;
  }

  /**
   * Check if Campaign has already started.
   *
   * @param $node Node Campaign node object.
   *
   * @return bool
   */
  private function checkIsCampaignStarted($node) {
    // Campaign timezone.
    $campaignTimezone = new \DateTime($node->get('field_campaign_timezone')->getString());
    $campaignTimezone = $campaignTimezone->getTimezone();

    // All time of campaign.
    $localeCampaignStart = OpenYLocaleDate::createDateFromFormat($node->get('field_campaign_start_date')->getString());
    $localeCampaignStart->convertTimezone($campaignTimezone);

    // Current dates. + labels with dates.
    $localTime = OpenYLocaleDate::createDateFromFormat(date('c'));
    $localTime->convertTimezone($campaignTimezone);

    return $localTime > $localeCampaignStart;
  }

}
