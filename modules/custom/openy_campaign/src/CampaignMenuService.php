<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Constructs a new CampMenuService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContainerInterface $container) {
    $this->entityTypeManager = $entity_type_manager;
    $this->container = $container;
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
    if ($node->bundle() != 'campaign') {
      return [];
    }

    $links = [];

    $landingPage = $this->getActiveCampaignPage($node);
    $links['campaign'] = [
      '#type' => 'link',
      '#title' => $node->getTitle(),
      '#url' => Url::fromRoute('openy_campaign.landing-page', ['landing_page_id' => $landingPage->id()]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'campaign-page',
          'node-' . $landingPage->id(),
          'active'
        ],
      ],
      '#attached' => [
        'library' => [
          'core/drupal.ajax'
        ]
      ]
    ];

    // Show only for logged in members
    if (MemberCampaign::isLoggedIn($node->id())) {
      $links['progress'] = [
        '#type' => 'link',
        '#title' => t('My progress'),
        '#url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
        '#attributes' => [
          'class' => [
            'my-progress'
          ],
        ],
      ];

      $links['activity'] = [
        '#type' => 'link',
        '#title' => t('Activity Tracking'),
        '#url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
        '#attributes' => [
          'class' => [
            'activity-tracking'
          ],
        ],
      ];
    }
    else {
      $links['login'] = [
        '#type' => 'link',
        '#title' => t('Register/Login'),
        '#url' => Url::fromRoute('openy_campaign.member-action', ['action' => 'login', 'campaign_id' => $node->id()]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'login'
          ],
        ],
        '#attached' => [
          'library' => [
            'core/drupal.dialog.ajax'
          ]
        ]
      ];
    }

    $rulesID = $node->get('field_rules_prizes_page')->getString();
    $links['rules'] = [
      '#type' => 'link',
      '#title' => t('Detailed Rules'),
      '#url' => Url::fromRoute('openy_campaign.landing-page', ['landing_page_id' => $rulesID]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'campaign-rules',
          'node-' . $rulesID,
        ],
      ],
      '#attached' => [
        'library' => [
          'core/drupal.ajax'
        ]
      ]
    ];

    return $links;
  }

  /**
   * Get Landing page referenced in Campaign node.
   *
   * @param \Drupal\node\NodeInterface $campaign Campaign node.
   *
   * @return mixed Published landing page or FALSE if there is no published referenced landing page.
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
    foreach ($landingPages as $node) {
      if ($node->isPublished()) {
        $publishedPages[] = $node;
      }
    }

    return reset($publishedPages);
  }
}
