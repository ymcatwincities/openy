<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Routing\CurrentRouteMatch;
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
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * WinnersCalculateForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   */
  public function __construct(
    RendererInterface $renderer,
    EntityTypeManagerInterface $entityTypeManager,
    CurrentRouteMatch $routeMatch
  ) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
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

    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->routeMatch->getParameter('node');
    $activitiesVoc = $campaign->field_campaign_fitness_category->target_id;
    if (!empty($form_state->getValue('field_campaign_fitness_category'))) {
      $activitiesVoc = $form_state->getValue('field_campaign_fitness_category')[0]['target_id'];
    }
    $activitiesTree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($activitiesVoc, 0, 1);
    $options = [];
    foreach ($activitiesTree as $item) {
      $options[$item->tid] = $item->name;
    }

    $form['generate']['excluded_activities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Activities, excluded from the drawing.'),
      '#default_value' => '',
      '#options' => $options,
      '#multiple' => TRUE,
    ];

    // Get all enabled activities list.
    $activitiesOptions = openy_campaign_get_enabled_activities($campaign);

    $enableVisitsGoal = in_array('field_prgf_activity_visits', $activitiesOptions);
    $form['generate']['visits_goal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run Visit Goal Random Drawing'),
      '#default_value' => $enableVisitsGoal,
    ];
    if (!$enableVisitsGoal) {
      $form['generate']['visits_goal']['#attributes'] = [
        'disabled' => TRUE,
      ];
    }

    $form['generate']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Draw winners'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $campaignId = $form_state->getValue('campaign_id');
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->entityTypeManager->getStorage('node')->load($campaignId);

    // Get all branches.
    $branches = [];
    $branchTargets = $campaign->get('field_campaign_branch_target')->getValue();
    foreach ($branchTargets as $branchTarget) {
      $branches[$branchTarget['target_id']] = $branchTarget['value'];
    }

    // Get all needed activities grouped by it's parent item.
    $activitiesVoc = $campaign->field_campaign_fitness_category->target_id;
    $activitiesTree = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($activitiesVoc, 0);
    // Get excluded terms.
    $excludedTids = $form_state->getValue('excluded_activities');

    $excluded = [];
    foreach ($excludedTids as $value) {
      $excluded[] = $value;
    }
    // Collect activities.
    $activities = [];
    foreach ($activitiesTree as $item) {
      $parent = isset($item->parents[0]) ? $item->parents[0] : '';
      // Exclude terms from 'excluded_activities'.
      if (in_array($item->tid, $excluded) || in_array($parent, $excluded)) {
        continue;
      }

      if (empty($parent)) {
        $activities[$item->tid][] = $item->tid;
      }
      else {
        $activities[$parent][] = $item->tid;
      }
    }

    // Get all places to determinate winners. Example: [1, 2, 3, 4].
    $places = [];
    foreach ($campaign->field_campaign_winners_prizes as $item) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $item->entity;
      if (!empty($paragraph->field_prgf_place->value)) {
        $places[] = $paragraph->field_prgf_place->value;
      }
    }

    // Define should we calculate winners for Visits goal.
    $isVisitsGoal = !empty($form_state->getValue('visits_goal'));

    $operations = [
      [[get_class($this), 'deleteWinners'], [$campaignId, $isVisitsGoal]],
      [[get_class($this), 'processCampaignWinnersBatch'], [$campaign, $branches, $activities, $places, $isVisitsGoal]],
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
    // Randomize array.
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
   * @param int $campaignId
   *   Campaign node ID.
   * @param bool $isAll
   *   Should we delete all winners or only winners with defined activity.
   */
  public static function deleteWinners($campaignId, $isAll = TRUE) {
    $connection = \Drupal::service('database');
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('openy_campaign_winner', 'w');
    $query->fields('w', ['id']);
    $query->join('openy_campaign_member_campaign', 'mc', 'w.member_campaign = mc.id');

    // Delete only winners with defined activity.
    if (!$isAll) {
      $query->condition('w.activity', 0, '!=');
    }
    $query->condition('mc.campaign', $campaignId);

    $winnerIDs = $query->execute()->fetchCol();

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage('openy_campaign_winner');
    $winners = $storage->loadMultiple($winnerIDs);
    $storage->delete($winners);
  }

  /**
   * Batch step to calculate winners for each branch.
   */
  public static function processCampaignWinnersBatch($campaign, $branches, $activities, $places, $isVisitsGoal, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;

      $context['sandbox']['campaign'] = $campaign;
      $context['sandbox']['activities'] = $activities;
      $context['sandbox']['branches'] = array_keys($branches);
      $context['sandbox']['places'] = $places;

      $context['sandbox']['max'] = count($branches);
    }

    $branchId = $context['sandbox']['branches'][$context['sandbox']['progress']];
    $campaign = $context['sandbox']['campaign'];
    $activities = $context['sandbox']['activities'];
    $places = $context['sandbox']['places'];

    // Get all member campaigns for this branch.
    $memberCampaignsInfo = self::getInfoByBranch($campaign, $branchId, $activities);

    $alreadyWinners = [];

    $activityWinners = [];
    // Assign places.
    foreach ($places as $place) {
      // Calculate winners per activity.
      foreach ($memberCampaignsInfo as $category => $data) {
        if (empty($data)) {
          break;
        }

        $memberCampaignItem = self::getRandomWinner($data, $alreadyWinners);

        // Create Winner entities.
        if (!empty($memberCampaignItem)) {
          $activityWinners[] = [
            'member_campaign' => $memberCampaignItem['member_campaign'],
            'activity' => $category,
            'place' => $place,
          ];
        }
      }
    }

    // Calculate winners by Visits goal.
    $goalWinners = [];
    if ($isVisitsGoal) {
      /** @var \Drupal\openy_campaign\CampaignMenuService $campaignMenuService */
      $campaignMenuService = \Drupal::service('openy_campaign.campaign_menu_handler');
      $goalWinnersIds = $campaignMenuService->getVisitsGoalWinners($campaign, $branchId, $alreadyWinners);
      // Create Winner entity. If winner defined by Visits goal without activity - set Activity = 0.
      foreach ($places as $place) {
        $goalWinnerId = array_shift($goalWinnersIds);
        $goalWinners[] = [
          'member_campaign' => $goalWinnerId,
          'activity' => 0,
          'place' => $place,
        ];
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
   * Get all needed data for current campaign.
   *
   * @param \Drupal\node\Entity\Node $campaign
   *   Campaign node entity.
   * @param int $branchId
   *   Branch ID to calculate winners for.
   * @param array $activities
   *   Array of arrays with all activities for current Campaign.
   *   Key - parent activity, value - array of child ones.
   *
   * @return array
   */
  private static function getInfoByBranch($campaign, $branchId, $activities) {
    $connection = \Drupal::service('database');

    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('openy_campaign_member', 'm');

    $query->join('openy_campaign_member_campaign', 'mc', 'm.id = mc.member');
    $query->join('openy_campaign_memb_camp_actv', 'mca', 'mc.id = mca.member_campaign');

    $query->addField('m', 'id', 'member');
    $query->addField('mca', 'activity');
    $query->addField('mca', 'member_campaign');

    $query->condition('m.branch', $branchId);
    $query->condition('m.is_employee', FALSE);
    $query->condition('mc.campaign', $campaign->id());

    $results = $query->execute()->fetchAll();

    $mainActivities = array_keys($activities);

    foreach ($results as $item) {
      $memberCampaignsInfo[$item->member_campaign]['member_campaign'] = $item->member_campaign;
      foreach ($mainActivities as $category) {
        $allItems = $activities[$category];
        if (in_array($item->activity, $allItems)) {
          if (empty($memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category])) {
            $memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category] = 1;
          }
          else {
            ++$memberCampaignsInfo[$item->member_campaign]['activity_visits'][$category];
          }
          break;
        }
      }
    }

    // Get main activity for each MemberCampaign and collect result array.
    $resultInfo = [];
    foreach ($memberCampaignsInfo as &$mcData) {
      // Get only members who tracked activities on site.
      if (!empty($mcData['activity_visits'])) {
        $mcData['main_activity'] = array_search(max($mcData['activity_visits']), $mcData['activity_visits']);
      }

      // Collect result array grouped by activity.
      if (!empty($mcData['activity_visits'])) {
        foreach ($mcData['activity_visits'] as $cat => $count) {
          if ($count > 0) {
            $resultInfo[$cat][] = $mcData;
          }
        }
      }
    }

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
