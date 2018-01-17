<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\locale\Form\LocaleSettingsForm;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberCheckin;
use Drupal\webform\Plugin\WebformElement\DateTime;
use function GuzzleHttp\Psr7\str;
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
    /** @var Node $campaign */
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();

    $block['#cache']['max-age'] = 0;

    $activeRegistration = TRUE;

    // Get localized versions of our times.
    $campaignTimezone = new \DateTime($campaign->get('field_campaign_timezone')->getString());
    $campaignTimezone = $campaignTimezone->getTimezone();

    $localeCampaignStart = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_start_date')->getString());
    $localeCampaignStart->convertTimezone($campaignTimezone);

    $localeCampaignEnd = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_end_date')->getString());
    $localeCampaignEnd->convertTimezone($campaignTimezone);

    $localeRegistrationStart = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_reg_start_date')->getString());
    $localeRegistrationStart->convertTimezone($campaignTimezone);

    $localeRegistrationEnd = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_reg_end_date')->getString());
    $localeRegistrationEnd->convertTimezone($campaignTimezone);

    // Define if we need to show register block or not.
    if (!$localeCampaignStart->dateExpired() || $localeRegistrationEnd->dateExpired()) {
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
            'startDate' => $campaign->get('field_campaign_start_date')->value,
            'endDate' => $campaign->get('field_campaign_end_date')->value,
            'startRegDate' => $campaign->get('field_campaign_reg_start_date')->value,
            'endRegDate' => $campaign->get('field_campaign_reg_end_date')->value
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
     * @var Node $currentNode
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
