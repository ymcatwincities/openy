<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\openy_campaign\OpenYLocaleDate;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

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

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $formBuilder,
    CampaignMenuServiceInterface $campaign_menu_service,
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
    $this->campaignMenuService = $campaign_menu_service;
    $this->request_stack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get campaign node from current page URL.
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();

    $block['#cache'] = [
      'max-age' => 3600,
    ];

    if ($campaign instanceof Node !== TRUE) {
      return $block;
    }

    $activeRegistration = TRUE;

    // Get site timezone.
    $config = $this->configFactory->get('system.date');
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

    $utcCampaignStart = $localeCampaignStart->getDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $utcCampaignEnd = $localeCampaignEnd->getDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $utcCampaignRegStart = $localeRegistrationStart->getDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $utcCampaignRegEnd = $localeRegistrationEnd->getDate()->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $campaignTimezone = !empty($campaign->get('field_campaign_timezone')->getString()) ? new \DateTimeZone($campaign->get('field_campaign_timezone')->getString()) : $siteDefaultTimezone;
    $startDateCampaignTz = $localeCampaignStart->convertTimezone($campaignTimezone);
    $endDateCampaignTz = $localeCampaignEnd->convertTimezone($campaignTimezone);

    $start_day = $startDateCampaignTz->format('d');
    $start_month = $startDateCampaignTz->format('F');
    $start_year = $startDateCampaignTz->format('Y');

    $end_day = $endDateCampaignTz->format('d');
    $end_month = $endDateCampaignTz->format('F');
    $end_year = $endDateCampaignTz->format('Y');

    // Format campaign period.
    $start_date = "$start_month $start_day";
    $end_date = $end_day;
    if ($start_month != $end_month) {
      $end_date = "$end_month $end_date";
    }
    $end_date = "$end_date, $end_year";
    if ($start_year != $end_year) {
      $start_date = "$start_date, $start_year";
    }

    $block = [
      '#theme' => 'openy_campaign_campaign_register',
      '#attached' => [
        'library' => [
          'openy_campaign/campaign_countdown'
        ],
        'drupalSettings' => [
          'campaignSettings' => [
            'startDate' => $utcCampaignStart,
            'endDate' => $utcCampaignEnd,
            'startRegDate' => $utcCampaignRegStart,
            'endRegDate' => $utcCampaignRegEnd,
          ]
        ]
      ],
      '#campaignDates' => "$start_date - $end_date",
      '#campaign' => $campaign,
      '#activeRegistration' => $activeRegistration,
      '#cache' => [
        'max-age' => CAMPAIGN_CACHE_TIME,
      ],
    ];

    /**
     * @var \Drupal\node\Entity\Node $currentNode
     */
    $currentNode = $this->request_stack->getCurrentRequest()->get('node');

    if (empty($currentNode)) {
      return $block;
    }

    $currentNodeType = $currentNode->getType();

    if (!empty($campaign)
        && !(MemberCampaign::isLoggedIn($campaign->id()))
        && $currentNodeType !== 'campaign_page') {

      if (($localeRegistrationStart->dateExpired() && !$localeRegistrationEnd->dateExpired()) ||
        ($localeCampaignStart->dateExpired() && !$localeCampaignEnd->dateExpired())
      ) {
        // Show Register block form.
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
