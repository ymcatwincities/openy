<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;


/**
 * Class CampaignController.
 */
class CampaignController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The Campaign menu service.
   *
   * @var \Drupal\openy_campaign\CampaignMenuServiceInterface
   */
  protected $campaignMenuService;

  /**
   * The CampaignController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   * @param CampaignMenuServiceInterface $campaign_menu_service
   *   The Campaign menu service.
   */
  public function __construct(FormBuilder $formBuilder, CampaignMenuServiceInterface $campaign_menu_service) {
    $this->formBuilder = $formBuilder;
    $this->campaignMenuService = $campaign_menu_service;
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
      $container->get('openy_campaign.campaign_menu_handler')
    );
  }

  /**
   * Show needed content on Campaign node
   *
   * @param int $campaign_id Node ID of the current campaign.
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse | \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function showPageContent($campaign_id, $landing_page_id) {
    $is_ajax = \Drupal::request()->isXmlHttpRequest();
    if (!$is_ajax) {
      return new RedirectResponse(Url::fromRoute('entity.node.canonical', ['node' => $campaign_id])->toString());
    }

    $response = new AjaxResponse();

    $campaign = Node::load($campaign_id);
    $fieldMyProgress = $campaign->field_my_progress_page->target_id;
    $isMyProgress = (!empty($fieldMyProgress) && $fieldMyProgress == $landing_page_id);

    // Only for My Progress page. Show modal popup if user is not logged in.
    if ($isMyProgress && !MemberCampaign::isLoggedIn($campaign_id)) {
      // Get the modal form using the form builder.
      $modalPopup = [
        '#theme' => 'openy_campaign_popup',
        '#form' => $this->formBuilder->getForm('Drupal\openy_campaign\Form\MemberLoginForm', $campaign_id, $landing_page_id),
      ];

      $options = [
        'width' => '800',
      ];
      // Add an AJAX command to open a modal dialog with the form as the content.
      $response->addCommand(new OpenModalDialogCommand($this->t('Sign in'), $modalPopup, $options));

      return $response;
    }

    $response = $this->campaignMenuService->ajaxReplaceLandingPage($landing_page_id);

    return $response;
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array Render array
   */
  public function showMembers(NodeInterface $node) {
    $build = [
      'view' => views_embed_view('campaign_members', 'campaign_members_block', $node->id()),
    ];

    return $build;
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function campaignPagesAccess(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('administer retention campaign') && $node->getType() == 'campaign');
  }

}