<?php

namespace Drupal\openy_campaign\Theme;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * {@inheritdoc}
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new ThemeNegotiator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {

    $custom_page_routes = [
      'openy_campaign.team_member.list',
      'openy_campaign.team_member.edit_form',
      'openy_campaign.member-registration-portal',
      'openy_campaign.campaign_reports_live_scorecard',
      'openy_campaign.scorecard-page',
    ];
    if (in_array($route_match->getRouteName(), $custom_page_routes)) {
      return TRUE;
    }

    $possible_routes = [
      'entity.node.canonical',
      'openy_campaign.campaign_game'
    ];

    if (in_array($route_match->getRouteName(), $possible_routes)) {
      $node = $route_match->getParameter('node');

      if (
        $route_match->getRouteName() === 'openy_campaign.campaign_game' ||
        in_array($node->getType(), ['campaign', 'campaign_page'])
      ) {
        return TRUE;
      }

      // Check if requested node uses in campaign.
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('status', 1)
        ->condition('type', 'campaign');
      $orGroup = $query->orConditionGroup()
        ->condition('field_campaign_pages', $node->id(), 'IN')
        ->condition('field_pause_landing_page', $node->id());

      $campaignLandingPages = $query->condition($orGroup)->execute();
      if (!empty($campaignLandingPages)) {
        return TRUE;
      }

    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Here return the actual theme name.
    return CAMPAIGN_THEME;
  }

}
