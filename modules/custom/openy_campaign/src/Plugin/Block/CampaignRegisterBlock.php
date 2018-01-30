<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\openy_campaign\OpenYLocaleDate;

/**
 * Provides a 'Register' block.
 *
 * @Block(
 *   id = "campaign_register_block",
 *   admin_label = @Translation("Campaign Member Register"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampaignRegisterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $request_stack;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              FormBuilderInterface $formBuilder,
                              CampaignMenuServiceInterface $campaign_menu_service,
                              $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
    $this->campaignMenuService = $campaign_menu_service;
    $this->request_stack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration,
                                $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get campaign node from current page URL
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();

    $block['#cache']['max-age'] = 0;

    if ($campaign instanceof Node !== TRUE) {
      return $block;
    }

    $activeRegistration = TRUE;

    // Get site timezone
    $config = \Drupal::config('system.date');
    $configSiteDefaultTimezone = !empty($config->get('timezone.default')) ? $config->get('timezone.default') : date_default_timezone_get();
    $siteDefaultTimezone = new \DateTimeZone($configSiteDefaultTimezone);

    // Get localized versions of Campaign dates. Convert it to site timezone to compare with current date.
    $localeCampaignStart = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_start_date')->getString(), $siteDefaultTimezone);
    $localeCampaignEnd = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_end_date')->getString(), $siteDefaultTimezone);
    $localeRegistrationEnd = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_reg_end_date')->getString(), $siteDefaultTimezone);
    $localeRegistrationStart = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_reg_start_date')->getString(), $siteDefaultTimezone);

    // Define if we need to show register block or not.
    if ($localeCampaignStart->dateExpired() || $localeRegistrationEnd->dateExpired()) {
      $activeRegistration = FALSE;
    }

    $block = [
      '#theme' => 'openy_campaign_campaign_register',
      '#attached' => [
        'library' => [
          'openy_campaign/campaign_countdown'
        ],
        'drupalSettings' => [
          'campaignSettings' => [
            'startDate' => $localeCampaignStart->getDate()->format(DATETIME_DATETIME_STORAGE_FORMAT),
            'endDate' => $localeCampaignEnd->getDate()->format(DATETIME_DATETIME_STORAGE_FORMAT),
            'startRegDate' => $localeRegistrationStart->getDate()->format(DATETIME_DATETIME_STORAGE_FORMAT),
            'endRegDate' => $localeRegistrationEnd->getDate()->format(DATETIME_DATETIME_STORAGE_FORMAT),
          ]
        ]
      ],
      '#campaign' => $campaign,
      '#activeRegistration' => $activeRegistration,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    /**
     * @var \Drupal\node\Entity\Node $currentNode
     */
    $currentNode= $this->request_stack->getCurrentRequest()->get('node');

    if(empty($currentNode)) {
      return $block;
    }

    $currentNodeType = $currentNode->getType();

    if (!empty($campaign)
        && !(MemberCampaign::isLoggedIn($campaign->id()))
        && $currentNodeType !== 'campaign_page') {

      if (($localeRegistrationStart->dateExpired() && !$localeRegistrationEnd->dateExpired()) ||
        ($localeCampaignStart->dateExpired() && !$localeCampaignEnd->dateExpired())
      ) {
        // Show Register block form
        $form = $this->formBuilder->getForm(
          'Drupal\openy_campaign\Form\MemberRegistrationSimpleForm',
          $campaign->id()
        );
      }
      else {
        $form = [];
      }

      $block['#form'] = $form;
    }
    return $block;
  }
}
