<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilder;
use Drupal\openy_campaign\Entity\MemberCampaignActivity;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ActivityTrackingController.
 */
class ActivityTrackingController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request_stack;

  /**
   * The Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cache invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   */
  public function __construct(
    FormBuilder $formBuilder,
    RequestStack $request_stack,
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    CacheTagsInvalidatorInterface $cache_tags_invalidator
  ) {
    $this->formBuilder = $formBuilder;
    $this->request_stack = $request_stack;
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
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
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * Save user's activity tracking.
   *
   * @param $visit_date
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function saveTrackingInfo($visit_date) {
    $memberCampaignActivityStorage = $this->entityTypeManager->getStorage('openy_campaign_memb_camp_actv');
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $memberCampaignStorage = $this->entityTypeManager->getStorage('openy_campaign_member_campaign');
    $utilizationActivityStorage = $this->entityTypeManager->getStorage('openy_campaign_util_activity');
    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $params = $this->request_stack->getCurrentRequest()->request->all();

    $config = $this->config('openy_campaign.general_settings');
    $activitiesCountMaxPerEntry = $config->get('activities_count_max_per_entry');
    $activitiesCountMaxPerActivity = $config->get('activities_count_max_per_activity');

    $dateRoute = \DateTime::createFromFormat('Y-m-d', $visit_date);
    $date = new \DateTime($dateRoute->format('d-m-Y'));
    $dateStamp = $date->format('U');
    if (!array_key_exists('activities', $params)) {
      $params['activities'] = NULL;
    }
    $activityIds = $params['activities'];
    $activities_count = isset($params['activities_count']) ? $params['activities_count'] : [];

    $memberCampaignId = $params['member_campaign_id'];

    // Invalidate all data of the active user.
    $this->cacheTagsInvalidator->invalidateTags(['member_campaign:' . $memberCampaignId]);

    $topTermId = $params['top_term_id'];

    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $termStorage->load($topTermId);
    $childTerms = $termStorage->loadTree($term->getVocabularyId(), $topTermId, 1, TRUE);
    $activityTerms = [];
    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach ($childTerms as $term) {
      $activityTerms[] = $term->id();
    }

    // Delete all records first.
    $existingActivityIds = MemberCampaignActivity::getExistingActivities($memberCampaignId, $date, $activityTerms);

    $entities = $memberCampaignActivityStorage->loadMultiple($existingActivityIds);
    $memberCampaignActivityStorage->delete($entities);

    /** @var MemberCampaign $memberCampaign */
    $memberCampaign = $memberCampaignStorage->load($memberCampaignId);
    $campaignId = $memberCampaign->getCampaign()->id();

    $campaign = $nodeStorage->load($campaignId);

    $utilizationActivities = $campaign->get('field_utilization_activities')->getValue();
    $allowedActivities = [];
    foreach ($utilizationActivities as $utilizationActivity) {
      $allowedActivities[] = $utilizationActivity['value'];
    }
    $saveUtilizationActivity = in_array('tracking', $allowedActivities);
    $utilizationActivitySaved = FALSE;

    // Save new selection.
    if ($activityIds) {
      $activityIds = array_filter($activityIds);
    }
    else {
      $activityIds = [];
    }

    foreach ($activityIds as $activityTermId) {
      $activityCount = 0.0;
      // If Activity Counter is disabled save zero value.
      if ($campaign->field_enable_activities_counter->value) {
        if (
          isset($activities_count[$activityTermId]) &&
          $activities_count[$activityTermId] > 0
        ) {
          $activityCount = $activities_count[$activityTermId];
        }
      }

      // Validate the count value by entry and in total.
      if ($activityCount > $activitiesCountMaxPerEntry) {
        $activityCount = $activitiesCountMaxPerEntry;
      }

      if ($activityCount > 0) {
        /** @var \Drupal\Core\Database\Query\Select $query */
        $query = $this->connection->select('openy_campaign_memb_camp_actv', 'mca');
        $query->condition('mca.member_campaign', $memberCampaignId)
          ->condition('mca.activity', $activityTermId)
          ->condition('mca.date', $dateStamp);
        $query->addExpression('SUM(mca.count)', 'sum_count');

        $sumCount = $query->execute()->fetchField();
        if ($sumCount + $activityCount > $activitiesCountMaxPerActivity) {
          $activityCount = 0.0;
        }
      }

      // To prevent duplicate activities creation we need to check if the activity was not created earlier.
      $query = $memberCampaignActivityStorage->getQuery()
        ->condition('member_campaign', $memberCampaignId)
        ->condition('activity', $activityTermId)
        ->condition('date', $dateStamp)
        ->execute();
      if (!empty($query)) {
        continue;
      }

      $preparedData = [
        'created' => time(),
        'date' => $dateStamp,
        'member_campaign' => $memberCampaignId,
        'activity' => $activityTermId,
        'count' => floatval($activityCount),
      ];

      $activity = $memberCampaignActivityStorage->create($preparedData);
      $activity->save();

      // Mark user for activate utilization activity.
      if ($saveUtilizationActivity && !$utilizationActivitySaved) {
        $loadedEntity = $utilizationActivityStorage->getQuery()
          ->condition('member_campaign', $memberCampaignId)
          ->execute();

        if (empty($loadedEntity)) {
          $preparedActivityData = [
            'member_campaign' => $memberCampaignId,
            'created' => time(),
            'activity_type' => 'tracking'
          ];
          $campaignUtilizationActivity = $utilizationActivityStorage->create($preparedActivityData);
          $campaignUtilizationActivity->save();

          $utilizationActivitySaved = TRUE;
        }
      }
    }
    return new AjaxResponse();

  }

  /**
   * Callback for opening the modal form.
   */
  public function openModalForm($visit_date, $member_campaign_id, $top_term_id) {
    $response = new AjaxResponse();
    $memberCampaignStorage = $this->entityTypeManager->getStorage('openy_campaign_member_campaign');

    // Get the modal form using the form builder.
    $activityTrackingModalForm = $this->formBuilder->getForm('Drupal\openy_campaign\Form\ActivityTrackingModalForm', $visit_date, $member_campaign_id, $top_term_id);

    $memberCampaign = $memberCampaignStorage->load($member_campaign_id);
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $memberCampaign->getCampaign();
    // If member logged in.
    if (MemberCampaign::isLoggedIn($campaign->id())) {
      // Add an AJAX command to open a modal dialog with the form as the content.
      $response->addCommand(new OpenModalDialogCommand(t('Track activity'), $activityTrackingModalForm, ['width' => '800']));
    }

    return $response;
  }

}
