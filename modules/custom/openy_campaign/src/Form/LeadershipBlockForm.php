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
use Drupal\openy_campaign\CampaignExtendedRegistrationService;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

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
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $taxonomyStorage;

  /**
   * The Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Extended Registration service.
   *
   * @var \Drupal\openy_campaign\CampaignExtendedRegistrationService
   */
  protected $extendedRegistrationService;

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * LeadershipBlockForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\openy_campaign\CampaignExtendedRegistrationService
   *   Extended Registration service.
   * @param \Drupal\Core\Form\FormBuilderInterface
   *   Form builder service.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function __construct(
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    CampaignExtendedRegistrationService $extended_registration_service,
    FormBuilderInterface $formBuilder
  ) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->taxonomyStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->connection = $connection;
    $this->extendedRegistrationService = $extended_registration_service;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('openy_campaign.extended_registration'),
      $container->get('form_builder')
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

    $campaign = $this->nodeStorage->load($campaignId);
    $extended_registration = $campaign->field_campaign_ext_registration->value;

    $form['#prefix'] = '<div id="leadership-form">';
    $form['#suffix'] = '</div>';
    $form['filters'] = [
      '#type' => 'container',
      '#prefix' => '<div class="leadership-filters">',
      '#suffix' => '</div>',
    ];

    if ($extended_registration) {
      $where_are_you_from_options = $this->extendedRegistrationService->getWhereAreYouFromOptions();
      $selected_where_are_you_from = key($where_are_you_from_options);
      if (!empty($form_state->getValue('where_are_you_from'))) {
        $selected_where_are_you_from = $form_state->getValue('where_are_you_from');
      }
      $form['filters']['where_are_you_from'] = [
        '#type' => 'select',
        '#title' => t('Choose which Group to participate in'),
        '#options' => $where_are_you_from_options,
        '#default_value' => $selected_where_are_you_from,
        '#ajax' => [
          'callback' => '::ajaxLeadershipCallback',
          'wrapper' => 'leadership-form',
        ],
        '#prefix' => '<div class="leadership-branches-select">',
        '#suffix' => '</div>',
      ];

      $where_are_you_from_specify_options = $this->extendedRegistrationService->getWhereAreYouFromSpecifyOptions($selected_where_are_you_from);
      $selected_where_are_you_from_specify = key($where_are_you_from_specify_options);
      if (
        !empty($form_state->getValue('where_are_you_from_specify')) &&
        isset($where_are_you_from_specify_options[$form_state->getValue('where_are_you_from_specify')])
      ) {
        $selected_where_are_you_from_specify = $form_state->getValue('where_are_you_from_specify');
      }
      else {
        $form_state->setValue('where_are_you_from_specify', $selected_where_are_you_from_specify);
      }
      $form['filters']['where_are_you_from_specify'] = [
        '#type' => 'select',
        '#title' => t('Choose which Location to participate in'),
        '#options' => $where_are_you_from_specify_options,
        '#default_value' => $selected_where_are_you_from_specify,
        '#ajax' => [
          'callback' => '::ajaxLeadershipCallback',
          'wrapper' => 'leadership-form',
        ],
        '#prefix' => '<div class="leadership-specify-select" id="leadership-specify-select">',
        '#suffix' => '</div>',
      ];
    }
    else {
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
      $form['filters']['branch'] = [
        '#type' => 'select',
        '#title' => t('Location'),
        '#options' => $branches,
        '#default_value' => $selectedBranch,
        '#ajax' => [
          'callback' => '::ajaxLeadershipCallback',
          'wrapper' => 'leadership-form',
        ],
        '#prefix' => '<div class="leadership-branches-select">',
        '#suffix' => '</div>',
      ];
    }

    $enabled_activities = openy_campaign_get_enabled_activities($campaign);
    $global_campaign = in_array('field_prgf_campaign_global_goal', $enabled_activities);
    $global_goal = $campaign->field_campaign_global_goal->value;
    // We need Activities select only for non "Global Goal" types.
    if (!$global_campaign) {
      $activities = $this->getActivities($campaignId);

      $selectedActivity = key($activities);
      if (!empty($form_state->getValue('activity'))) {
        $selectedActivity = $form_state->getValue('activity');
      }

      $form['filters']['activity'] = [
        '#type' => 'select',
        '#title' => t('Activity'),
        '#options' => $activities,
        '#default_value' => $selectedActivity,
        '#ajax' => [
          'callback' => '::ajaxLeadershipCallback',
          'wrapper' => 'leadership-form',
        ],
        '#prefix' => '<div class="leadership-activities-select" id="leadership-activities-select">',
        '#suffix' => '</div>',
      ];
    }

    $leaders = [];
    $page = $form_state->get('page') ? $form_state->get('page') : 0;
    $form_state->set('page', $page);
    if ($extended_registration && !empty($form_state->getValue('where_are_you_from_specify'))) {
      $where_are_you_from_specify = explode('_', $form_state->getValue('where_are_you_from_specify'));
      if (reset($where_are_you_from_specify) == 'node') {
        $leaders = $this->getCampaignLeadership(
          $campaignId,
          NULL,
          end($where_are_you_from_specify),
          !empty($selectedActivity) ? $selectedActivity : NULL,
          $global_campaign,
          $page
        );
      }
      else {
        $leaders = $this->getCampaignLeadership(
          $campaignId,
          end($where_are_you_from_specify),
          NULL,
          !empty($selectedActivity) ? $selectedActivity : NULL,
          $global_campaign,
          $page
        );
      }
    }
    elseif (!empty($selectedBranch) && !empty($selectedActivity)) {
      $leaders = $this->getCampaignLeadership(
        $campaignId,
        NULL,
        $selectedBranch,
        $selectedActivity,
        $global_campaign,
        $page
      );
    }

    $form['leadership'] = [
      '#prefix' => '<div id="leadership-block-wrapper">',
      '#suffix' => '</div>',
      '#markup' => $this->showLeadershipBlock(!empty($leaders['leaders']) ? $leaders['leaders'] : [], $extended_registration),
    ];
    // Build custom pager.
    if (!empty($leaders['count']) && $global_goal) {
      $form['pager'] = [
        '#type' => 'container',
        '#prefix' => '<div class="leadership-pager">',
        '#suffix' => '</div>',
      ];
      if ($page > 0) {
        $form['pager']['previous'] = [
          '#type' => 'submit',
          '#name' => 'previous',
          '#value' => t('Previous page'),
          '#ajax' => [
            'callback' => '::ajaxLeadershipCallback',
            'wrapper' => 'leadership-form',
          ],
        ];
      }
      $last_page = ceil($leaders['count'] / static::MAX_LEADERS) - 1;
      if ($page < $last_page) {
        $form['pager']['next'] = [
          '#type' => 'submit',
          '#name' => 'next',
          '#value' => t('Next page'),
          '#ajax' => [
            'callback' => '::ajaxLeadershipCallback',
            'wrapper' => 'leadership-form',
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * Render Leadership Block.
   *
   * @param array $leaders Leaders list
   * @param bool $extended_registration Extended registration flag
   *
   * @return \Drupal\Component\Render\MarkupInterface
   * @throws \Exception
   */
  public function showLeadershipBlock($leaders, $extended_registration = FALSE) {
    if (!empty($leaders)) {
      $output = [
        '#theme' => 'openy_campaign_leadership',
        '#leaders' => $leaders,
        '#extended_registration' => $extended_registration,
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
   * AJAX Callback for the whole form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The portion of the render structure that will replaced.
   */
  public function ajaxLeadershipCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'previous') {
      $form_state->set('page', $form_state->get('page') - 1);
    }
    elseif ($triggering_element['#name'] == 'next') {
      $form_state->set('page', $form_state->get('page') + 1);
    }
    else {
      $form_state->set('page', 0);
    }
    $new_form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    return $new_form;
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

    $query = $this->nodeStorage->getQuery();
    $nids = $query->condition('type', 'branch')
      ->condition('status', '1')
      ->sort('title', 'ASC')
      ->execute();
    $branches = $this->nodeStorage->loadMultiple($nids);

    // Get list of branches related to the Campaign.
    $campaign = NULL;
    $campaignBranches = [];
    if (!empty($campaignId)) {
      /** @var \Drupal\node\Entity\Node $campaign Campaign node. */
      $campaign = $this->nodeStorage->load($campaignId);
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
    $campaign = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($campaignId);

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $campaign->field_campaign_fitness_category->entity;
    /** @var \Drupal\taxonomy\VocabularyStorageInterface $vocabularyStorage */
    $vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $tids = $vocabularyStorage->getToplevelTids([$vocabulary->id()]);

    $terms = $this->taxonomyStorage->loadMultiple($tids);
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
      $topTerm = $this->taxonomyStorage->load($categoryId);

      $terms = $this->taxonomyStorage->loadTree($topTerm->getVocabularyId(), $categoryId, 1, TRUE);
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
   * @param $whereAreYouFrom
   * @param $branchFacilityId
   * @param $activityId
   * @param bool $global Whether campaign has global goal.
   * @param int $page Pager page.
   *
   * @return array
   */
  private function getCampaignLeadership($campaignId, $whereAreYouFrom = NULL, $branchFacilityId = NULL, $activityId = NULL, $global = FALSE, $page = 0) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_memb_camp_actv', 'mca');
    $query->join('openy_campaign_member_campaign', 'mc', 'mc.id = mca.member_campaign');
    $query->join('openy_campaign_member', 'm', 'm.id = mc.member');

    $query->condition('mc.campaign', $campaignId);
    if (!empty($whereAreYouFrom)) {
      $query->condition('m.where_are_you_from', $whereAreYouFrom);
    }
    if (!empty($branchFacilityId)) {
      $query->condition('m.branch', $branchFacilityId);
    }
    if (!empty($activityId)) {
      $query->condition('mca.activity', $activityId);
    }
    $query->condition('m.is_employee', FALSE);

    $query->fields('m', ['id', 'first_name', 'last_name', 'membership_id']);
    $query->addField('mc', 'id', 'member_campaign');

    $query->groupBy('mc.id');
    $query->groupBy('m.id');
    $query->groupBy('m.first_name');
    $query->groupBy('m.last_name');
    $query->groupBy('m.membership_id');

    if ($global) {
      $query->leftJoin('taxonomy_term__field_global_goal_activity_worth', 'aw', 'aw.entity_id = mca.activity');
      $query->addExpression('SUM(aw.field_global_goal_activity_worth_value)', 'total');
      $query->having('SUM(aw.field_global_goal_activity_worth_value) > 0');
    }
    else {
      $query->addExpression('SUM(mca.count)', 'total');
      $query->having('SUM(mca.count) > 0');
    }

    $query->orderBy('total', 'DESC');

    $count_query = clone $query;
    $count = $count_query->countQuery()->execute()->fetchField();

    if ($global) {
      $query->range($page * static::MAX_LEADERS, static::MAX_LEADERS);
    }
    else {
      $query->range(0, static::MAX_LEADERS);
    }

    $results = $query->execute()->fetchAll();

    $leaders = [];
    $rank = $global ? $page * static::MAX_LEADERS + 1 : 1;
    foreach ($results as $item) {
      $lastNameLetter = !empty($item->last_name) ? ' ' . strtoupper($item->last_name[0]) : '';

      $total = floatval($item->total);

      $leaders[] = [
        'rank' => $rank,
        'member_id' => $item->id,
        'member_campaign_id' => $item->member_campaign,
        'total' => $total,
        'name' => $item->first_name . $lastNameLetter,
        'membership_id' => substr($item->membership_id, -4),
      ];
      $rank++;
    }

    return [
      'leaders' => $leaders,
      'count' => $count,
    ];
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
