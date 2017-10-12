<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
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

  public function saveTrackingInfo($visit_date) {
    $params = $this->request_stack->getCurrentRequest()->request->all();
    $memberCampaignId = 1;
    $dateRoute = \DateTime::createFromFormat('Y-m-d', $visit_date);
    $date =  new \DateTime($dateRoute->format('d-m-Y'));
    $activityIds = $params['activities'];

    $userData = MemberCampaign::getMemberCampaignData(71);
    $memberCampaignId = 1;

    // Delete all records first.
    $existingActivityIds = MemberCampaignActivity::getExistingActivities($memberCampaignId, $date, array_values($activityIds));

    entity_delete_multiple('openy_campaign_memb_camp_actv', $existingActivityIds);

    // Save new selection.
    $activityIds = array_filter($activityIds);


    foreach ($activityIds as $activityTermId) {
      $preparedData = [
        'created' => time(),
        'date' => $date->format('U'),
        'member_campaign' => $memberCampaignId,
        'activity' => $activityTermId,
      ];

      $activity = MemberCampaignActivity::create($preparedData);

      $activity->save();
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
    // If member logged in
    if (MemberCampaign::isLoggedIn($campaign->id())) {
      // Add an AJAX command to open a modal dialog with the form as the content.
      $response->addCommand(new OpenModalDialogCommand(t('Track activity'), $activityTrackingModalForm, ['width' => '800']));
    }

    return $response;
  }
}
