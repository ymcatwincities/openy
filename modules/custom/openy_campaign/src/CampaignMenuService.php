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
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AjaxResponse;

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
   * Constructs a new CampMenuService.
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

    // Get Campaign node with reference to given Landing page node
    $entity_query_service = $this->container->get('entity.query');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $entity_query_service->get('node')
      ->condition('status', 1)
      ->condition('type', 'campaign');
    $orGroup = $query->orConditionGroup()
      ->condition('field_campaign_pages', $node->id(), 'IN')
      ->condition('field_my_progress_page', $node->id())
      ->condition('field_rules_prizes_page', $node->id())
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
   * @param \Drupal\node\NodeInterface $node
   *   The Campaign node.
   *
   * @return array
   *   Array of menu links.
   */
  private function getCampaignNodeCampaignMenu(NodeInterface $node) {
    /** @var Node $node */
    if ($node->bundle() != 'campaign') {
      return [];
    }

    $links = [];

    /** @var Node $landingPage */
    $landingPage = $this->getActiveCampaignPage($node);
    $parameters = ['campaign_id' => $node->id(), 'landing_page_id' => $landingPage->id()];
    // Main tab
    $links['campaign'] = [
      '#type' => 'link',
      '#title' => $node->getTitle(),
      '#url' => Url::fromRoute('openy_campaign.campaign-page', $parameters),
      '#attributes' => [
        'class' => [
          'campaign-page',
          'node-' . $landingPage->id(),
          'active'
        ],
      ],
    ];

    // About the challenge tab
    $about = $node->field_about_challenge_page->entity;
    $links['about'] = [
      '#type' => 'link',
      '#title' => 'About the challenge',
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $about->id()]),
      '#attributes' => [
        'class' => [
          'campaign-page',
          'node-' . $about->id(),
          'active'
        ],
      ],
    ];

    /** @var Node $myProgress */
    $myProgress = $node->field_my_progress_page->entity;
    $parameters = ['campaign_id' => $node->id(), 'landing_page_id' => $myProgress->id()];
    // My progress link
    $links['progress'] = [
      '#type' => 'link',
      '#title' => t('My progress'),
      '#url' => Url::fromRoute('openy_campaign.campaign-page', $parameters),
      '#attributes' => [
        'class' => [
          'campaign-my-progress',
          'node-' . $myProgress->id(),
        ],
      ],
    ];

    /** @var Node $rules */
    $rules = $node->field_rules_prizes_page->entity;
    $parameters = ['campaign_id' => $node->id(), 'landing_page_id' => $rules->id()];
    $links['rules'] = [
      '#type' => 'link',
      '#title' => t('Detailed Rules'),
      '#url' => Url::fromRoute('openy_campaign.campaign-page', $parameters),
      '#attributes' => [
        'class' => [
          'campaign-rules',
          'node-' . $rules->id(),
        ],
      ],
    ];

    return $links;
  }

  /**
   * Get Campaign page referenced in Campaign node.
   *
   * @param \Drupal\node\NodeInterface $campaign Campaign node.
   *
   * @return mixed Published campaign page or FALSE if there is no published referenced campaign page.
   */
  private function getActiveCampaignPage($campaign) {
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

    return reset($publishedPages);
  }

  /**
   * Get Landing page based on query string tab or active main landing page.
   *
   * @param \Drupal\node\Entity\Node $campaign Campaign node.
   *
   * @return \Drupal\node\Entity\Node | null | static
   */
  public function showRequestLandingPage($campaign) {
    // Check query string for tab parameter.
    $tab = \Drupal::request()->get('tab');
    if (!empty($tab)) {
      $aliasLanding = \Drupal::service('path.alias_manager')->getPathByAlias($tab);
      $route = Url::fromUri("internal:/" . $aliasLanding);
      if ($route->isRouted()) {
        $paramsLanding = $route->getRouteParameters();
      }
    }

    // Show My Progress page only for logged in members.
    /** @var Node $myProgressId */
    $myProgressId = $campaign->get('field_my_progress_page')->getString();
    $isMyProgress = !empty($paramsLanding['node']) && $paramsLanding['node'] == $myProgressId;
    // Show Campaign main landing page.
    if ($isMyProgress && !MemberCampaign::isLoggedIn($campaign->id())) {
      $landingPage = $this->getActiveCampaignPage($campaign);

      return $landingPage;
    }

    // Show landing page by query string.
    if (!empty($paramsLanding['node']) and is_numeric($paramsLanding['node'])) {
      $landingPage = Node::load($paramsLanding['node']);
    }
    // Show the first published Landing page.
    else {
      $landingPage = $this->getActiveCampaignPage($campaign);
    }

    return $landingPage;
  }

  /**
   * Place new landing page Content area paragraphs instead of current ones.
   *
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxReplaceLandingPage($landing_page_id) {
    $response = new AjaxResponse();

    /** @var Node $node New landing page node to replace. */
    $node = Node::load($landing_page_id);
    if (empty($node)) {
      return $response;
    }

    $fieldsView = [];
    foreach ($node->field_content as $item) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $item->entity;
      $viewBuilder = $this->entityTypeManager->getViewBuilder($paragraph->getEntityTypeId());
      $fieldsView[] = $viewBuilder->view($paragraph, 'default');
    }
    $fieldsRender = '<section class="wrapper-field-content">' . $this->renderer->renderRoot($fieldsView) . '</section>';

    // Replace Content area of current landing page with all paragraphs from field-content of new landing page node.
    $response->addCommand(new ReplaceCommand('.wrapper-field-content', $fieldsRender));

    // Set 'active' class to menu link.
    $response->addCommand(new InvokeCommand('.campaign-menu a', 'removeClass', ['active']));
    $response->addCommand(new InvokeCommand('.campaign-menu a.node-' . $landing_page_id, 'addClass', ['active']));

    // Replace URL query string
    $queryParameter = trim($node->toUrl()->toString(), "/");
    $response->addCommand(new InvokeCommand('#drupal-modal', 'replaceQuery', [$queryParameter]));

    return $response;
  }
}
