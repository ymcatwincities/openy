<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberCheckin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;

/**
 * Provides a 'Activity Tracking' block.
 *
 * @Block(
 *   id = "campaign_activity_block",
 *   admin_label = @Translation("Campaign Activity Tracking"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampaignGameBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The Campaign menu service.
   *
   * @var \Drupal\openy_campaign\CampaignMenuServiceInterface
   */
  protected $campaignMenuService;

  /**
   * Constructs a new Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder, RouteMatchInterface $route_match, CampaignMenuServiceInterface $campaign_menu_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
    $this->routeMatch = $route_match;
    $this->campaignMenuService = $campaign_menu_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('current_route_match'),
      $container->get('openy_campaign.campaign_menu_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];
    $block['#cache']['max-age'] = 0;


    // get current member
    // get current campaign
    // check number of games available
    // show form with button
    // on submit, direct to game page


    // Get campaign node from current page
    /** @var \Drupal\Node\Entity\Node $campaign */
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface !== TRUE) {
      $node = Node::load($node);
    }
    if (empty($node)) {
      return [];
    }
    $campaign = $this->campaignMenuService->getNodeCampaignNode($node);

    if (empty($campaign) || $campaign->getType() != 'campaign') {
      return $block;
    }

    $userData = MemberCampaign::getMemberCampaignData($campaign->id());
    $memberCampaignID = MemberCampaign::findMemberCampaign($userData['membership_id'], $campaign->id());

    $games = \Drupal::entityQuery('openy_campaign_member_game')
      ->condition('member', $memberCampaignID)
      ->condition('result', '')
      ->execute();

//    $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
//    $campaignStartDate->setTime(0, 0, 0);
//    $yesterday = new \DateTime();
//    $yesterday->sub(new \DateInterval('P1D'))->setTime(23, 59, 59);
//    $currentCheckins = MemberCheckin::getFacilityCheckIns($userData['member_id'], $campaignStartDate, $yesterday);

    $block['games'] = [
      '#markup' => var_export($games, TRUE),
    ];

//    $block['goal_block'] = [
//      '#theme' => 'openy_campaign_visits_goal',
//      '#goal' => $memberCampaign->getGoal(),
//      '#current' => count($currentCheckins),
//    ];
//
//    // Show Activity block form
//    $form = $this->formBuilder->getForm('Drupal\openy_campaign\Form\ActivityBlockForm', $campaign->id());
//    $block['form'] = $form;

    return $block;
  }

}
