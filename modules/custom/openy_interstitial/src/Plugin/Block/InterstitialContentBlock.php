<?php

namespace Drupal\openy_interstitial\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an 'Interstitial Content Block' dialog block.
 *
 * @Block(
 *   id = "interstitial_content_block",
 *   admin_label = @Translation("Interstitial Content Block"),
 *   category = @Translation("Interstitial Blocks")
 * )
 */
class InterstitialContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   */
  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->container = $container;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];
    $block['#cache']['max-age'] = INTERSTITIAL_BLOCK_CACHE_TIME;

    // Get all Interstitial page node
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $entity_type_manager->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->condition('type', 'interstitial_page');
    $query->range(0, 1);
    $res = $query->execute();

    if (empty(reset($res))) {
      return $block;
    }

    /** @var Node $interstitialPage */
    $interstitialPage = Node::load(reset($res));

    // Check COOKIES if we need to show this block
    $showTimes = $interstitialPage->field_show_times->value;
    $cookieCount = $this->request->cookies->get('OpenYInterstitialBlock');
    if ($cookieCount > $showTimes) {
      return $block;
    }

    $block['#attached']['library'][] = 'openy_interstitial/openy_interstitial';

    $block['#attached']['drupalSettings']['openyInterstitial']['InterstitialContentBlock'] = [
      'time' => $interstitialPage->field_close_time->value,
      'interaction' => $interstitialPage->field_close_interaction->value,
      'title' => $interstitialPage->field_dialog_title->value,
      'showTimes' => $showTimes,
    ];

    $block['content'] = [
      '#markup' => check_markup($interstitialPage->body->value, $interstitialPage->body->format),
      '#prefix' => '<div id="interstitial-block">',
      '#suffix' => '</div>',
    ];

    return $block;
  }

}
