<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a "openy_campaign_winners_block_form" form.
 */
class LeadershipBlockForm extends FormBase {

  const MAX_LEADERS = 50;

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
   * The Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * LeadershipBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   */
  public function __construct(
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection
  ) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_leadership_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $campaignId = NULL) {
    // Disable caching on this form.
    $form_state->setCached(FALSE);

    // Get all branches.
    $branches = $this->getBranches($campaignId);

    $selectedBranch = key($branches);
    if (!empty($form_state->getValue('branch'))) {
      $selectedBranch = $form_state->getValue('branch');
    }
    else {
      // Get current user branch.
      if (MemberCampaign::isLoggedIn($campaignId)) {
        $userData = MemberCampaign::getMemberCampaignData($campaignId);
        $member = Member::load($userData['member_id']);
        $selectedBranch = $member->getBranchId();
      }
    }

    $form['filters'] = [
      '#type' => 'container',
      '#prefix' => '<div class="leadership-filters">',
      '#suffix' => '</div>',
    ];
    $form['filters']['branch'] = [
      '#type' => 'select',
      '#title' => 'Location',
      '#options' => $branches,
      '#default_value' => $selectedBranch,
      '#ajax' => [
        'callback' => '::ajaxLeadershipCallback',
        'wrapper' => 'leadership-block-wrapper',
      ],
      '#prefix' => '<div class="leadership-branches-select">',
      '#suffix' => '</div>',
    ];

    $activities = $this->getActivities($campaignId);

    $selectedActivity = key($activities);
    if (!empty($form_state->getValue('activity'))) {
      $selectedActivity = $form_state->getValue('activity');
    }

    $form['filters']['activity'] = [
      '#type' => 'select',
      '#title' => 'Activity',
      '#options' => $activities,
      '#default_value' => $selectedActivity,
      '#ajax' => [
        'callback' => '::ajaxLeadershipCallback',
        'wrapper' => 'leadership-block-wrapper',
      ],
      '#prefix' => '<div class="leadership-activities-select" id="leadership-activities-select">',
      '#suffix' => '</div>',
    ];

    $leadershipBlock = '';
    if (!empty($selectedBranch) && !empty($selectedActivity)) {
      $leadershipBlock = $this->showLeadershipBlock($campaignId, $selectedBranch, $selectedActivity);
    }

    $form['leadership'] = [
      '#prefix' => '<div id="leadership-block-wrapper">',
      '#suffix' => '</div>',
      '#markup' => $leadershipBlock,
    ];

    return $form;
  }

  /**
   * Render Leadership Block.
   *
   * @param $campaignId
   * @param $branchId
   * @param $activityId
   *
   * @return \Drupal\Component\Render\MarkupInterface
   * @throws \Exception
   */
  public function showLeadershipBlock($campaignId, $branchId, $activityId) {
    $leaders = $this->getCampaignLeadership($campaignId, $branchId, $activityId);
    if (!empty($leaders)) {
      $output = [
        '#theme' => 'openy_campaign_leadership',
        '#leaders' => $leaders,
      ];
    }
    else {
      $config = $this->config('openy_campaign.general_settings');
      $output = [
        '#prefix' => '<div class="leadership-no-results">',
        '#markup' => $config->get('activities_count_no_results_message'),
        '#suffix' => '</div>',
      ];
    }

    $render = $this->renderer->render($output);

    return $render;
  }

  /**
   * AJAX Callback for the leadership list.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The portion of the render structure that will replaced.
   */
  public function ajaxLeadershipCallback(array $form, FormStateInterface $form_state) {
    return $form['leadership'];
  }

  /**
   * Get all available branches.
   *
   * @param int $campaignId
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getBranches($campaignId) {
    $locations = [];

    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $nids = $query->condition('type', 'branch')
      ->condition('status', '1')
      ->sort('title', 'ASC')
      ->execute();
    $branches = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    // Get list of branches related to the Campaign.
    $campaign = NULL;
    $campaignBranches = [];
    if (!empty($campaignId)) {
      /** @var \Drupal\node\Entity\Node $campaign Campaign node. */
      $campaign = $this->entityTypeManager->getStorage('node')->load($campaignId);
      $branchesField = $campaign->get('field_campaign_branch_target')->getValue();
      foreach ($branchesField as $branchItem) {
        $campaignBranches[] = $branchItem['target_id'];
      }
    }
    /** @var \Drupal\node\Entity\Node $branch */
    foreach ($branches as $branch) {
      if (!empty($campaignBranches) && !in_array($branch->id(), $campaignBranches)) {
        continue;
      }
      $locations[$branch->id()] = $branch->getTitle();
    }

    return $locations;
  }

  /**
   * Get all categories of the campaign.
   *
   * @param int $campaignId
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getCategories($campaignId) {
    $categories = [];

    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = Node::load($campaignId);

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $campaign->field_campaign_fitness_category->entity;
    /** @var \Drupal\taxonomy\VocabularyStorageInterface $vocabularyStorage */
    $vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $tids = $vocabularyStorage->getToplevelTids([$vocabulary->id()]);

    $terms = Term::loadMultiple($tids);
    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach ($terms as $term) {
      $categories[$term->id()] = $term->getName();
    }
    return $categories;
  }

  /**
   * Get activities by the category.
   *
   * @param int $campaignId
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getActivities($campaignId) {
    $activities = [];

    $categories = $this->getCategories($campaignId);

    foreach (array_keys($categories) as $categoryId) {
      $topTerm = Term::load($categoryId);

      $terms = $this->entityTypeManager->getStorage("taxonomy_term")->loadTree($topTerm->getVocabularyId(), $categoryId, 1, TRUE);
      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($terms as $term) {
        if (!$term->field_enable_activities_counter->value) {
          continue;
        }
        $activities[$term->id()] = $term->getName();
      }
    }
    asort($activities);

    return $activities;
  }

  /**
   * Get all leaders of current Campaign by branch and activity.
   *
   * @param $campaignId
   * @param $branchId
   * @param $activityId
   *
   * @return array
   */
  private function getCampaignLeadership($campaignId, $branchId, $activityId) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_memb_camp_actv', 'mca');
    $query->join('openy_campaign_member_campaign', 'mc', 'mc.id = mca.member_campaign');
    $query->join('openy_campaign_member', 'm', 'm.id = mc.member');

    $query->condition('mca.activity', $activityId);
    $query->condition('mc.campaign', $campaignId);
    $query->condition('m.branch', $branchId);
    $query->condition('m.is_employee', FALSE);

    $query->fields('m', ['id', 'first_name', 'last_name', 'membership_id']);
    $query->addField('mc', 'id', 'member_campaign');
    $query->addExpression('SUM(mca.count)', 'total');

    $query->groupBy('mc.id');
    $query->groupBy('m.id');
    $query->groupBy('m.first_name');
    $query->groupBy('m.last_name');
    $query->groupBy('m.membership_id');

    $query->having('SUM(mca.count) > 0');

    $query->orderBy('total', 'DESC');

    $query->range(0, static::MAX_LEADERS);

    $results = $query->execute()->fetchAll();

    $leaders = [];
    $rank = 1;
    foreach ($results as $item) {
      $lastNameLetter = !empty($item->last_name) ? ' ' . strtoupper($item->last_name[0]) : '';

      $leaders[] = [
        'rank' => $rank,
        'member_id' => $item->id,
        'member_campaign_id' => $item->member_campaign,
        'total' => floatval($item->total),
        'name' => $item->first_name . $lastNameLetter,
        'membership_id' => substr($item->membership_id, -4),
      ];
      $rank++;
    }

    return $leaders;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
