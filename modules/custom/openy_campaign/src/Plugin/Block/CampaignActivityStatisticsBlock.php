<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberCampaignActivity;
use Drupal\openy_campaign\Entity\MemberCheckin;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder, CampaignMenuServiceInterface $campaign_menu_service, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
    $this->campaignMenuService = $campaign_menu_service;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // The block is rendered for each user separately.
    // We can't cache it.
    $block['#cache']['max-age'] = 0;

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

    $activities = [];
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = Node::load($campaignId);

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $campaign->field_campaign_fitness_category->entity;
    /** @var \Drupal\taxonomy\VocabularyStorageInterface $vocabularyStorage */
    $vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $tids = $vocabularyStorage->getToplevelTids([$vocabulary->id()]);

    $terms = Term::loadMultiple($tids);

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

    foreach (MemberCheckin::loadMultiple($facilityCheckInIds) as $checkIn) {
      $checkInDate = new \DateTime('@' . $checkIn->date->value);
      $checkinRecords[$checkInDate->format('Y-m-d')] = $checkInDate->format('Y-m-d');
    }

    $stopper = 0;
    while ($end->format('U') > $start->format('U') && $stopper < 100) {
      $key = $start->format('Y-m-d');

      $disabled = FALSE;
      if (\Drupal::time()->getRequestTime() < $start->format('U')) {
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

        $childTerms = $this->entityTypeManager->getStorage("taxonomy_term")->loadTree($vocabulary->id(), $tid, 1, TRUE);
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

          $activityTrackingForm = \Drupal::formBuilder()->getForm(
            $form_class,
            $key,
            $memberCampaignId,
            $tid
          );

          $activities[$key][$tid] = $activityTrackingForm;

          // Get term icon
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

    $block['#activities'] = $activities;
    $block['#theme'] = 'openy_campaign_activity_block';

    $block['#attached'] = [
      'library' => [
        'openy_campaign/activityTracking'
      ],
    ];

    return $block;
  }

}
