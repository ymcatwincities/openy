<?php

namespace Drupal\openy_campaign\Form;

use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\Winner;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a "openy_campaign_winners_block_form" form.
 */
class WinnersCalculateForm extends FormBase {

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * CalcBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RendererInterface $renderer, EntityTypeManagerInterface $entity_type_manager) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_winners_calculate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {
    $form['campaign_id'] = [
      '#type' => 'value',
      '#value' => $nid,
    ];

    $form['generate'] = [
      '#type' => 'details',
      '#title' => $this->t('Generate winners'),
      '#description' => $this->t('Note! All current winners will be deleted.'),
      '#open' => FALSE,
    ];

    $form['generate']['visits_goal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate winners by Visits goal'),
      '#default_value' => TRUE,
    ];

    /** @var Node $campaign */
    $campaign = \Drupal::routeMatch()->getParameter('node');
    $activitiesVoc = $campaign->field_campaign_fitness_category->target_id;
    if (!empty($form_state->getValue('field_campaign_fitness_category'))) {
      $activitiesVoc = $form_state->getValue('field_campaign_fitness_category')[0]['target_id'];
    }
    $activitiesTree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($activitiesVoc, 0, 1);
    $options = [];
    foreach ($activitiesTree as $item) {
      $options[$item->tid] = $item->name;
    }

    $form['generate']['not_matched_activities'] = [
      '#type' => 'select',
      '#title' => $this->t('Activities, not related to check-ins.'),
      '#description' => $this->t('Select activities that NOT related to real branch visits (check-ins). They will not be matched with today check-in.'),
      '#default_value' => 58, // hardcoded value for Community
      '#options' => $options,
      '#multiple' => TRUE,
    ];

    $form['generate']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate winners'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $campaignId = $form_state->getValue('campaign_id');
    /** @var Node $campaign */
    $campaign = Node::load($campaignId);

    // Get all branches.
    $values = [
      'type' => 'branch',
      'status' => 1,
    ];
    $branches = $this->entityTypeManager->getListBuilder('node')->getStorage()->loadByProperties($values);

    // Get all needed activities grouped by it's parent item.
    $activitiesVoc = $campaign->field_campaign_fitness_category->target_id;
    $activitiesTree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($activitiesVoc, 0);
    // Get excluded terms
    $excludedTids = $campaign->get('field_exclude_activities')->getValue();
    $excluded = [];
    foreach ($excludedTids as $value) {
      $excluded[] = $value['value'];
    }
    // Collect activities
    $activities = [];
    foreach ($activitiesTree as $item) {
      $parent = isset($item->parents[0]) ? $item->parents[0] : '';
      // Exclude terms from 'field_exclude_activities'
      if (in_array($item->tid, $excluded) || in_array($parent, $excluded)) {
        continue;
      }

      if (empty($parent)) {
        $activities[$item->tid][] = $item->tid;
      } else {
        $activities[$parent][] = $item->tid;
      }
    }

    // Get all places to determinate winners. Example: [1, 2, 3, 4]
    $places = [];
    foreach ($campaign->field_campaign_winners_prizes as $item) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $item->entity;
      if (!empty($paragraph->field_prgf_place->value)) {
        $places[] = $paragraph->field_prgf_place->value;
      }
    }

    // Define should we calculate winners for Visits goal
    $isVisitsGoal = !empty($form_state->getValue('visits_goal')) ? TRUE : FALSE;

    // Not matched activities
    $notMatchedVisitsActivities = $form_state->getValue('not_matched_activities');

    $operations = [
      [[get_class($this), 'deleteWinners'], [$campaignId, $isVisitsGoal]],
      [[get_class($this), 'processCampaignWinnersBatch'], [$campaign, $branches, $activities, $notMatchedVisitsActivities, $places, $isVisitsGoal]],
    ];

    $batch = [
      'title' => t('Get winners'),
      'operations' => $operations,
      'finished' => [get_class($this), 'finishBatch'],
    ];

    batch_set($batch);
  }

  /**
   * Recursive function to get random winner excluding already won members.
   *
   * @param array $data
   * @param array $alreadyWinners
   *
   * @return mixed
   */
  private static function getRandomWinner(&$data, &$alreadyWinners) {
    // Randomize array
    shuffle($data);

    $memberCampaignItem = array_shift($data);

    if (!in_array($memberCampaignItem['member_campaign'], $alreadyWinners)) {
      $alreadyWinners[] = $memberCampaignItem['member_campaign'];
      return $memberCampaignItem;
    }
    else {
      if (!empty($data)) {
        return self::getRandomWinner($data, $alreadyWinners);
      }
    }
  }

  /**
   * Delete current Campaign winners.
   *
   * @param int $campaignId Campaign node ID
   * @param bool $isAll Should we delete all winners or only winners with defined activity
   */
  public static function deleteWinners($campaignId, $isAll = TRUE) {
    $connection = \Drupal::service('database');
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('openy_campaign_winner', 'w');
    // Delete only winners with defined activity
    if (!$isAll) {
      $query->condition('w.activity', 0, '!=');
    }
    $query->join('openy_campaign_member_campaign', 'mc', 'w.member_campaign = mc.id');
    $query->condition('mc.campaign', $campaignId);
    $query->fields('w', ['id']);
    $winnerIDs = $query->execute()->fetchCol();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage('openy_campaign_winner');
    $winners = $storage->loadMultiple($winnerIDs);
    $storage->delete($winners);
  }

  /**
   * Batch step to calculate winners for each branch.
   */
  public static function processCampaignWinnersBatch($campaign, $branches, $activities, $notMatchedVisitsActivities, $places, $isVisitsGoal, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;

      $context['sandbox']['campaign'] = $campaign;
      $context['sandbox']['activities'] = $activities;
      $context['sandbox']['branches'] = array_keys($branches);
      $context['sandbox']['places'] = $places;

      $connection = \Drupal::service('database');
      /** @var \Drupal\Core\Database\Query\Select $queryCheck */
      $queryCheck = $connection->select('openy_campaign_member_checkin', 'ch');
      $queryCheck->fields('ch', ['member', 'date']);
      $checksRes = $queryCheck->execute()->fetchAll();
      // Group check-ins dates by member
      $allChecks = [];
      foreach ($checksRes as $res) {
        $allChecks[$res->member][] = $res->date;
      }
      $context['sandbox']['all_checks'] = $allChecks;

      $context['sandbox']['max'] = count($branches);
    }

    $branchId = $context['sandbox']['branches'][$context['sandbox']['progress']];
    $campaign = $context['sandbox']['campaign'];
    $activities = $context['sandbox']['activities'];
    $places = $context['sandbox']['places'];
    $allChecks = $context['sandbox']['all_checks'];

    // Get all member campaigns for this branch with goal, visits and main activity
    $memberCampaignsInfo = self::getInfoByBranch($campaign, $branchId, $activities, $notMatchedVisitsActivities, $allChecks);

    $alreadyWinners = [];

    // Calculate winners for Visits goal
    $goalWinners = [];
    if ($isVisitsGoal) {
      $goalWinners = self::getVisitsGoalWinners($memberCampaignsInfo['all_visits'], $places);
      // Collect $alreadyWinners array
      foreach ($goalWinners as $item) {
        $alreadyWinners[] = $item['member_campaign'];
      }
    }

    $activityWinners = [];
    // Assign places
    foreach ($places as $place) {
      // Calculate winners per activity
      foreach ($memberCampaignsInfo as $category => $data) {
        if ($category == 'all_visits') {
          continue;
        }
        if (empty($data)) {
          break;
        }

        $memberCampaignItem = self::getRandomWinner($data, $alreadyWinners);

        // Create Winner entity. If winner defined by all visits without activity - set Activity = 0
        if (!empty($memberCampaignItem)) {
          $activityWinners[] = [
            'member_campaign' => $memberCampaignItem['member_campaign'],
            'activity' => (is_numeric($category) && in_array($category, array_keys($activities))) ? $category : 0,
            'place' => $place,
          ];
        }
      }
    }

    $winners = array_merge($goalWinners, $activityWinners);

    foreach ($winners as $item) {
      $winner = Winner::create($item);
      $winner->save();

      $context['results'][] = $item['member_campaign'];
    }

    $context['sandbox']['progress']++;

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * @param array $memberCampaignsData All needed data after getInfoByBranch() function.
   * @param $places
   *
   * @return array
   */
  private static function getVisitsGoalWinners($memberCampaignsData, $places) {
    $goalWinners = [];

    // Separate all reached the goal MemberCampaigns - get place with random
    $reachedGoal = [];
    $other = [];
    foreach ($memberCampaignsData as $item) {
      if ($item['percentage'] == 100) {
        $reachedGoal[] = $item;
      } else {
        $other[] = $item;
      }
    }

    // Sort not reached the goal by percentage
    usort($other, function($a, $b) {
      return $a['percentage'] - $b['percentage'];
    });

    // Randomize array
    shuffle($reachedGoal);

    // Assign places
    foreach ($places as $place) {
      if (!empty($reachedGoal)) {
        $memberCampaignItem = array_shift($reachedGoal);
      }
      elseif (!empty($other)) {
        $memberCampaignItem = $other[0];
        unset($other);
      }
      else {
        break;
      }

      // Create Winner entity. If winner defined by Visits goal without activity - set Activity = 0
      $goalWinners[] = [
        'member_campaign' => $memberCampaignItem['member_campaign'],
        'activity' => 0,
        'place' => $place,
      ];
    }

    return $goalWinners;
  }

  /**
   * Get all needed data for current campaign
   *
   * @param Node $campaign Campaign node entity.
   * @param int $branchId Branch ID to calculate winners for.
   * @param array $activities Array of arrays with all activities for current Campaign.
   *    Key - parent activity, value - array of child ones.
   * @param array $notMatchedVisitsActivities Array of parent activities that shouldn't be checked with visits (like Community).
   * @param array $allChecks Grouped by member ID array of check-ins time.
   *
   * @return array
   */
  private static function getInfoByBranch($campaign, $branchId, $activities, $notMatchedVisitsActivities, $allChecks) {
    /** @var Node $campaign */
    $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
    $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString());

    $connection = \Drupal::service('database');

    // Get visits
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('openy_campaign_member_checkin', 'ch');
    $query->condition('ch.date', $campaignStartDate->format('U'), '>=');
    $query->condition('ch.date', $campaignEndDate->format('U'), '<');
    $query->addExpression('COUNT(ch.id)', 'visits');
    $query->groupBy('ch.member');
    $query->fields('ch', ['member']);
    $query->join('openy_campaign_member', 'm', 'm.id = ch.member');
    $query->condition('m.branch', $branchId);
    $query->condition('m.is_employee', FALSE);
    $query->join('openy_campaign_member_campaign', 'mc', 'm.id = mc.member');
    $query->condition('mc.campaign', $campaign->id());

    // Get activities for all members
    /** @var \Drupal\Core\Database\Query\Select $queryAct */
    $queryAct = $connection->select('openy_campaign_member_campaign', 'mc');
    $queryAct->join($query, 'vis', 'mc.member = vis.member');
    $queryAct->condition('mc.campaign', $campaign->id());
    $queryAct->addField('mc', 'id', 'member_campaign');
    $queryAct->fields('mc', ['member', 'goal']);
    $queryAct->addField('vis', 'visits');
    $queryAct->leftjoin('openy_campaign_memb_camp_actv', 'a', 'mc.id = a.member_campaign');
    $queryAct->fields('a', ['activity', 'date']);

    $results = $queryAct->execute()->fetchAll();

    $mainActivities = array_keys($activities);

    $memberCampaignsInfo = [];
    // Get visits by activity
    foreach ($results as $item) {
      $memberCampaignsInfo[$item->member_campaign]['member_campaign'] = $item->member_campaign;
      $memberCampaignsInfo[$item->member_campaign]['goal'] = $item->goal;
      $memberCampaignsInfo[$item->member_campaign]['visits'] = $item->visits;

      foreach ($mainActivities as $category) {
        $allItems = $activities[$category];
        if (in_array($item->activity, $allItems)) {
          // Check with real visits - check-ins
          if (!in_array($category, $notMatchedVisitsActivities)) {
            if (in_array($item->date, $allChecks[$item->member])) {
              $memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category] = !empty($memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category]) ?
                $memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category] + 1 : 1;
            }
          } else {
            $memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category] = !empty($memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category]) ?
              $memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category] + 1 : 1;
          }
          break;
        }
      }
    }

    // Get main activity for each MemberCampaign and collect result array
    $resultInfo = [];
    foreach ($memberCampaignsInfo as &$mcData) {
      // Get only members who tracked activities on site
      if (!empty($mcData['activity_visits'])) {
        $mcData['main_activity'] = array_search(max($mcData['activity_visits']), $mcData['activity_visits']);
      }
      // Calculate percentage
      if ($mcData['visits'] - $mcData['goal'] >= 0) {
        $mcData['percentage'] = 100;
      } else {
        $mcData['percentage'] = round($mcData['visits'] / $mcData['goal'] * 100, 2);
      }

      // Collect result array grouped by activity
      if (!empty($mcData['activity_visits'])) {
        foreach ($mcData['activity_visits'] as $cat => $count) {
          if ($count > 0) {
            $resultInfo[$cat][] = $mcData;
          }
        }
      }
    }

    // Collect all data to get winner by visits
    $resultInfo['all_visits'] = $memberCampaignsInfo;

    return $resultInfo;
  }

  /**
   * Finish batch.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'Created one winner.', 'Created @count winners.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }
}
