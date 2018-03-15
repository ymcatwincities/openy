<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
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

    // Get all activities categories.
    $categories = $this->getCategories($campaignId);

    $selectedBranch = !empty($form_state->getValue('branch')) ? $form_state->getValue('branch') : $branches['default'];
    $form['branch'] = [
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

    $selectedCategory = !empty($form_state->getValue('category')) ? $form_state->getValue('category') : NULL ;
    $form['category'] = [
      '#type' => 'select',
      '#title' => 'Category',
      '#options' => $categories,
      '#default_value' => $selectedCategory ?? $categories['default'],
      '#ajax' => [
        'callback' => '::ajaxLeadershipCallback',
        'wrapper' => 'leadership-block-wrapper',
      ],
      '#prefix' => '<div class="leadership-categories-select">',
      '#suffix' => '</div>',
    ];

    // Get all activities.
    $activities = $this->getActivities($selectedCategory != 'default' ? $selectedCategory : NULL);

    $selectedActivity = !empty($form_state->getValue('activity')) ? $form_state->getValue('activity') : $activities['default'];
    $form['activity'] = [
      '#type' => 'select',
      '#title' => 'Activity',
      '#options' => $activities,
      '#default_value' => $selectedActivity,
      '#ajax' => [
        'callback' => '::ajaxLeadershipCallback',
        'wrapper' => 'leadership-block-wrapper',
      ],
      '#prefix' => '<div class="leadership-activities-select">',
      '#suffix' => '</div>',
    ];

    $leadershipBlock = '';
    if (
      (!empty($form_state->getValue('branch')) && $form_state->getValue('branch') != 'default') &&
      (!empty($form_state->getValue('activity')) && $form_state->getValue('activity') != 'default')
    ) {
      $branchId = $form_state->getValue('branch');
      $activityId = $form_state->getValue('activity');
      $leadershipBlock = $this->showLeadershipBlock($campaignId, $branchId, $activityId);
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
    $output = [
      '#theme' => 'openy_campaign_leadership',
      '#leaders' => $leaders,
    ];

    $render = $this->renderer->render($output);

    return $render;
  }

  /**
   * AJAX Callback for the leadership list.
   */
  public function ajaxLeadershipCallback($form, $form_state) {
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
    $locations = [
      'default' => $this->t('Location'),
    ];

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
    $categories = [
      'default' => $this->t('Category'),
    ];

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
   * @param int $categoryId
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getActivities($categoryId = NULL) {
    $activities = [
      'default' => $this->t('Activity'),
    ];

    if (empty($categoryId)) {
      return $activities;
    }

    $topTerm = Term::load($categoryId);

    $terms = $this->entityTypeManager->getStorage("taxonomy_term")->loadTree($topTerm->getVocabularyId(), $categoryId, 1, TRUE);
    /** @var \Drupal\taxonomy\Entity\Term $childTerm */
    foreach ($terms as $term) {
      $activities[$term->id()] = $term->getName();
    }

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

    $query->groupBy('member_campaign');

    $query->orderBy('total', 'DESC');

    $results = $query->execute()->fetchAll();

    $leaders = [];
    $place = 1;
    foreach ($results as $item) {
      $lastNameLetter = !empty($item->last_name) ? ' ' . strtoupper($item->last_name[0]) : '';

      $leaders[$place][] = [
        'member_id' => $item->id,
        'member_campaign_id' => $item->member_campaign,
        'total' => $item->total,
        'name' => $item->first_name . $lastNameLetter,
        'membership_id' => substr($item->membership_id, -4),
      ];
      $place++;
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
    // TODO: Implement submitForm() method.
  }
}
