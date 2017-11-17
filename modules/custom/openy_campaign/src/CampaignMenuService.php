<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\RendererInterface;

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
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContainerInterface $container, RouteMatchInterface $route_match, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->container = $container;
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
  }

  /**
   * Get campaign node from current page URL.
   *
   * @return bool|\Drupal\Node\Entity\Node
   */
  public function getCampaignNodeFromRoute() {
    $node = $this->routeMatch->getParameter('node');
    // For custom routes - get campaign_id
    if ($node instanceof NodeInterface !== TRUE) {
      $campaignId = $this->routeMatch->getParameter('campaign_id');
      if (is_null($campaignId)) {
        $campaignId = $this->container->get('request_stack')->getCurrentRequest()->get('campaign_id');
      }
      $node = !empty($campaignId) ? Node::load($campaignId) : FALSE;
    }
    if (empty($node)) {
      return FALSE;
    }
    /** @var \Drupal\Node\Entity\Node $campaign */
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

    // Get Campaign node with reference to given Landing page node
    $entity_query_service = $this->container->get('entity.query');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $entity_query_service->get('node')
      ->condition('status', 1)
      ->condition('type', 'campaign');
    $orGroup = $query->orConditionGroup()
      ->condition('field_campaign_pages', $node->id(), 'IN')
      ->condition('field_pause_landing_page', $node->id());
    $nids = $query->condition($orGroup)->execute();

    /** @var NodeInterface $campaign */
    $campaign = $this->entityTypeManager->getStorage('node')->load(reset($nids));

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
    /** @var Node $campaign */
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

    /** @var Node $landingPage */
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
   * @param \Drupal\node\NodeInterface $campaign Campaign node.
   *
   * @return mixed Published campaign page or FALSE if there is no published referenced campaign page.
   */
  public function getActiveCampaignPage($campaign) {
    // Check if Campaign is Paused
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

    // Load Landing page and check if it's published
    $landingPages = Node::loadMultiple($landingPageIds);
    /** @var Node $node */
    foreach ($landingPages as $node) {
      if ($node->isPublished()) {
        $publishedPages[] = $node;
      }
    }

    // If all pages are disabled, show Pause page.
    if (empty($publishedPages) && isset($fieldPauseLandingPage[0]['target_id'])) {
      return Node::load($fieldPauseLandingPage[0]['target_id']);
    }

    return reset($publishedPages);
  }

  /**
   * Check permissions of the current page.
   *
   * @param \Drupal\node\Entity\Node $node Campaign/Campaign Page node.
   *
   * @return boolean
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
}
