<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\CampaignUtilizationActivitiy;
use Drupal\openy_campaign\Entity\MemberCampaign;
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
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder, $request_stack) {
    $this->formBuilder = $formBuilder;
    $this->request_stack = $request_stack;
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
      $container->get('request_stack')
    );
  }

  /**
   * Save user's activity tracking.
   *
   * @param $visit_date
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function saveTrackingInfo($visit_date) {
    $params = $this->request_stack->getCurrentRequest()->request->all();

    $dateRoute = \DateTime::createFromFormat('Y-m-d', $visit_date);
    $date = new \DateTime($dateRoute->format('d-m-Y'));
    $activityIds = $params['activities'];
    $activities_count = $params['activities_count'] ?? [];

    $memberCampaignId = $params['member_campaign_id'];

    // Delete all records first.
    $existingActivityIds = MemberCampaignActivity::getExistingActivities($memberCampaignId, $date);

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
      // If Activity Counter is disabled save zero value.
      if ($campaign->field_enable_activities_counter->value) {
        $activityCount = $activities_count[$activityTermId] ?? 0.0;
      }
      else {
        $activityCount = 0.0;
      }

      // To prevent duplicate activities creation we need to check
      // if the activity was not created earlier.
      $query = \Drupal::entityQuery('openy_campaign_memb_camp_actv')
        ->condition('member_campaign', $memberCampaignId)
        ->condition('activity', $activityTermId)
        ->condition('date', $date->format('U'))
        ->execute();
      if (!empty($query)) {
        continue;
      }

      $preparedData = [
        'created' => time(),
        'date' => $date->format('U'),
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
