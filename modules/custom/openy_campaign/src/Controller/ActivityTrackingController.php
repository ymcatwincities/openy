<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\CampaignUtilizationActivitiy;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBuilder;
use Drupal\openy_campaign\Entity\MemberCampaignActivity;

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
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(FormBuilder $formBuilder, $request_stack, Connection $connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->formBuilder = $formBuilder;
    $this->request_stack = $request_stack;
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
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
    $params = $this->request_stack->getCurrentRequest()->request->all();

    $config = $this->config('openy_campaign.general_settings');
    $activitiesCountMaxPerEntry = $config->get('activities_count_max_per_entry');
    $activitiesCountMaxPerActivity = $config->get('activities_count_max_per_activity');


    $dateRoute = \DateTime::createFromFormat('Y-m-d', $visit_date);
    $date = new \DateTime($dateRoute->format('d-m-Y'));
    $dateStamp = $date->format('U');
    $activityIds = $params['activities'];
    $activities_count = $params['activities_count'] ?? [];

    $memberCampaignId = $params['member_campaign_id'];
    $topTermId = $params['top_term_id'];

    $term = Term::load($topTermId);
    $childTerms = $this->entityTypeManager->getStorage("taxonomy_term")->loadTree($term->getVocabularyId(), $topTermId, 1, TRUE);
    $activityTerms = [];
    /** @var \Drupal\taxonomy\Entity\Term $term */
    foreach ($childTerms as $term) {
      $activityTerms[] = $term->id();
    }

    // Delete all records first.
    $existingActivityIds = MemberCampaignActivity::getExistingActivities($memberCampaignId, $date, $activityTerms);

    entity_delete_multiple('openy_campaign_memb_camp_actv', $existingActivityIds);

    $memberCampaign = MemberCampaign::load($memberCampaignId);
    $campaignId = $memberCampaign->getCampaign()->id();
    $campaign = Node::load($campaignId);
    $utilizationActivities = $campaign->get('field_utilization_activities')->getValue();
    $allowedActivities = [];
    foreach ($utilizationActivities as $utilizationActivity) {
      $allowedActivities[] = $utilizationActivity['value'];
    }
    $saveUtilizationActivity = in_array('tracking', $allowedActivities);
    $utilizationActivitySaved = FALSE;

    // Save new selection.
    $activityIds = array_filter($activityIds);

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
      $query = \Drupal::entityQuery('openy_campaign_memb_camp_actv')
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

      $activity = MemberCampaignActivity::create($preparedData);
      $activity->save();

      // Mark user for activate utilization activity.
      if ($saveUtilizationActivity && !$utilizationActivitySaved) {
        $loadedEntity = \Drupal::entityQuery('openy_campaign_util_activity')
          ->condition('member_campaign', $memberCampaignId)
          ->execute();

        if (empty($loadedEntity)) {
          $preparedActivityData = [
            'member_campaign' => $memberCampaignId,
            'created' => time(),
            'activity_type' => 'tracking'
          ];
          $campaignUtilizationActivity = CampaignUtilizationActivitiy::create($preparedActivityData);
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

    // Get the modal form using the form builder.
    $activityTrackingModalForm = $this->formBuilder->getForm('Drupal\openy_campaign\Form\ActivityTrackingModalForm', $visit_date, $member_campaign_id, $top_term_id);

    $memberCampaign = MemberCampaign::load($member_campaign_id);
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
