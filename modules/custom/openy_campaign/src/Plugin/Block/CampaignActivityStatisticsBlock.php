<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberCampaignActivity;
use Drupal\openy_campaign\Entity\MemberCheckin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;

/**
 * Provides a 'Activity Tracking' block.
 *
 * @Block(
 *   id = "campaign_activity_statistics_block",
 *   admin_label = @Translation("Campaign Activity Tracking"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampaignActivityStatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
   * @param \Drupal\openy_campaign\CampaignMenuServiceInterface $campaign_menu_service
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $formBuilder,
    CampaignMenuServiceInterface $campaign_menu_service,
    EntityTypeManagerInterface $entity_type_manager,
    TimeInterface $time,
    Connection $connection
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
    $this->campaignMenuService = $campaign_menu_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
    $this->connection = $connection;
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
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // The block is rendered for each user separately.
    // It should be invalidated when user tracks the activity.
    $block['#cache'] = [
      'max-age' => 3600,
    ];

    // Get campaign node from current page URL.
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();

    if (empty($campaign) || !MemberCampaign::isLoggedIn($campaign->id())) {
      return $block;
    }

    $campaignId = $campaign->id();

    $memberCampaignData = MemberCampaign::getMemberCampaignData($campaignId);
    $membershipId = $memberCampaignData['membership_id'];

    // Get MemberCampaign ID.
    $memberCampaignId = MemberCampaign::findMemberCampaign($membershipId, $campaignId);
    if (!$memberCampaignId) {
      return [];
    }

    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $memberCheckinStorage = $this->entityTypeManager->getStorage('openy_campaign_member_checkin');
    /** @var \Drupal\taxonomy\VocabularyStorageInterface $vocabularyStorage */
    $vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');

    $activities = [];
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $nodeStorage->load($campaignId);

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $campaign->field_campaign_fitness_category->entity;
    $tids = $vocabularyStorage->getToplevelTids([$vocabulary->id()]);
    $terms = $termStorage->loadMultiple($tids);

    /** @var \DateTime $start */
    $start = $campaign->field_campaign_start_date->date;

    // Reset time to include the current day to the list.
    $start->setTime(0, 0, 0);

    /** @var \DateTime $end */
    $end = $campaign->field_campaign_end_date->date;

    if (empty($start) || empty($end)) {
      drupal_set_message('Start or End dates are not set for campaign.', 'error');
      return [
        'message' => [
          '#markup' => '[ Placeholder for Activity Tracking block ]',
        ],
      ];
    }

    $facilityCheckInIds = MemberCheckin::getFacilityCheckIns($memberCampaignData['member_id'], $start, $end);
    $checkinRecords = [];

    foreach ($memberCheckinStorage->loadMultiple($facilityCheckInIds) as $checkIn) {
      $checkInDate = new \DateTime('@' . $checkIn->date->value);
      $checkinRecords[$checkInDate->format('Y-m-d')] = $checkInDate->format('Y-m-d');
    }

    $stopper = 0;
    while ($end->format('U') > $start->format('U') && $stopper < 100) {
      $key = $start->format('Y-m-d');

      $disabled = FALSE;
      if ($this->time->getRequestTime() < $start->format('U')) {
        $disabled = TRUE;
      }

      if (isset($checkinRecords[$key])) {
        $activities[$key]['checkin'] = [
          '#markup' => 'checked in',
        ];
      }

      /**
       * @var int $tid Term ID
       * @var \Drupal\taxonomy\Entity\Term $term
       */
      foreach ($terms as $tid => $term) {

        $childTerms = $termStorage->loadTree($vocabulary->id(), $tid, 1, TRUE);
        $childTermIds = [];
        /** @var \Drupal\taxonomy\Entity\Term $childTerm */
        foreach ($childTerms as $childTerm) {
          $childTermIds[] = $childTerm->id();
        }

        $date = new \DateTime($key);
        $activityIds = MemberCampaignActivity::getExistingActivities($memberCampaignId, $date, $childTermIds);

        $name = $term->getName();
        $cleanName = $name;
        if (!empty($activityIds)) {
          $name .= ' x ' . count($activityIds);
        }

        if ($disabled) {
          $activities[$key][$tid] = [
            '#markup' => '<div class="btn btn-primary" disabled="disabled">' . SafeMarkup::checkPlain($name) . '</div>'
          ];
        }
        else {
          $form_class = ('Drupal\openy_campaign\Form\ActivityTrackingModalForm');

          $activityTrackingForm = $this->formBuilder->getForm(
            $form_class,
            $key,
            $memberCampaignId,
            $tid
          );

          $activities[$key][$tid] = $activityTrackingForm;

          // Get term icon.
          $iconUri = $term->field_activity_icon->entity;
          $addIconAttr = '';
          if (!empty($iconUri)) {
            $relativeUrl = file_url_transform_relative(file_create_url($iconUri->getFileUri()));
            $addIconAttr = ' data-icon="' . $relativeUrl . '"';
          }

          $preparedClass = str_replace([' ','!','"','#','$','%','&','\\',"'",'(',')','*','+',',','.','/',':',';','<','=','>','?','@','[',']','^','`','{','|','}','~'], '', $cleanName);
          $activities[$key][$tid]['#prefix'] .= '<span class="activity-name ' . $preparedClass . '"' . $addIconAttr . '>' . $cleanName . '</span>';
        }
      }

      $start->modify('+1 day');
    }

    // Create a cache for each member separately.
    $block['#cache'] = [
      'tags' => ['member_campaign:' . $memberCampaignId],
      'max-age' => 3600,
    ];

    $block['#activities'] = $activities;

    $enabled_activities = openy_campaign_get_enabled_activities($campaign);
    $global_campaign = in_array('field_prgf_campaign_global_goal', $enabled_activities);
    if ($global_campaign) {
      $current = $this->getUserProgress($campaignId, $memberCampaignId);
      $global_goal = $campaign->field_campaign_global_goal->value;
      $block['#progress'] = [
        'current' => $current,
        'goal' => $global_goal,
        'percent' => round(100 * $current / $global_goal),
      ];
    }
    $block['#theme'] = 'openy_campaign_activity_block';

    $block['#attached'] = [
      'library' => [
        'openy_campaign/activityTracking'
      ],
    ];

    return $block;
  }

  /**
   * Get all leaders of current Campaign by branch and activity.
   *
   * @param $campaign_id
   * @param $member_campaign_id
   *
   * @return array
   */
  private function getUserProgress($campaign_id, $member_campaign_id) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_memb_camp_actv', 'mca');
    $query->join('openy_campaign_member_campaign', 'mc', 'mc.id = mca.member_campaign');
    $query->join('openy_campaign_member', 'm', 'm.id = mc.member');

    $query->condition('mc.campaign', $campaign_id);
    $query->condition('mc.id', $member_campaign_id);

    $query->groupBy('mc.id');

    $query->leftJoin('taxonomy_term__field_global_goal_activity_worth', 'aw', 'aw.entity_id = mca.activity');
    $query->addExpression('SUM(aw.field_global_goal_activity_worth_value)', 'total');
    $query->having('SUM(aw.field_global_goal_activity_worth_value) > 0');

    $query->orderBy('total', 'DESC');

    $total = $query->execute()->fetchField();

    return floatval($total);
  }

}
