<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Provides a Campaign user menu block.
 *
 * @Block(
 *   id = "campaign_user_menu_block",
 *   admin_label = @Translation("Campaign user menu block"),
 *   category = @Translation("Campaign"),
 * )
 */
class CampaignUserMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CampaignMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param RouteMatchInterface $route_match
   *   The Campaign menu service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Check if current page is campaign
    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $this->routeMatch->getParameter('node');
    if ($campaign->getType() != 'campaign') {
      return $build;
    }

    $build['register'] = [
      '#type' => 'link',
      '#title' => $this->t('Register'),
      '#url' => Url::fromRoute('openy_campaign.member-action', ['action' => 'registration', 'campaign_id' => $campaign->id()]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'register'
        ],
      ],
    ];

    $build['login'] = [
      '#type' => 'link',
      '#title' => $this->t('Sign in'),
      '#url' => Url::fromRoute('openy_campaign.member-action', ['action' => 'login', 'campaign_id' => $campaign->id()]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'login'
        ],
      ],
    ];

    $build['logout'] = [
      '#type' => 'link',
      '#title' => $this->t('Logout'),
      '#url' => Url::fromRoute('openy_campaign.member-logout', ['campaign_id' => $campaign->id()]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'logout'
        ],
      ],
    ];

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }
}