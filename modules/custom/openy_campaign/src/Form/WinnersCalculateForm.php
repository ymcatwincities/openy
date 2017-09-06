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
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    // Show campaign winners
    $form['winners_view'] = views_embed_view('campaign_winners', 'campaign_winners_block', $node);

    $form['campaign_id'] = [
      '#type' => 'value',
      '#value' => $node,
    ];

    $form['generate'] = [
      '#type' => 'details',
      '#title' => t('Generate winners'),
      '#description' => t('Note! All current winners will be deleted.'),
      '#open' => FALSE,
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
    // Get excluded terms
    $excludedTids = $campaign->get('field_exclude_activities')->getValue();
    $excluded = [];
    foreach ($excludedTids as $value) {
      $excluded[] = $value['value'];
    }

    $activitiesVoc = $campaign->field_campaign_fitness_category->target_id;
    $activitiesTree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($activitiesVoc, 0);
    // Collect activities by it's parent item
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

    // Get all branches
    $values = [
      'type' => 'branch',
      'status' => 1,
    ];
    $branches = $this->entityTypeManager->getListBuilder('node')->getStorage()->loadByProperties($values);

    $operations = [
      [[get_class($this), 'deleteWinners'], [$campaignId]],
      [[get_class($this), 'processCampaignBatch'], [$campaign, $branches, $activities]],
    ];
    $batch = [
      'title' => t('Get winners'),
      'operations' => $operations,
      'finished' => [get_class($this), 'finishBatch'],
    ];

    batch_set($batch);
  }

  /**
   * Delete current Campaign winners.
   *
   * @param Node $campaignId Campaign node ID
   */
  public static function deleteWinners($campaignId) {
    // Remove all winners.
    $connection = \Drupal::service('database');
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('openy_campaign_winner', 'w');
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
  public static function processCampaignBatch($campaign, $branches, $activities, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;

      $context['sandbox']['campaign'] = $campaign;
      $context['sandbox']['activities'] = $activities;
      $context['sandbox']['branches'] = array_keys($branches);

      $context['sandbox']['max'] = count($branches);
    }

    $branchId = $context['sandbox']['branches'][$context['sandbox']['progress']];
    $campaign = $context['sandbox']['campaign'];
    $activities = $context['sandbox']['activities'];

    // Get all member campaigns for this branch with goal, visits and main activity
    $memberCampaignsInfo = self::getInfoByBranch($campaign, $branchId, $activities);
    // All members to get winners by visits
    $allResults = $memberCampaignsInfo['all_visits'];

    $mainActivities = array_keys($memberCampaignsInfo);
    // Calculate winners per category
    foreach ($mainActivities as $category) {
      $memberCampaigns = ($category != 'all_visits') ? $memberCampaignsInfo[$category] : $allResults;

      // Separate all reached the goal MemberCampaigns - get place with random
      $reachedGoal = [];
      $other = [];
      foreach ($memberCampaigns as $item) {
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
      $places = [1, 2, 3];
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

        // Delete winner from all results array
        unset($allResults[$memberCampaignItem['member_campaign']]);

        // Create Winner entity. If winner defined by all visits without activity - set Activity = 0
        $winner = Winner::create([
          'member_campaign' => $memberCampaignItem['member_campaign'],
          'activity' => (is_numeric($category) && in_array($category, array_keys($activities))) ? $category : 0,
          'place' => $place,
        ]);
        $winner->save();

        $context['results'][] = $memberCampaignItem['member_campaign'];
      }
    }

    $context['sandbox']['progress']++;

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Get all needed data for current campaign
   *
   * @param Node $campaign Campaign node entity.
   * @param int $branchId Branch ID to calculate winners for.
   * @param array $activities Array of arrays with all activities for current Campaign.
   *    Key - parent activity, value - array of child ones.
   *
   * @return array
   */
  private function getInfoByBranch($campaign, $branchId, $activities) {
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
    $queryAct->leftjoin('openy_member_campaign_activity', 'a', 'mc.id = a.member_campaign');
    $queryAct->fields('a', ['activity']);

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
          $memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category] = !empty($memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category]) ?
            $memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category] + 1 : 1;
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

      // Collect result array grouped by main activity
      if (isset($mcData['main_activity'])) {
        $resultInfo[$mcData['main_activity']][] = $mcData;
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
