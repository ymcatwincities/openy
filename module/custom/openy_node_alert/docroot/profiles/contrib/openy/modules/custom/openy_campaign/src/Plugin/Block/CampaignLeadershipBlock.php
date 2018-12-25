<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Leadership' block.
 *
 * @Block(
 *   id = "campaign_leadership_block",
 *   admin_label = @Translation("Campaign Leadership"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampaignLeadershipBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   *   The Campaign menu service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder, CampaignMenuServiceInterface $campaign_menu_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
    $this->campaignMenuService = $campaign_menu_service;
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
      $container->get('openy_campaign.campaign_menu_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];
    $block['#cache']['max-age'] = CAMPAIGN_CACHE_TIME;

    // Get campaign node from current page URL.
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();
    if (empty($campaign)) {
      return $block;
    }

    // Show Winners block form.
    $form = $this->formBuilder->getForm('Drupal\openy_campaign\Form\LeadershipBlockForm', $campaign->id());
    $block['form'] = $form;

    return $block;
  }

}
