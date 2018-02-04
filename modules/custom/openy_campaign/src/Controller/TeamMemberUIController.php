<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\Core\Url;

/**
 * Class TeamMemberUIController to show page with UI for Team members.
 */
class TeamMemberUIController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Campaign menu service.
   *
   * @var \Drupal\openy_campaign\CampaignMenuServiceInterface
   */
  protected $campaignMenuService;

  /**
   * ViewExecutableFactory service.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewsExecutableFactory;

  /**
   * Team Member list constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\openy_campaign\CampaignMenuServiceInterface $campaign_menu_service
   *   The Campaign menu service.
   * @param \Drupal\views\ViewExecutableFactory $views_executable_factory
   *   ViewExecutableFactory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
    CampaignMenuServiceInterface $campaign_menu_service,
    ViewExecutableFactory $views_executable_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->campaignMenuService = $campaign_menu_service;
    $this->viewsExecutableFactory = $views_executable_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('views.executable')
    );
  }

  /**
   * Render view block to show all members table.
   *
   * @return array Render array
   */
  public function showMembers() {
    $entityView = $this->entityTypeManager->getStorage('view')->load('campaign_members');
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $this->viewsExecutableFactory->get($entityView);
    $view->setDisplay('members_list_block');

    $campaigns = $this->campaignMenuService->getActiveCampaigns();
    if (empty($view->getExposedInput()) && !empty($campaigns)) {
      $defaultCampaign = current($campaigns);
      $view->setExposedInput(['campaign' => $defaultCampaign->id()]);
    }

    $build = [
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Registration Portal >>>'),
        '#url' => Url::fromRoute('openy_campaign.member-registration-portal'),
        '#attributes' => [
          'class' => [
            'align-right',
          ],
        ],
        '#prefix' => '<div class="row">',
        '#suffix' => '</div>',
      ],
      'view' => $view->render(),
    ];

    return $build;
  }

}
