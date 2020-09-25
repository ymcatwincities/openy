<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Class CampaignMenuService.
 *
 * @package Drupal\openy_campaign
 */
class CampaignMenuService implements CampaignMenuServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new CampaignMenuService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
    ContainerInterface $container,
    RouteMatchInterface $route_match,
    RendererInterface $renderer,
    Connection $connection,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->container = $container;
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
    $this->connection = $connection;
    $this->configFactory = $config_factory;
  }

  /**
   * Get campaign node from current page URL.
   *
   * @return bool|\Drupal\node\NodeInterface
   */
  public function getCampaignNodeFromRoute() {
    $node = $this->routeMatch->getParameter('node');
    // For custom routes - get campaign_id.
    if ($node instanceof NodeInterface !== TRUE) {
      $campaignId = $this->routeMatch->getParameter('campaign_id');
      if (is_null($campaignId)) {
        $campaignId = $this->container->get('request_stack')->getCurrentRequest()->get('campaign_id');
      }
      $node = !empty($campaignId) ? $this->entityTypeManager->getStorage('node')->load($campaignId) : FALSE;
    }
    if (empty($node)) {
      return FALSE;
    }
    /** @var \Drupal\node\NodeInterface $campaign */
    $campaign = $this->getNodeCampaignNode($node);

    return !empty($campaign) && ($campaign->getType() == 'campaign') ? $campaign : FALSE;
  }

  /**
   * Retrieves referenced Campaign node for the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Campaign node or NULL.
   */
  public function getNodeCampaignNode(NodeInterface $node) {
    $campaign = NULL;

    if ($node->getType() == 'campaign') {
      return $node;
    }

    if ($node->getType() == 'campaign_page') {
      $campaign = $node->field_campaign_parent->entity;
      if (!empty($campaign)) {
        return $campaign;
      }
    }

    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Get Campaign node with reference to given Landing page node.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $nodeStorage->getQuery()
      ->condition('status', 1)
      ->condition('type', 'campaign');
    $orGroup = $query->orConditionGroup()
      ->condition('field_campaign_pages', $node->id(), 'IN')
      ->condition('field_pause_landing_page', $node->id());
    $nids = $query->condition($orGroup)->execute();

    /** @var \Drupal\node\NodeInterface $campaign */
    $campaign = $nodeStorage->load(reset($nids));

    return $campaign;
  }

  /**
   * Retrieves Campaign menu for any node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Array of menu links.
   */
  public function getNodeCampaignMenu(NodeInterface $node) {
    if (!($campaign = $this->getNodeCampaignNode($node))) {
      return [];
    }

    return $this->getCampaignNodeCampaignMenu($campaign);
  }

  /**
   * Retrieves Campaign menu for the Campaign CT node.
   *
   * @param \Drupal\node\NodeInterface $campaign
   *   The Campaign node.
   *
   * @return array
   *   Array of menu links.
   */
  private function getCampaignNodeCampaignMenu(NodeInterface $campaign) {
    /** @var \Drupal\node\Entity\Node $campaign */
    if ($campaign->bundle() != 'campaign') {
      return [];
    }

    // Get full menu from the Campaign node.
    $campaignMenu = $campaign->get('field_campaign_menu')->getValue();
    if (!empty($campaignMenu)) {
      $campaignMenu = unserialize($campaign->field_campaign_menu->value);
    }

    if (empty($campaignMenu)) {
      return [];
    }

    /** @var \Drupal\node\Entity\Node $landingPage */
    $landingPage = $this->getActiveCampaignPage($campaign);

    if (empty($landingPage)) {
      return [];
    }

    $links = [];
    foreach ($campaignMenu[$landingPage->id()]['links'] as $k => $link) {
      if (empty($link['page'])) {
        continue;
      }
      $linkPageId = $link['page'][0]['target_id'];

      // Replace the link on the main Campaign page by the Campaign's URL.
      if ($linkPageId == $landingPage->id()) {
        $linkPageId = $campaign->id();
      }
      $needsLogin = $link['logged'] && !MemberCampaign::isLoggedIn($campaign->id());
      $links['campaign_' . $k] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => !$needsLogin
          ? Url::fromRoute('entity.node.canonical', ['node' => $linkPageId])
          : Url::fromRoute('openy_campaign.member-action', ['action' => 'login', 'campaign_id' => $campaign->id()])
        ,
        '#attributes' => [
          'class' => [
            'campaign-page',
            'node-' . $linkPageId,
            'active',
            !$needsLogin ? '' : 'use-ajax login',
          ],
        ],
      ];
    }

    return $links;
  }

  /**
   * Get Campaign page referenced in Campaign node.
   *
   * @param \Drupal\node\NodeInterface $campaign
   *   Campaign node.
   *
   * @return mixed Published campaign page or FALSE if there is no published referenced campaign page.
   */
  public function getActiveCampaignPage($campaign) {
    // Check if Campaign is Paused.
    $isPaused = $campaign->get('field_pause_campaign')->value;
    $fieldPauseLandingPage = $campaign->get('field_pause_landing_page')->getValue();
    $landingPageIds = [];
    if ($isPaused && isset($fieldPauseLandingPage[0]['target_id'])) {
      $landingPageIds[] = $fieldPauseLandingPage[0]['target_id'];
    }
    else {
      $fieldCampaignPages = $campaign->get('field_campaign_pages')->getValue();
      foreach ($fieldCampaignPages as $field) {
        $landingPageIds[] = $field['target_id'];
      }
    }
    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Load Landing page and check if it's published.
    $landingPages = $nodeStorage->loadMultiple($landingPageIds);
    /** @var \Drupal\node\Entity\Node $node */
    foreach ($landingPages as $node) {
      if ($node->isPublished()) {
        $publishedPages[] = $node;
      }
    }

    // If all pages are disabled, show Pause page.
    if (empty($publishedPages) && isset($fieldPauseLandingPage[0]['target_id'])) {
      return $nodeStorage->load($fieldPauseLandingPage[0]['target_id']);
    }

    return reset($publishedPages);
  }

  /**
   * Check permissions of the current page.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Campaign/Campaign Page node.
   *
   * @return bool
   */
  public function checkPermissions($node) {
    switch ($node->getType()) {
      case 'campaign':
        $campaign = $node;
        break;

      case 'campaign_page':
        $campaign = $this->getCampaignNodeFromRoute();
        break;

      default:
        return FALSE;
    }

    // Get full menu from the Campaign node.
    $campaignMenu = $campaign->get('field_campaign_menu')->getValue();
    if (!empty($campaignMenu)) {
      $campaignMenu = unserialize($campaign->field_campaign_menu->value);
    }

    if (empty($campaignMenu)) {
      return FALSE;
    }

    $landingPage = $this->getActiveCampaignPage($campaign);

    // Show Pause page without the permissions check.
    if ($campaign->get('field_pause_campaign')->value) {
      return TRUE;
    }

    if ($node->getType() == 'campaign') {
      $pageId = $landingPage->id();
    }
    else {
      $pageId = $node->id();
    }
    foreach ($campaignMenu[$landingPage->id()]['links'] as $k => $link) {
      if (empty($link['page'])) {
        continue;
      }
      $linkPageId = $link['page'][0]['target_id'];
      if ($linkPageId != $pageId) {
        continue;
      }
      return !$link['logged'] || MemberCampaign::isLoggedIn($campaign->id());

      break;
    }
    return FALSE;
  }

  /**
   * Get all active Campaign nodes.
   */
  public function getActiveCampaigns() {
    // All dates are stored in database in UTC timezone.
    // Get current datetime in UTC timezone.
    $dt = new \DateTime('now', new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONEORAGE_TIMEZONE));
    $now = DrupalDateTime::createFromDateTime($dt);

    /** @var \Drupal\node\NodeStorage $nodeStorage */
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $campaignIds = $nodeStorage->getQuery()
      ->condition('type', 'campaign')
      ->condition('status', TRUE)
      ->condition('field_campaign_end_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=')
      ->sort('created', 'DESC')
      ->execute();
    $campaigns = $nodeStorage->loadMultiple($campaignIds);

    return !empty($campaigns) ? $campaigns : NULL;
  }

  /**
   * Get achieved Visit Goal members by branch.
   *
   * @param \Drupal\node\NodeInterface $campaign
   *   Campaign node entity.
   * @param int $branchId
   *   Optional: calculate winners per branch. Branch ID to calculate winners for.
   * @param array $alreadyWinners
   *   Optional: exclude already defined winners.
   * @param bool $withoutEmployees
   *   Exclude staff from the calculation.
   *
   * @return array
   *   Array with winners MemberCampaign ids.
   */
  public function getVisitsGoalWinners($campaign, $branchId = NULL, $alreadyWinners = [], $withoutEmployees = TRUE) {
    $goalWinners = [];

    // Get all enabled activities list.
    $activitiesOptions = openy_campaign_get_enabled_activities($campaign);

    // For disabled Visits Goal activity.
    if (!in_array('field_prgf_activity_visits', $activitiesOptions)) {
      return $goalWinners;
    }

    // We need to set UTC zone as far as Drupal stores dates in UTC zone.
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString(), new \DateTimeZone(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::STORAGE_TIMEZONE));
    // The checkins are saved with 0:0:0 time.
    $campaignStartDate->setTime(0, 0, 0);
    $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString(), new \DateTimeZone(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::STORAGE_TIMEZONE));
    $minVisitsGoal = !empty($campaign->field_min_visits_goal->value) ? $campaign->field_min_visits_goal->value : 0;

    // Get visits.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_member_checkin', 'ch');
    $query->join('openy_campaign_member', 'm', 'm.id = ch.member');
    $query->join('openy_campaign_member_campaign', 'mc', 'm.id = mc.member');

    $query->addField('mc', 'id', 'member_campaign');

    $query->condition('ch.date', $campaignStartDate->format('U'), '>=');
    $query->condition('ch.date', $campaignEndDate->format('U'), '<');
    if (!empty($branchId)) {
      $query->condition('m.branch', $branchId);
    }
    if ($withoutEmployees) {
      $query->condition('m.is_employee', FALSE);
    }

    $query->condition('mc.campaign', $campaign->id());

    $query->groupBy('ch.member');
    $query->groupBy('mc.id');
    $query->groupBy('mc.goal');

    $query->having('COUNT(ch.id) > 0 AND COUNT(ch.id) >= mc.goal AND COUNT(ch.id) >= :minGoal', [':minGoal' => $minVisitsGoal]);

    $query->orderRandom();

    $results = $query->execute()->fetchAll();

    foreach ($results as $item) {
      $memberCampaignId = $item->member_campaign;
      if (!in_array($memberCampaignId, $alreadyWinners)) {
        $goalWinners[] = $memberCampaignId;
      }
    }

    return $goalWinners;
  }

  /**
   * Get achieved Visit Goal members by branch.
   *
   * @param \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign
   *
   * @return bool
   */
  public function isGoalAchieved(MemberCampaign $memberCampaign) {
    /** @var \Drupal\node\NodeInterface $campaign */
    $campaign = $memberCampaign->getCampaign();

    // Get all enabled activities list.
    $activitiesOptions = openy_campaign_get_enabled_activities($campaign);

    // For disabled Visits Goal activity.
    if (!in_array('field_prgf_activity_visits', $activitiesOptions)) {
      return FALSE;
    }

    // We need to set UTC zone as far as Drupal stores dates in UTC zone.
    $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString(), new \DateTimeZone(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::STORAGE_TIMEZONE));
    // The checkins are saved with 0:0:0 time.
    $campaignStartDate->setTime(0, 0, 0);
    $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString(), new \DateTimeZone(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::STORAGE_TIMEZONE));
    $minVisitsGoal = !empty($campaign->field_min_visits_goal->value) ? $campaign->field_min_visits_goal->value : 0;

    // Get member with achieved goal.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('openy_campaign_member_checkin', 'ch');
    $query->join('openy_campaign_member_campaign', 'mc', 'ch.member = mc.member');

    $query->addField('mc', 'id', 'member_campaign');

    $query->condition('ch.date', $campaignStartDate->format('U'), '>=');
    $query->condition('ch.date', $campaignEndDate->format('U'), '<');

    $query->condition('mc.id', $memberCampaign->id());

    $query->groupBy('mc.goal');
    $query->groupBy('mc.id');
    $query->having('COUNT(ch.id) > 0 AND COUNT(ch.id) >= mc.goal AND COUNT(ch.id) >= :minGoal', [':minGoal' => $minVisitsGoal]);

    $results = $query->execute()->fetchAll();
    if (!empty($results)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Gets the color scheme for a campaign node.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   */
  public function getCampaignPalette(NodeInterface $node) {
    $palette = [];
    $campaign = $this->getNodeCampaignNode($node);
    $scheme_id = $campaign->get('field_campaign_palette')->getString();
    $color_info = color_get_info(OPENY_THEME);
    if (!empty($color_info['schemes'][$scheme_id])) {
      $palette = $color_info['schemes'][$scheme_id];
    }
    return $palette;
  }

  /**
   * Gets the active winner stream for display on each campaign and landing page.
   *
   * @param \Drupal\node\NodeInterface $campaign
   *
   * @return array
   */
  public function getWinnerStream(NodeInterface $campaign) {
    $winners = [];
    $streamType = $campaign->get('field_campaign_stream_type')->getString();
    switch ($streamType) {
      case 'all':
        $instantWinners = $this->getWinnersOfType('instant', $campaign->id());
        $visitGoalAchived = $this->getWinnersOfType('visit', $campaign->id());

        $winners = array_merge($instantWinners, $visitGoalAchived);
        break;

      case 'instant':
        $winners = $this->getWinnersOfType('instant', $campaign->id());
        break;

      case 'visit':
        $winners = $this->getWinnersOfType('visit', $campaign->id());
        break;

      default:
        break;
    }
    shuffle($winners);
    return $winners;
  }

  /**
   * Helper function to get winners by type.
   *
   * @param string $type
   * @param string $node_id
   *
   * @return array|\Drupal\views\ResultRow[]
   */
  private function getWinnersOfType($type, $node_id) {
    $result = [];
    $streamText = '';
    $config = $this->configFactory->get('openy_campaign.general_settings');
    switch ($type) {
      case 'instant':
        /** @var \Drupal\Core\Database\Query\Select $query */
        $query = $this->connection->select('openy_campaign_member_game', 'g');
        $query->join('openy_campaign_member', 'm', 'g.member = m.id');
        $query->join('openy_campaign_member_campaign', 'mc', 'mc.member = m.id');
        $query->condition('mc.campaign', $node_id);
        $query->condition('g.result', '%SORRY%', 'NOT LIKE');
        $query->condition('g.result', '%Did not win%', 'NOT LIKE');
        $query->fields('g', ['created']);
        $query->fields('m', ['first_name', 'last_name']);
        $query->orderBy('g.created', 'ASC');
        $query->range(0, 50);
        $result = $query->execute()->fetchAll();
        $streamText = $config->get('stream_text_game');
        break;

      case 'visit':
        $campaign = $this->entityTypeManager->getStorage('node')->load($node_id);
        // Calculate visit goal winners.
        $achievedMemberCampaignIds = $this->getVisitsGoalWinners($campaign);
        // Load needed information.
        if (!empty($achievedMemberCampaignIds)) {
          /** @var \Drupal\Core\Database\Query\Select $query */
          $query = $this->connection->select('openy_campaign_member_campaign', 'mc');
          $query->condition('mc.id', $achievedMemberCampaignIds, 'IN');
          $query->join('openy_campaign_member', 'm', 'mc.member = m.id');
          $query->fields('m', ['first_name', 'last_name']);
          $query->range(0, 50);
          $result = $query->execute()->fetchAll();
          $streamText = $config->get('stream_text_visits');
        }
        break;
    }
    if (count($result)) {
      foreach ($result as &$row) {
        if (isset($row->last_name)) {
          $row->last_name = substr($row->last_name, 0, 1);
        }
        if (isset($row->created)) {
          $row->created = $this->timeAgo($row->created);
        }
        $row->stream_text = $streamText;
      }
    }
    return $result;
  }

  /**
   * Returns a string date comparison in the format "1 day ago" etc.
   *
   * @param string $timestamp
   *
   * @return string
   */
  private function timeAgo($timestamp) {
    $now = new \DateTime('now');
    $date = new \DateTime();
    $date->setTimestamp($timestamp);
    $datediff = date_diff($now, $date);
    $message = '';
    $components = [
      ['y', 'year', 'years'], ['m', 'month', 'months'],
      ['d', 'day', 'days'], ['h', 'hour', 'hours'],
      ['i', 'minute', 'minutes'], ['s', 'second', 'seconds'],
    ];
    foreach ($components as $component) {
      if ($datediff->{$component[0]} > 0) {
        $message = $datediff->{$component[0]} . ' '
          . ($datediff->{$component[0]} > 1 ? t($component[2]) : $component[1]);
        break;
      }
    }
    return !empty($message) ? $message . ' ' . t('ago') : '';
  }

}
