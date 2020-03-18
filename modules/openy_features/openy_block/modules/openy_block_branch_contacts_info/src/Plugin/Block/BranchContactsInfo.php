<?php

namespace Drupal\openy_block_branch_contacts_info\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Node Contacts Info' block.
 *
 * @Block(
 *   id = "branch_contacts_info",
 *   admin_label = @Translation("Branch Contacts Info Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class BranchContactsInfo extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * RouteMatch service instance
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\pathauto\AliasCleaner
   */
  protected $alias_cleaner;

  /**
   *   Plugin config
   *
   * @param array $configuration
   *   Plugin id
   * @param string $plugin_id
   *   Plugin definition
   * @param mixed $plugin_definition
   *   RouteMatch service instance
   * @param $routeMatch
   *   Renderer service instance
   * @param $renderer
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $routeMatch, $renderer, $alias_cleaner) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->renderer = $renderer;
    $this->alias_cleaner = $alias_cleaner;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('renderer'),
      $container->get('pathauto.alias_cleaner')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render_array = [];

    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && in_array($node->getType(), ['branch', 'camp', 'facility'])) {
      $render_array = ['#theme' => 'block_branch_contacts_info'];
      $render_array['#node_bundle'] = $node->getType();

      $address = $node->get('field_location_address')->get(0);
      if ($address) {
        $address_array = $address->toArray();
        $location_address = "{$address_array['address_line1']} {$address_array['locality']}, {$address_array['administrative_area']} {$address_array['postal_code']}";
        $directions_url = Url::fromUri('https://www.google.com/maps/dir/', [
          'query' => [
            'api' => 1,
            'destination' => $this->alias_cleaner
              ->cleanString($location_address),
          ],
        ])->toString();
        $render_array['#directions_url'] = $directions_url;
        $render_array['#address_title'] = $location_address;
      }

      $phone = $node->get('field_location_phone')->get(0);
      if ($phone) {
        $render_array['#phone'] = $phone->getString();
      }

      $render_array['#fax'] = $node->field_location_fax->value;
      $render_array['#email'] = $node->field_location_email->value;
      $render_array['#directions_field_title'] = $node->field_location_directions->title;
      $render_array['#directions_field_url'] = $node->field_location_directions->url;

      $render_array['#branch_title'] = $node->getTitle();
      $branch_selector = openy_branch_selector_get_link($node->id());
      $render_array['#openy_branch_selector'] = $this->renderer->render($branch_selector);

      $branch_hours = $node->get('field_branch_hours')->view([
        'type' => 'openy_today_custom_hours',
        'settings' => [],
      ]);
      $render_array['#branch_hours'] = $this->renderer->render($branch_hours);
    }

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = $this->routeMatch->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
