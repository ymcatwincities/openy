<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberCheckin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\openy_campaign\Entity\MemberCampaignActivity;
/**
 * Provides a 'Activity Visits Tracking' block.
 *
 * @Block(
 *   id = "campaign_activity_visits_block",
 *   admin_label = @Translation("Campaign Activity Visits"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampaignActivityVisitsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder, CampaignMenuServiceInterface $campaign_menu_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
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
      $container->get('openy_campaign.campaign_menu_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];

    // The block is rendered for each user separately.
    // It should be invalidated when a new visit is added.
    $block['#cache'] = [
      'max-age' => 3600,
    ];

    // Get campaign node from current page URL.
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();
    if (empty($campaign)) {
      return $block;
    }

    // Get all enabled activities list.
    $activitiesOptions = openy_campaign_get_enabled_activities($campaign);

    $enableVisitsGoal = in_array('field_prgf_activity_visits', $activitiesOptions);

    if ($enableVisitsGoal && MemberCampaign::isLoggedIn($campaign->id())) {
      // Show Visits goal block.
      $userData = MemberCampaign::getMemberCampaignData($campaign->id());
      $memberCampaignId = MemberCampaign::findMemberCampaign($userData['membership_id'], $campaign->id());
      $memberCampaign = MemberCampaign::load($memberCampaignId);

      $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
      $campaignStartDate->setTime(0, 0, 0);
      $yesterday = new \DateTime();
      $yesterday->sub(new \DateInterval('P1D'))->setTime(23, 59, 59);
      $currentCheckins = MemberCheckin::getFacilityCheckIns($userData['member_id'], $campaignStartDate, $yesterday);

      // Get default values from settings.
      $config = \Drupal::config('openy_campaign.general_settings');

      $msgMyVisits = $config->get('track_activity_my_visits');
      $msgMyVisits = check_markup($msgMyVisits['value'], $msgMyVisits['format']);

      $goal = 0;
      if (!empty($memberCampaign)) {
        $goal = (int) $memberCampaign->getGoal();
      }

      if ($campaign->get('field_enable_activities_counter')->value) {
        $desc = $campaign->get('field_tracking_actv_goal_desc')->value;
      }

      $countedActivities = MemberCampaignActivity::getTrackedActivities($memberCampaignId);

      // Create a cache for each member separately.
      $block['#cache'] = [
        'tags' => ['member_campaign:' . $memberCampaignId],
        'max-age' => 86400,
      ];

      $block['goal_block'] = [
        '#theme' => 'openy_campaign_visits_goal',
        '#enActvCounter' => $campaign->get('field_enable_activities_counter')->value,
        '#goal' => $goal,
        '#goal_message' => $msgMyVisits,
        '#trackActvDesc' => $desc,
        '#countedActv' => $countedActivities,
        '#current' => count($currentCheckins),
      ];
    }

    return $block;
  }

}
